<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreWorkLogRequest;
use App\Http\Requests\Finance\UpdateWorkLogRequest;
use App\Models\ManHourRate;
use App\Models\Project;
use App\Models\ProjectMemberRate;
use App\Models\ProjectWorkLog;
use App\Models\User;
use App\Services\Finance\LaborCostCalculatorService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WorkLogController extends Controller
{
    public function __construct(
        protected LaborCostCalculatorService $calculator
    ) {}

    /**
     * Display a listing of labor work logs with financial KPI cards.
     */
    public function index(Request $request): View
    {
        $query = ProjectWorkLog::with(['project', 'user'])->latest('work_date');

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('work_date', [$request->start_date, $request->end_date]);
        }

        // Clone for KPI aggregates
        $kpiQuery = clone $query;
        $totalHours = (float)(clone $kpiQuery)->sum('hours_worked');
        $totalUnpaidNominal = (float)(clone $kpiQuery)->where('payment_status', 'unpaid')->sum('calculated_nominal');
        $totalPendingNominal = (float)(clone $kpiQuery)->where('payment_status', 'pending_approval')->sum('calculated_nominal');
        $totalPaidNominal = (float)(clone $kpiQuery)->where('payment_status', 'paid')->sum('calculated_nominal');
        $grandTotalNominal = (float)(clone $kpiQuery)->sum('calculated_nominal');

        $workLogs = $query->paginate(20)->withQueryString();

        $projects = Project::orderBy('title')->get(['id', 'title', 'project_code']);
        $users = User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'role']);
        $masterRates = ManHourRate::all();

        return view('finance.work-logs.index', compact(
            'workLogs',
            'projects',
            'users',
            'masterRates',
            'totalHours',
            'totalUnpaidNominal',
            'totalPendingNominal',
            'totalPaidNominal',
            'grandTotalNominal'
        ));
    }

    /**
     * Real-time rate calculation endpoint for interactive Alpine.js modal.
     */
    public function calculateRate(Request $request): JsonResponse
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'user_id' => 'required|exists:users,id',
            'work_date' => 'required|date',
            'hours_worked' => 'required|numeric|min:0.1',
            'role_slug' => 'nullable|string',
            'skill_level' => 'nullable|string',
            'custom_base_rate' => 'nullable|numeric|min:0',
            'force_weekend' => 'nullable|boolean',
            'force_overtime' => 'nullable|boolean',
        ]);

        $result = $this->calculator->calculate(
            user: (int)$request->user_id,
            project: (int)$request->project_id,
            workDate: $request->work_date,
            hoursWorked: (float)$request->hours_worked,
            roleSlug: $request->role_slug,
            skillLevel: $request->skill_level ?: 'middle',
            forceWeekend: $request->has('force_weekend') ? $request->boolean('force_weekend') : null,
            forceOvertime: $request->has('force_overtime') ? $request->boolean('force_overtime') : null,
            customBaseRate: $request->filled('custom_base_rate') ? (float)$request->custom_base_rate : null
        );

        return response()->json([
            'status' => 'success',
            'data' => $result
        ]);
    }

    /**
     * Store a newly created work log entry.
     */
    public function store(StoreWorkLogRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $projectId = (int)$data['project_id'];

        if (!empty($data['date_ranges']) && is_array($data['date_ranges'])) {
            $earliestStart = null;
            $latestEnd = null;
            $totalDays = 0;
            $totalHours = 0.0;
            $totalNominal = 0.0;
            $anyWeekend = false;
            $anyOvertime = false;

            $baseRate = (float)($data['base_rate'] ?? 0);
            $resolvedRole = $data['role_in_project'] ?? null;
            // Resolve base rate jika belum terisi
            if ($baseRate <= 0) {
                $calc = $this->calculator->calculate(
                    user: (int)$data['user_id'],
                    project: (int)$data['project_id'],
                    workDate: $data['date_ranges'][0]['start_date'],
                    hoursWorked: 8.0,
                    roleSlug: $data['role_in_project'] ?? null,
                    skillLevel: $data['skill_level'] ?? 'middle'
                );
                $baseRate = (float)$calc['base_rate'];
                if (empty($resolvedRole) && !empty($calc['resolved_role'])) {
                    $resolvedRole = $calc['resolved_role'];
                }
            }

            $rangesData = [];
            foreach ($data['date_ranges'] as $range) {
                $start = Carbon::parse($range['start_date']);
                $end = Carbon::parse($range['end_date']);
                $days = $start->diffInDays($end) + 1;
                $hpd = (float)($range['hours_per_day'] ?? 8.0);
                $rangeHours = round($days * $hpd, 2);

                $isWeekend = !empty($range['is_weekend']);
                $isOvertime = !empty($range['is_overtime']);
                if ($isWeekend) $anyWeekend = true;
                if ($isOvertime) $anyOvertime = true;

                $weekendMult = $isWeekend ? 1.10 : 1.0;
                $overtimeMult = $isOvertime ? 1.20 : 1.0;
                $rangeNominal = round($rangeHours * $baseRate * $weekendMult * $overtimeMult);

                $totalDays += $days;
                $totalHours += $rangeHours;
                $totalNominal += $rangeNominal;

                if (!$earliestStart || $start->lt($earliestStart)) $earliestStart = $start;
                if (!$latestEnd || $end->gt($latestEnd)) $latestEnd = $end;

                $rangesData[] = [
                    'start_date'    => $start->toDateString(),
                    'end_date'      => $end->toDateString(),
                    'days_count'    => $days,
                    'hours_per_day' => $hpd,
                    'hours_worked'  => $rangeHours,
                    'is_weekend'    => $isWeekend,
                    'is_overtime'   => $isOvertime,
                    'nominal'       => $rangeNominal,
                ];
            }

            $paymentDate = ($data['payment_status'] === 'paid')
                ? (!empty($data['payment_date']) ? $data['payment_date'] : Carbon::today()->toDateString())
                : null;

            // Buat HANYA SATU record log kerja untuk seluruh rentang tanggal
            $log = ProjectWorkLog::create([
                'project_id'          => (int)$data['project_id'],
                'user_id'             => (int)$data['user_id'],
                'work_date'           => $earliestStart->toDateString(),
                'end_date'            => $latestEnd->toDateString(),
                'days_count'          => $totalDays,
                'hours_per_day'       => $totalDays > 0 ? round($totalHours / $totalDays, 2) : 8.0,
                'hours_worked'        => $totalHours,
                'role_in_project'     => $resolvedRole,
                'source_account_type' => $data['source_account_type'],
                'base_rate'           => $baseRate,
                'calculated_nominal'  => $totalNominal,
                'is_weekend'          => $anyWeekend,
                'is_overtime'         => $anyOvertime,
                'payment_status'      => $data['payment_status'],
                'payment_date'        => $paymentDate,
                'date_ranges_data'    => $rangesData,
                'notes'               => $data['notes'] ?? null,
                'audit_notes'         => $data['audit_notes'] ?? null,
            ]);

            $formattedHours = (fmod($totalHours, 1.0) === 0.0) ? (int)$totalHours : $totalHours;
            $message = "Log kerja berhasil dicatat ({$totalDays} hari kerja, {$formattedHours} jam).";

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => $message,
                    'total_days' => $totalDays,
                    'total_hours' => $totalHours,
                    'data' => $log,
                ]);
            }

            if ($request->filled('redirect_to')) {
                return redirect($request->redirect_to)->with('success', $message);
            }

            return redirect()->route('projects.show', $projectId)
                ->with('success', $message);
        }

        // Backward compatibility: Single record creation
        $log = DB::transaction(function () use ($data) {
            $startDate = Carbon::parse($data['work_date']);
            $endDate = !empty($data['end_date']) ? Carbon::parse($data['end_date']) : $startDate;
            $daysCount = (int)($data['days_count'] ?? ($startDate->diffInDays($endDate) + 1));
            $hoursPerDay = isset($data['hours_per_day']) ? (float)$data['hours_per_day'] : (float)($data['hours_worked'] ?? 8.0);
            $totalHours = isset($data['hours_worked']) && !isset($data['hours_per_day']) 
                ? (float)$data['hours_worked'] 
                : round($daysCount * $hoursPerDay, 2);

            $baseRate = (float)($data['base_rate'] ?? 0);
            $resolvedRole = $data['role_in_project'] ?? null;
            if ($baseRate <= 0) {
                $calc = $this->calculator->calculate(
                    user: (int)$data['user_id'],
                    project: (int)$data['project_id'],
                    workDate: $startDate->toDateString(),
                    hoursWorked: $totalHours,
                    roleSlug: $resolvedRole,
                    skillLevel: $data['skill_level'] ?? 'middle'
                );
                $baseRate = (float)$calc['base_rate'];
                if (empty($resolvedRole) && !empty($calc['resolved_role'])) {
                    $resolvedRole = $calc['resolved_role'];
                }
            }

            $isWeekend = (bool)($data['is_weekend'] ?? false);
            $isOvertime = (bool)($data['is_overtime'] ?? false);
            $weekendMultiplier = $isWeekend ? 1.10 : 1.0;
            $overtimeMultiplier = $isOvertime ? 1.20 : 1.0;
            $calculatedNominal = round($totalHours * $baseRate * $weekendMultiplier * $overtimeMultiplier);

            $paymentDate = null;
            if ($data['payment_status'] === 'paid') {
                $paymentDate = !empty($data['payment_date']) ? $data['payment_date'] : Carbon::today()->toDateString();
            }

            $singleRangeData = [
                [
                    'start_date'    => $startDate->toDateString(),
                    'end_date'      => $endDate->toDateString(),
                    'days_count'    => $daysCount,
                    'hours_per_day' => $hoursPerDay,
                    'hours_worked'  => $totalHours,
                    'is_weekend'    => $isWeekend,
                    'is_overtime'   => $isOvertime,
                    'nominal'       => $calculatedNominal,
                ]
            ];

            return ProjectWorkLog::create([
                'project_id'          => (int)$data['project_id'],
                'user_id'             => (int)$data['user_id'],
                'work_date'           => $startDate->toDateString(),
                'end_date'            => $endDate->toDateString(),
                'days_count'          => $daysCount,
                'hours_per_day'       => $hoursPerDay,
                'hours_worked'        => $totalHours,
                'role_in_project'     => $resolvedRole,
                'source_account_type' => $data['source_account_type'],
                'base_rate'           => $baseRate,
                'calculated_nominal'  => $calculatedNominal,
                'is_weekend'          => $isWeekend,
                'is_overtime'         => $isOvertime,
                'payment_status'      => $data['payment_status'],
                'payment_date'        => $paymentDate,
                'date_ranges_data'    => $singleRangeData,
                'notes'               => $data['notes'] ?? null,
                'audit_notes'         => $data['audit_notes'] ?? null,
            ]);
        });

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Log kerja berhasil dicatat.',
                'data' => $log->load(['project', 'user'])
            ]);
        }

        if ($request->filled('redirect_to')) {
            return redirect($request->redirect_to)->with('success', 'Log jam kerja berhasil dicatat.');
        }

        return redirect()->route('projects.show', $projectId)->with('success', 'Log jam kerja berhasil dicatat.');
    }

    /**
     * Update an existing work log entry.
     */
    public function update(UpdateWorkLogRequest $request, ProjectWorkLog $workLog): RedirectResponse|JsonResponse
    {
        $data = $request->validated();

        if (!empty($data['date_ranges']) && is_array($data['date_ranges'])) {
            $earliestStart = null;
            $latestEnd = null;
            $totalDays = 0;
            $totalHours = 0.0;
            $totalNominal = 0.0;
            $anyWeekend = false;
            $anyOvertime = false;

            $baseRate = isset($data['base_rate']) && $data['base_rate'] > 0 ? (float)$data['base_rate'] : (float)$workLog->base_rate;
            $resolvedRole = $data['role_in_project'] ?? $workLog->role_in_project;

            if ($baseRate <= 0) {
                $calc = $this->calculator->calculate(
                    user: (int)$data['user_id'],
                    project: (int)$data['project_id'],
                    workDate: $data['date_ranges'][0]['start_date'],
                    hoursWorked: 8.0,
                    roleSlug: $resolvedRole,
                    skillLevel: $data['skill_level'] ?? 'middle'
                );
                $baseRate = (float)$calc['base_rate'];
                if (empty($resolvedRole) && !empty($calc['resolved_role'])) {
                    $resolvedRole = $calc['resolved_role'];
                }
            }

            $rangesData = [];
            foreach ($data['date_ranges'] as $range) {
                $start = Carbon::parse($range['start_date']);
                $end = Carbon::parse($range['end_date']);
                $days = $start->diffInDays($end) + 1;
                $hpd = (float)($range['hours_per_day'] ?? 8.0);
                $rangeHours = round($days * $hpd, 2);

                $isWeekend = !empty($range['is_weekend']);
                $isOvertime = !empty($range['is_overtime']);
                if ($isWeekend) $anyWeekend = true;
                if ($isOvertime) $anyOvertime = true;

                $weekendMult = $isWeekend ? 1.10 : 1.0;
                $overtimeMult = $isOvertime ? 1.20 : 1.0;
                $rangeNominal = round($rangeHours * $baseRate * $weekendMult * $overtimeMult);

                $totalDays += $days;
                $totalHours += $rangeHours;
                $totalNominal += $rangeNominal;

                if (!$earliestStart || $start->lt($earliestStart)) $earliestStart = $start;
                if (!$latestEnd || $end->gt($latestEnd)) $latestEnd = $end;

                $rangesData[] = [
                    'start_date'    => $start->toDateString(),
                    'end_date'      => $end->toDateString(),
                    'days_count'    => $days,
                    'hours_per_day' => $hpd,
                    'hours_worked'  => $rangeHours,
                    'is_weekend'    => $isWeekend,
                    'is_overtime'   => $isOvertime,
                    'nominal'       => $rangeNominal,
                ];
            }

            $paymentDate = $workLog->payment_date;
            if ($data['payment_status'] === 'paid') {
                $paymentDate = !empty($data['payment_date']) ? $data['payment_date'] : ($workLog->payment_date ? $workLog->payment_date->toDateString() : Carbon::today()->toDateString());
            } elseif ($data['payment_status'] === 'unpaid') {
                $paymentDate = null;
            }

            $workLog->update([
                'project_id'          => (int)$data['project_id'],
                'user_id'             => (int)$data['user_id'],
                'work_date'           => $earliestStart->toDateString(),
                'end_date'            => $latestEnd->toDateString(),
                'days_count'          => $totalDays,
                'hours_per_day'       => $totalDays > 0 ? round($totalHours / $totalDays, 2) : 8.0,
                'hours_worked'        => $totalHours,
                'role_in_project'     => $resolvedRole,
                'source_account_type' => $data['source_account_type'],
                'base_rate'           => $baseRate,
                'calculated_nominal'  => $totalNominal,
                'is_weekend'          => $anyWeekend,
                'is_overtime'         => $anyOvertime,
                'payment_status'      => $data['payment_status'],
                'payment_date'        => $paymentDate,
                'date_ranges_data'    => $rangesData,
                'notes'               => $data['notes'] ?? $workLog->notes,
                'audit_notes'         => $data['audit_notes'] ?? $workLog->audit_notes,
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Log kerja berhasil diperbarui.',
                    'data' => $workLog->fresh(['project', 'user'])
                ]);
            }

            return redirect()->back()->with('success', 'Log kerja berhasil diperbarui.');
        }

        $startDate = Carbon::parse($data['work_date']);
        $endDate = !empty($data['end_date']) ? Carbon::parse($data['end_date']) : $startDate;
        $daysCount = (int)($data['days_count'] ?? ($startDate->diffInDays($endDate) + 1));
        
        $hoursPerDay = isset($data['hours_per_day']) 
            ? (float)$data['hours_per_day'] 
            : ($workLog->hours_per_day ?? 8.0);

        if (isset($data['hours_per_day'])) {
            $totalHours = round($daysCount * $hoursPerDay, 2);
        } elseif (isset($data['hours_worked'])) {
            $totalHours = (float)$data['hours_worked'];
            if ($daysCount > 0 && !isset($data['hours_per_day'])) {
                $hoursPerDay = round($totalHours / $daysCount, 2);
            }
        } else {
            $totalHours = round($daysCount * $hoursPerDay, 2);
        }

        $baseRate = isset($data['base_rate']) && $data['base_rate'] > 0 ? (float)$data['base_rate'] : (float)$workLog->base_rate;
        $resolvedRole = $data['role_in_project'] ?? $workLog->role_in_project;

        if ($baseRate <= 0) {
            $calc = $this->calculator->calculate(
                user: (int)$data['user_id'],
                project: (int)$data['project_id'],
                workDate: $startDate->toDateString(),
                hoursWorked: $totalHours,
                roleSlug: $resolvedRole,
                skillLevel: $data['skill_level'] ?? 'middle'
            );
            $baseRate = (float)$calc['base_rate'];
            if (empty($resolvedRole) && !empty($calc['resolved_role'])) {
                $resolvedRole = $calc['resolved_role'];
            }
        }

        $isWeekend = array_key_exists('is_weekend', $data) ? (bool)$data['is_weekend'] : (bool)$workLog->is_weekend;
        $isOvertime = array_key_exists('is_overtime', $data) ? (bool)$data['is_overtime'] : (bool)$workLog->is_overtime;

        $weekendMultiplier = $isWeekend ? 1.10 : 1.0;
        $overtimeMultiplier = $isOvertime ? 1.20 : 1.0;
        $calculatedNominal = round($totalHours * $baseRate * $weekendMultiplier * $overtimeMultiplier);

        $paymentDate = $workLog->payment_date;
        if ($data['payment_status'] === 'paid') {
            $paymentDate = !empty($data['payment_date']) ? $data['payment_date'] : ($workLog->payment_date ? $workLog->payment_date->toDateString() : Carbon::today()->toDateString());
        } elseif ($data['payment_status'] === 'unpaid') {
            $paymentDate = null;
        }

        $singleRangeData = [
            [
                'start_date'    => $startDate->toDateString(),
                'end_date'      => $endDate->toDateString(),
                'days_count'    => $daysCount,
                'hours_per_day' => $hoursPerDay,
                'hours_worked'  => $totalHours,
                'is_weekend'    => $isWeekend,
                'is_overtime'   => $isOvertime,
                'nominal'       => $calculatedNominal,
            ]
        ];

        $workLog->update([
            'project_id'          => (int)$data['project_id'],
            'user_id'             => (int)$data['user_id'],
            'work_date'           => $startDate->toDateString(),
            'end_date'            => $endDate->toDateString(),
            'days_count'          => $daysCount,
            'hours_per_day'       => $hoursPerDay,
            'hours_worked'        => $totalHours,
            'role_in_project'     => $resolvedRole,
            'source_account_type' => $data['source_account_type'],
            'base_rate'           => $baseRate,
            'calculated_nominal'  => $calculatedNominal,
            'is_weekend'          => $isWeekend,
            'is_overtime'         => $isOvertime,
            'payment_status'      => $data['payment_status'],
            'payment_date'        => $paymentDate,
            'date_ranges_data'    => $singleRangeData,
            'notes'               => $data['notes'] ?? $workLog->notes,
            'audit_notes'         => $data['audit_notes'] ?? $workLog->audit_notes,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Log kerja berhasil diperbarui.',
                'data' => $workLog->fresh(['project', 'user'])
            ]);
        }

        return redirect()->back()->with('success', 'Log kerja berhasil diperbarui.');
    }

    /**
     * Delete a work log entry.
     */
    public function destroy(ProjectWorkLog $workLog): RedirectResponse|JsonResponse
    {
        $workLog->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Log kerja berhasil dihapus.'
            ]);
        }

        return redirect()->back()->with('success', 'Log kerja berhasil dihapus.');
    }

    /**
     * Batch update payment status of multiple work logs.
     */
    public function batchUpdateStatus(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'log_ids' => 'required|array|min:1',
            'log_ids.*' => 'exists:project_work_logs,id',
            'payment_status' => 'required|in:unpaid,pending_approval,paid',
            'payment_date' => 'nullable|date',
            'audit_notes' => 'nullable|string|max:1000',
        ]);

        $updatePayload = [
            'payment_status' => $request->payment_status,
        ];

        if ($request->payment_status === 'paid') {
            $updatePayload['payment_date'] = $request->payment_date ?: Carbon::today()->toDateString();
        } elseif ($request->payment_status === 'unpaid') {
            $updatePayload['payment_date'] = null;
        }

        if ($request->filled('audit_notes')) {
            $updatePayload['audit_notes'] = $request->audit_notes;
        }

        ProjectWorkLog::whereIn('id', $request->log_ids)->update($updatePayload);

        $count = count($request->log_ids);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => "Berhasil memperbarui status {$count} log kerja."
            ]);
        }

        return redirect()->back()->with('success', "Status {$count} log kerja berhasil diperbarui ke " . strtoupper($request->payment_status) . '.');
    }

    /**
     * View and manage Master Rate Cards and Project-Scoped Member Rates.
     */
    public function ratesIndex(): View
    {
        $masterRates = ManHourRate::orderBy('role_slug')->orderBy('skill_level')->get();
        $memberRates = ProjectMemberRate::with(['project', 'user'])->latest()->get();
        $projects = Project::orderBy('title')->get(['id', 'title', 'project_code']);
        $users = User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'role']);

        return view('finance.work-logs.rates', compact('masterRates', 'memberRates', 'projects', 'users'));
    }

    /**
     * Store master rate card.
     */
    public function storeMasterRate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role_slug' => 'required|string|max:100',
            'skill_level' => 'required|string|max:100',
            'base_rate_per_hour' => 'required|numeric|min:0',
            'weekend_multiplier' => 'required|numeric|min:1.0',
            'overtime_multiplier' => 'required|numeric|min:1.0',
            'description' => 'nullable|string|max:500',
        ]);

        ManHourRate::updateOrCreate(
            ['role_slug' => $validated['role_slug'], 'skill_level' => $validated['skill_level']],
            $validated
        );

        return redirect()->back()->with('success', 'Master Rate Card berhasil disimpan.');
    }

    /**
     * Update master rate card.
     */
    public function updateMasterRate(Request $request, ManHourRate $rate): RedirectResponse
    {
        $validated = $request->validate([
            'role_slug' => 'nullable|string|max:100',
            'skill_level' => 'nullable|string|max:100',
            'base_rate_per_hour' => 'required|numeric|min:0',
            'weekend_multiplier' => 'required|numeric|min:1.0',
            'overtime_multiplier' => 'required|numeric|min:1.0',
            'description' => 'nullable|string|max:500',
        ]);

        $rate->update($validated);

        return redirect()->back()->with('success', 'Master Rate Card berhasil diperbarui.');
    }

    /**
     * Delete master rate card.
     */
    public function destroyMasterRate(ManHourRate $rate): RedirectResponse
    {
        $rate->delete();
        return redirect()->back()->with('success', 'Rate Card berhasil dihapus.');
    }

    /**
     * Store custom project member rate override.
     */
    public function storeMemberRate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'user_id' => 'required|exists:users,id',
            'role_slug' => 'nullable|string|max:50',
            'rate_per_hour' => 'required|numeric|min:0',
            'weekend_multiplier' => 'nullable|numeric|min:1.0|max:5.0',
            'overtime_multiplier' => 'nullable|numeric|min:1.0|max:5.0',
            'notes' => 'nullable|string|max:500',
        ]);

        ProjectMemberRate::updateOrCreate(
            ['project_id' => $validated['project_id'], 'user_id' => $validated['user_id']],
            $validated
        );

        return redirect()->back()->with('success', 'Rate khusus anggota proyek berhasil disimpan.');
    }

    /**
     * Update custom project member rate override.
     */
    public function updateMemberRate(Request $request, ProjectMemberRate $memberRate): RedirectResponse
    {
        $validated = $request->validate([
            'rate_per_hour' => 'required|numeric|min:0',
            'weekend_multiplier' => 'nullable|numeric|min:1.0|max:5.0',
            'overtime_multiplier' => 'nullable|numeric|min:1.0|max:5.0',
            'notes' => 'nullable|string|max:500',
        ]);

        $memberRate->update($validated);

        return redirect()->back()->with('success', 'Rate khusus anggota proyek berhasil diperbarui.');
    }

    /**
     * Delete custom project member rate override.
     */
    public function destroyMemberRate(ProjectMemberRate $memberRate): RedirectResponse
    {
        $memberRate->delete();
        return redirect()->back()->with('success', 'Rate khusus anggota proyek berhasil dihapus.');
    }
}
