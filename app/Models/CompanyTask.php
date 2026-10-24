<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CompanyTask extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'due_date' => 'date:Y-m-d',
        'meeting_time' => 'datetime',
        'sort_order' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_task_user')->withTimestamps();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Generate unique sequential code (e.g. INT-001, INT-002)
     */
    public static function generateNextCode(): string
    {
        $last = static::orderBy('id', 'desc')->first();
        if (!$last) {
            return 'INT-001';
        }

        if (preg_match('/INT-(\d+)/', $last->code, $matches)) {
            $nextNum = intval($matches[1]) + 1;
            return sprintf('INT-%03d', $nextNum);
        }

        return 'INT-' . str_pad($last->id + 1, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Label and styling metadata for types
     */
    public static function typeDefinitions(): array
    {
        return [
            'meeting' => [
                'label' => 'Pertemuan Klien',
                'short' => 'Meeting',
                'icon' => 'calendar',
                'badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                'dot' => 'bg-indigo-500',
            ],
            'proposal' => [
                'label' => 'Proposal & Quotation',
                'short' => 'Proposal',
                'icon' => 'document',
                'badge' => 'bg-amber-50 text-amber-800 border-amber-200',
                'dot' => 'bg-amber-500',
            ],
            'report' => [
                'label' => 'Laporan Akhir Bulan',
                'short' => 'Laporan',
                'icon' => 'chart',
                'badge' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                'dot' => 'bg-emerald-500',
            ],
            'operational' => [
                'label' => 'Operasional & IT',
                'short' => 'Operasional',
                'icon' => 'cog',
                'badge' => 'bg-purple-50 text-purple-700 border-purple-200',
                'dot' => 'bg-purple-500',
            ],
            'general' => [
                'label' => 'Tugas Umum',
                'short' => 'Umum',
                'icon' => 'clipboard',
                'badge' => 'bg-gray-100 text-gray-700 border-gray-200',
                'dot' => 'bg-gray-400',
            ],
        ];
    }

    /**
     * Column / Status Definitions
     */
    public static function statusDefinitions(): array
    {
        return [
            'todo' => [
                'title' => 'To Do / Terjadwal',
                'color' => 'bg-gray-100 text-gray-700',
                'border' => 'border-gray-200',
            ],
            'in_progress' => [
                'title' => 'In Progress',
                'color' => 'bg-blue-50 text-blue-700',
                'border' => 'border-blue-200',
            ],
            'waiting_client' => [
                'title' => 'Menunggu Klien / Review',
                'color' => 'bg-amber-50 text-amber-800',
                'border' => 'border-amber-200',
            ],
            'done' => [
                'title' => 'Selesai',
                'color' => 'bg-emerald-50 text-emerald-700',
                'border' => 'border-emerald-200',
            ],
        ];
    }

    /**
     * Priority Definitions
     */
    public static function priorityDefinitions(): array
    {
        return [
            'urgent' => [
                'label' => 'Urgent',
                'badge' => 'bg-red-50 text-red-700 border-red-200',
                'dot' => 'bg-red-500',
            ],
            'high' => [
                'label' => 'Tinggi',
                'badge' => 'bg-orange-50 text-orange-700 border-orange-200',
                'dot' => 'bg-orange-500',
            ],
            'medium' => [
                'label' => 'Sedang',
                'badge' => 'bg-amber-50 text-amber-700 border-amber-200',
                'dot' => 'bg-amber-500',
            ],
            'low' => [
                'label' => 'Rendah',
                'badge' => 'bg-gray-100 text-gray-600 border-gray-200',
                'dot' => 'bg-gray-400',
            ],
        ];
    }
}
