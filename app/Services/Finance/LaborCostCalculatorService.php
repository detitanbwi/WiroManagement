<?php

namespace App\Services\Finance;

use App\Models\ManHourRate;
use App\Models\Project;
use App\Models\ProjectMemberRate;
use App\Models\ProjectWorkLog;
use App\Models\User;
use Carbon\Carbon;

class LaborCostCalculatorService
{
    /**
     * Default fallback hourly rate (IDR) if no specific rate card is set.
     */
    public const DEFAULT_BASE_RATE = 50000.00;
    public const DEFAULT_WEEKEND_MULTIPLIER = 1.10;
    public const DEFAULT_OVERTIME_MULTIPLIER = 1.20;
    public const OVERTIME_HOUR_THRESHOLD = 2.0;

    /**
     * Calculate labor cost parameters for a work log entry.
     *
     * @param User|int $user
     * @param Project|int $project
     * @param Carbon|string $workDate
     * @param float $hoursWorked
     * @param string|null $roleSlug
     * @param string|null $skillLevel
     * @param bool|null $forceWeekend
     * @param bool|null $forceOvertime
     * @param float|null $customBaseRate
     * @return array
     */
    public function calculate(
        User|int $user,
        Project|int $project,
        Carbon|string $workDate,
        float $hoursWorked,
        ?string $roleSlug = null,
        ?string $skillLevel = 'middle',
        ?bool $forceWeekend = null,
        ?bool $forceOvertime = null,
        ?float $customBaseRate = null
    ): array {
        $userId = $user instanceof User ? $user->id : (int)$user;
        $projectId = $project instanceof Project ? $project->id : (int)$project;
        $date = $workDate instanceof Carbon ? $workDate->copy() : Carbon::parse($workDate);

        // 1. Weekend detection
        $isWeekend = $forceWeekend !== null ? (bool)$forceWeekend : $date->isWeekend();

        // 2. Overtime / duration threshold detection (> 2 hours/day continuous bonus)
        if ($forceOvertime !== null) {
            $isOvertime = (bool)$forceOvertime;
        } else {
            // Check if single session > threshold OR cumulative hours for user on this date > threshold
            $cumulativeHoursOnDate = (float)ProjectWorkLog::where('user_id', $userId)
                ->where('work_date', $date->toDateString())
                ->sum('hours_worked');

            $isOvertime = ($hoursWorked > self::OVERTIME_HOUR_THRESHOLD)
                || (($cumulativeHoursOnDate + $hoursWorked) > self::OVERTIME_HOUR_THRESHOLD);
        }

        // 3. Resolve Rate Card & Multipliers
        $baseRate = null;
        $weekendMultiplier = self::DEFAULT_WEEKEND_MULTIPLIER;
        $overtimeMultiplier = self::DEFAULT_OVERTIME_MULTIPLIER;

        // Custom override per project member
        $memberOverride = ProjectMemberRate::where('project_id', $projectId)
            ->where('user_id', $userId)
            ->first();

        if (!$roleSlug) {
            $roleSlug = $memberOverride?->role_slug 
                ?? \App\Models\ProjectMember::where('project_id', $projectId)->where('user_id', $userId)->value('role_in_project');
        }

        if ($customBaseRate !== null && $customBaseRate > 0) {
            $baseRate = (float)$customBaseRate;
            if ($memberOverride) {
                $weekendMultiplier = (float)($memberOverride->weekend_multiplier ?: self::DEFAULT_WEEKEND_MULTIPLIER);
                $overtimeMultiplier = (float)($memberOverride->overtime_multiplier ?: self::DEFAULT_OVERTIME_MULTIPLIER);
            } else {
                $level = $skillLevel ?: 'middle';
                $masterRate = null;

                if ($roleSlug) {
                    $slugVariants = [
                        $roleSlug,
                        strtolower($roleSlug),
                        str_replace(' ', '_', strtolower($roleSlug)),
                        str_replace('_', ' ', strtolower($roleSlug))
                    ];

                    $masterRate = ManHourRate::whereIn('role_slug', $slugVariants)
                        ->where(function ($q) use ($level) {
                            $q->where('skill_level', $level)
                              ->orWhere('skill_level', strtolower($level));
                        })
                        ->first();

                    if (!$masterRate) {
                        $masterRate = ManHourRate::whereIn('role_slug', $slugVariants)->first();
                    }
                }

                if ($masterRate) {
                    $weekendMultiplier = (float)($masterRate->weekend_multiplier ?: self::DEFAULT_WEEKEND_MULTIPLIER);
                    $overtimeMultiplier = (float)($masterRate->overtime_multiplier ?: self::DEFAULT_OVERTIME_MULTIPLIER);
                }
            }
        } elseif ($memberOverride) {
            $baseRate = (float)$memberOverride->rate_per_hour;
            $weekendMultiplier = (float)($memberOverride->weekend_multiplier ?: self::DEFAULT_WEEKEND_MULTIPLIER);
            $overtimeMultiplier = (float)($memberOverride->overtime_multiplier ?: self::DEFAULT_OVERTIME_MULTIPLIER);
        } else {
            // Query master rate card (reference only)
            $level = $skillLevel ?: 'middle';
            $masterRate = null;

            if ($roleSlug) {
                $slugVariants = [
                    $roleSlug,
                    strtolower($roleSlug),
                    str_replace(' ', '_', strtolower($roleSlug)),
                    str_replace('_', ' ', strtolower($roleSlug))
                ];

                $masterRate = ManHourRate::whereIn('role_slug', $slugVariants)
                    ->where(function ($q) use ($level) {
                        $q->where('skill_level', $level)
                          ->orWhere('skill_level', strtolower($level));
                    })
                    ->first();

                if (!$masterRate) {
                    $masterRate = ManHourRate::whereIn('role_slug', $slugVariants)->first();
                }
            }

            if ($masterRate) {
                $baseRate = (float)$masterRate->base_rate_per_hour;
                $weekendMultiplier = (float)($masterRate->weekend_multiplier ?: self::DEFAULT_WEEKEND_MULTIPLIER);
                $overtimeMultiplier = (float)($masterRate->overtime_multiplier ?: self::DEFAULT_OVERTIME_MULTIPLIER);
            } else {
                $baseRate = self::DEFAULT_BASE_RATE;
            }
        }

        // 4. Calculate Final Nominal
        $effectiveMultiplier = 1.0;
        if ($isWeekend) {
            $effectiveMultiplier *= $weekendMultiplier;
        }
        if ($isOvertime) {
            $effectiveMultiplier *= $overtimeMultiplier;
        }

        $effectiveRate = round($baseRate * $effectiveMultiplier, 2);
        $calculatedNominal = round($hoursWorked * $effectiveRate, 2);

        return [
            'role_slug' => $roleSlug,
            'resolved_role' => $roleSlug,
            'base_rate' => round($baseRate, 2),
            'is_weekend' => $isWeekend,
            'is_overtime' => $isOvertime,
            'weekend_multiplier' => round($weekendMultiplier, 2),
            'overtime_multiplier' => round($overtimeMultiplier, 2),
            'effective_rate' => $effectiveRate,
            'effective_multiplier' => round($effectiveMultiplier, 2),
            'hours_worked' => round($hoursWorked, 2),
            'calculated_nominal' => $calculatedNominal,
        ];
    }
}
