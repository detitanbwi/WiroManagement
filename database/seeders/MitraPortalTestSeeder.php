<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectUpdate;
use App\Models\User;
use App\Models\ClientInvitation;
use App\Models\Invoice;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class MitraPortalTestSeeder extends Seeder
{
    public function run(): void
    {
        // 1. PM User
        $pm = User::updateOrCreate(
            ['email' => 'pm@wirodev.com'],
            [
                'name' => 'Aditya Pratama (Project Manager)',
                'password' => Hash::make('password123'),
                'role' => 'pm',
                'is_active' => true,
            ]
        );

        // 2. Klien PT Sinergi Digital Indonesia
        $client = Client::updateOrCreate(
            ['email' => 'budi@sinergidigital.com'],
            [
                'name' => 'Budi Santoso',
                'company_name' => 'PT Sinergi Digital Indonesia',
                'phone' => '081234567890',
                'address' => 'Jl. Jenderal Sudirman No. 45, Jakarta Selatan',
            ]
        );

        // 3. User Akun Portal Klien
        $clientUser = User::updateOrCreate(
            ['email' => 'budi@sinergidigital.com'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('password123'),
                'role' => 'client',
                'client_id' => $client->id,
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        // 4. Token Undangan untuk simulasi /set-password
        $testRawToken = 'test-onboarding-token-123456789abcdef';
        ClientInvitation::updateOrCreate(
            ['client_id' => $client->id, 'email' => $client->email],
            [
                'token_hash' => hash('sha256', $testRawToken),
                'expires_at' => Carbon::now()->addDays(7),
                'accepted_at' => null,
            ]
        );

        // 5. Project 1: ERP Inventory & Warehouse
        $project1 = Project::updateOrCreate(
            ['project_code' => 'PRJ-2026-001'],
            [
                'client_id' => $client->id,
                'pm_id' => $pm->id,
                'title' => 'Sistem ERP Inventory & Warehouse Management',
                'status' => 'in_progress',
                'progress_percentage' => 75,
                'start_date' => Carbon::now()->subMonths(2),
                'end_date' => Carbon::now()->addMonth(),
            ]
        );

        // Milestones Project 1
        ProjectMilestone::updateOrCreate(
            ['project_id' => $project1->id, 'order_index' => 1],
            [
                'title' => 'Analisis Kebutuhan & Desain Arsitektur Database',
                'description' => 'Finalisasi ERD, data dictionary, dan spesifikasi API.',
                'status' => 'completed',
                'due_date' => Carbon::now()->subMonths(1),
            ]
        );

        ProjectMilestone::updateOrCreate(
            ['project_id' => $project1->id, 'order_index' => 2],
            [
                'title' => 'Pengembangan Core Inventory & Manajemen Gudang',
                'description' => 'Modul stock in/out, transfer gudang, dan approval flow.',
                'status' => 'completed',
                'due_date' => Carbon::now()->subDays(10),
            ]
        );

        ProjectMilestone::updateOrCreate(
            ['project_id' => $project1->id, 'order_index' => 3],
            [
                'title' => 'Integrasi Barcode Scanner & Real-Time Sync',
                'description' => 'Implementasi hardware scanner handheld dan sync WebSocket.',
                'status' => 'in_progress',
                'due_date' => Carbon::now()->addDays(14),
            ]
        );

        ProjectMilestone::updateOrCreate(
            ['project_id' => $project1->id, 'order_index' => 4],
            [
                'title' => 'User Acceptance Testing (UAT) & Training Karyawan',
                'description' => 'Pengujian end-to-end oleh user PT Sinergi Digital.',
                'status' => 'pending',
                'due_date' => Carbon::now()->addMonth(),
            ]
        );

        // Project Updates Project 1
        ProjectUpdate::updateOrCreate(
            ['project_id' => $project1->id, 'title' => 'Integrasi Modul Scanner Selesai 80%'],
            [
                'author_id' => $pm->id,
                'content' => 'Tim telah menyelesaikan integrasi scanner untuk gudang utama di Cikarang. Saat ini sedang pengujian latensi sync data.',
                'is_visible_to_client' => true,
            ]
        );

        // 6. Project 2: Mobile App Mitra Kurir & Tracking
        $project2 = Project::updateOrCreate(
            ['project_code' => 'PRJ-2026-002'],
            [
                'client_id' => $client->id,
                'pm_id' => $pm->id,
                'title' => 'Mobile App Mitra Kurir & Real-time GPS Tracking',
                'status' => 'in_progress',
                'progress_percentage' => 40,
                'start_date' => Carbon::now()->subMonth(),
                'end_date' => Carbon::now()->addMonths(2),
            ]
        );

        // Milestones Project 2
        ProjectMilestone::updateOrCreate(
            ['project_id' => $project2->id, 'order_index' => 1],
            [
                'title' => 'UI/UX Design & Prototype Mobile App',
                'description' => 'Desain interaktif Figma disetujui oleh direksi.',
                'status' => 'completed',
                'due_date' => Carbon::now()->subDays(15),
            ]
        );

        ProjectMilestone::updateOrCreate(
            ['project_id' => $project2->id, 'order_index' => 2],
            [
                'title' => 'Background Geolocation & Webhook Handler',
                'description' => 'Tracking kurir hemat baterai dengan geofencing.',
                'status' => 'in_progress',
                'due_date' => Carbon::now()->addDays(20),
            ]
        );

        ProjectMilestone::updateOrCreate(
            ['project_id' => $project2->id, 'order_index' => 3],
            [
                'title' => 'Release Beta Testing di Google Play Internal Track',
                'description' => 'Distribusi file APK ke pilot kurir.',
                'status' => 'pending',
                'due_date' => Carbon::now()->addMonths(2),
            ]
        );

        // Invoices Project 1 & 2
        Invoice::updateOrCreate(
            ['invoice_number' => 'INV-2026-03-001'],
            [
                'project_id' => $project1->id,
                'type' => 'initial',
                'subtotal' => 25000000,
                'tax' => 2750000,
                'total_amount' => 27750000,
                'due_date' => Carbon::now()->subDays(20),
                'issued_date' => Carbon::now()->subDays(30),
                'status' => 'paid',
            ]
        );

        Invoice::updateOrCreate(
            ['invoice_number' => 'INV-2026-04-002'],
            [
                'project_id' => $project1->id,
                'type' => 'final',
                'subtotal' => 25000000,
                'tax' => 2750000,
                'total_amount' => 27750000,
                'due_date' => Carbon::now()->addDays(15),
                'issued_date' => Carbon::now(),
                'status' => 'issued',
            ]
        );
    }
}
