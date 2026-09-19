<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Project;
use App\Models\Client;
use App\Models\Role;
use App\Models\User;

class ProjectController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $projects = Project::visibleTo($user)
            ->with(['client', 'members.roles'])
            ->latest()
            ->get();

        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        abort_unless(auth()->user()->can('projects.create'), 403, 'Akses ditolak. Anda tidak memiliki izin untuk membuat proyek baru.');
        $clients = Client::all();
        $users = User::whereNull('client_id')->where('is_active', true)->get();
        $roles = Role::where('slug', '!=', 'client')->orderBy('id')->get();
        return view('projects.create', compact('clients', 'users', 'roles'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('projects.create'), 403, 'Akses ditolak. Anda tidak memiliki izin untuk membuat proyek baru.');
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'required|string|max:255',
            'status' => 'required|in:draft,quotation_sent,approved,in_progress,completed,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'members' => 'nullable|array',
            'members.*.user_id' => 'required|exists:users,id',
            'members.*.roles' => 'required|array|min:1',
            'members.*.roles.*' => 'exists:roles,slug',
        ]);

        $project = Project::create([
            'client_id' => $validated['client_id'],
            'title' => $validated['title'],
            'status' => $validated['status'],
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
        ]);

        if (!empty($validated['members'])) {
            foreach ($validated['members'] as $memberData) {
                if (!empty($memberData['user_id']) && !empty($memberData['roles'])) {
                    $project->assignMember($memberData['user_id'], $memberData['roles']);
                }
            }
        }

        return redirect()->route('projects.index')->with('success', 'Proyek berhasil dibuat.');
    }

    public function show(Project $project)
    {
        abort_unless(auth()->user()->can('projects.manage'), 403, 'Akses ditolak. Anda tidak memiliki izin untuk mengelola proyek ini.');
        $project->load(['client', 'quotations', 'invoices.items', 'changeRequests', 'members.user', 'members.roles']);
        $availableUsers = User::whereNull('client_id')->where('is_active', true)->get();
        $assignableRoles = Role::where('slug', '!=', 'client')->orderBy('id')->get();

        return view('projects.show', compact('project', 'availableUsers', 'assignableRoles'));
    }

    public function qc(Project $project)
    {
        $projectMembers = $project->users()->where('is_active', true)->get();
        $users = $projectMembers->isNotEmpty() ? $projectMembers : User::whereNull('client_id')->where('is_active', true)->get();
        return view('projects.qc', compact('project', 'users'));
    }

    public function edit(Project $project)
    {
        abort_unless(auth()->user()->can('projects.edit'), 403, 'Akses ditolak. Anda tidak memiliki izin untuk mengedit proyek ini.');
        $clients = Client::all();
        $users = User::whereNull('client_id')->where('is_active', true)->get();
        $roles = Role::where('slug', '!=', 'client')->orderBy('id')->get();
        $project->load('members.roles');

        return view('projects.edit', compact('project', 'clients', 'users', 'roles'));
    }

    public function update(Request $request, Project $project)
    {
        abort_unless(auth()->user()->can('projects.edit'), 403, 'Akses ditolak. Anda tidak memiliki izin untuk mengedit proyek ini.');
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'required|string|max:255',
            'status' => 'required|in:draft,quotation_sent,approved,in_progress,completed,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
        ]);

        $project->update($validated);

        return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project)
    {
        abort_unless(auth()->user()->can('projects.delete'), 403, 'Akses ditolak. Anda tidak memiliki izin untuk menghapus proyek ini.');
        $hasUnpaidInvoices = $project->invoices()->whereIn('status', ['issued', 'partial'])->exists();

        if ($hasUnpaidInvoices) {
            return redirect()->back()->with('error', 'Proyek tidak dapat dihapus karena masih ada invoice yang belum lunas.');
        }

        $project->delete();
        return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
    }

    public function updateStatus(Request $request, Project $project)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,quotation_sent,approved,in_progress,completed,cancelled'
        ]);

        $project->update($validated);

        return back()->with('success', 'Project status updated successfully.');
    }

    public static function terbilang($angka)
    {
        $angka = abs($angka);
        $baca = array("", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas");
        $terbilang = "";

        if ($angka < 12) {
            $terbilang = " " . $baca[$angka];
        } else if ($angka < 20) {
            $terbilang = self::terbilang($angka - 10) . " Belas";
        } else if ($angka < 100) {
            $terbilang = self::terbilang(floor($angka / 10)) . " Puluh" . self::terbilang($angka % 10);
        } else if ($angka < 200) {
            $terbilang = " Seratus" . self::terbilang($angka - 100);
        } else if ($angka < 1000) {
            $terbilang = self::terbilang(floor($angka / 100)) . " Ratus" . self::terbilang($angka % 100);
        } else if ($angka < 2000) {
            $terbilang = " Seribu" . self::terbilang($angka - 1000);
        } else if ($angka < 1000000) {
            $terbilang = self::terbilang(floor($angka / 1000)) . " Ribu" . self::terbilang($angka % 1000);
        } else if ($angka < 1000000000) {
            $terbilang = self::terbilang(floor($angka / 1000000)) . " Juta" . self::terbilang($angka % 1000000);
        }

        return $terbilang;
    }

    public static function formatTerbilang($angka)
    {
        return trim(self::terbilang($angka));
    }
}
