<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPERADMIN = 'superadmin';
    case ADMIN = 'admin';
    case PM = 'pm';
    case FINANCE = 'finance';
    case QC = 'qc';
    case STAFF = 'staff';
    case CLIENT = 'client';

    public function label(): string
    {
        return match ($this) {
            self::SUPERADMIN => 'Super Admin',
            self::ADMIN => 'Administrator',
            self::PM => 'Project Manager (PM)',
            self::FINANCE => 'Finance & Accounting',
            self::QC => 'Quality Control (QC)',
            self::STAFF => 'Staff / Developer',
            self::CLIENT => 'Client Portal',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SUPERADMIN => 'Akses mutlak ke seluruh sistem, manajemen pengguna, dan pengaturan sistem.',
            self::ADMIN => 'Akses penuh ke manajemen klien, proyek, finance, QA/QC, dan estimasi AI.',
            self::PM => 'Mengelola proyek, milestone, update klien, pengawasan QC, dan koordinasi tim.',
            self::FINANCE => 'Mengelola invoice, quotation, pembayaran, rekening bank, dan pembukuan.',
            self::QC => 'Merancang test case, mengeksekusi pengujian aplikasi, dan melaporkan bug.',
            self::STAFF => 'Mengerjakan task proyek, diskusi teknis, eksekusi QA, dan estimasi AI.',
            self::CLIENT => 'Akses khusus portal klien Wiromitra untuk memantau progres dan invoice proyek sendiri.',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::SUPERADMIN => 'bg-purple-100 text-purple-800 border-purple-200',
            self::ADMIN => 'bg-indigo-100 text-indigo-800 border-indigo-200',
            self::PM => 'bg-blue-100 text-blue-800 border-blue-200',
            self::FINANCE => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            self::QC => 'bg-amber-100 text-amber-800 border-amber-200',
            self::STAFF => 'bg-slate-100 text-slate-800 border-slate-200',
            self::CLIENT => 'bg-teal-100 text-teal-800 border-teal-200',
        };
    }

    public function isInternal(): bool
    {
        return $this !== self::CLIENT;
    }

    /**
     * Role yang dapat dipilih saat membuat atau mengedit user oleh Super Admin.
     */
    public static function availableForManagement(): array
    {
        return [
            self::SUPERADMIN,
            self::ADMIN,
            self::PM,
            self::FINANCE,
            self::QC,
            self::STAFF,
        ];
    }
}
