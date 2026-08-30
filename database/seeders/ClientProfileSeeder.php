<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ClientProfile;
use Illuminate\Database\Seeder;

class ClientProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Klien: Kopi Titik Temu (F&B / Coffee Chain)
        $client1 = Client::firstOrCreate(
            ['name' => 'Kopi Titik Temu'],
            [
                'company_name' => 'PT Titik Temu Nusantara',
                'email' => 'halo@kopititiktemu.id',
                'phone' => '081234567891',
                'address' => 'Jl. Merdeka No. 45, Banyuwangi'
            ]
        );

        $article1 = <<<'HTML'
<h3>Perjalanan Dari Kedai Kecil Menuju Jaringan Roastery Modern</h3>
<p>Kopi Titik Temu didirikan pada tahun 2021 dengan visi sederhana: menyajikan kopi artisan lokal berkualitas tinggi dalam suasana ruang temu yang hangat dan inklusif. Dimulai dari satu kedai di pusat kota, Titik Temu kini telah berkembang menjadi destinasi favorit komunitas kreatif, pekerja jarak jauh (remote workers), serta para pecinta kopi single-origin.</p>

<h4>Filosofi dan Komitmen Kualitas</h4>
<p>Setiap cangkir kopi yang disajikan melalui proses kurasi ketat. Biji kopi dipetik langsung dari petani lokal di lereng Gunung Ijen dan pegunungan Nusantara, kemudian di-roasting dengan standar profil rasa internasional oleh head roaster bersertifikasi Q-Grader.</p>

<blockquote>"Bagi kami, kedai kopi bukan sekadar tempat membeli minuman, melainkan titik temu di mana ide-ide besar dan kolaborasi bermula."</blockquote>

<h4>Tantangan Ekspansi & Transformasi Digital</h4>
<p>Seiring bertambahnya cabang ke-4, manajemen menghadapi kendala klasik industri F&amp;B: diskrepansi stok bahan baku susu dan sirup, variasi rasa resep antar barista, serta lambatnya rekonsiliasi data penjualan harian dari masing-masing outlet. Bersama tim <strong>Wirodev</strong>, kami merancang ekosistem POS dan inventory cloud yang menyatukan alur kasir, resep BOM otomatis, dan notifikasi omset harian langsung ke WhatsApp manajemen.</p>
HTML;

        ClientProfile::updateOrCreate(
            ['slug' => 'kopi-titik-temu'],
            [
                'client_id' => $client1->id,
                'name' => 'Kopi Titik Temu',
                'slug' => 'kopi-titik-temu',
                'category' => 'Food & Beverage',
                'logo' => 'clients/logos/titik-temu.png',
                'cover_image' => 'clients/covers/titik-temu-cover.jpg',
                'description' => 'Kopi Titik Temu adalah coffee roastery & cafe modern yang menyajikan kopi lokal berkualitas tinggi dengan konsep ruang temu kreatif bagi para profesional muda dan komunitas kreatif.',
                'article_content' => $article1,
                'website_url' => 'https://kopititiktemu.id',
                'social_links' => [
                    'instagram' => 'https://instagram.com/kopititiktemu',
                    'facebook' => 'https://facebook.com/kopititiktemu',
                    'tiktok' => 'https://tiktok.com/@kopititiktemu'
                ],
                'location_maps' => 'https://maps.google.com/?q=Banyuwangi',
                'project_title' => 'Integrated Multi-Outlet POS & Real-Time Inventory System',
                'problem_statement' => 'Titik Temu mengalami selisih stok bahan baku kopi dan sirup antar cabang setiap akhir bulan, serta kesulitan memantau laporan penjualan harian dari 4 outlet berbeda secara terpusat.',
                'solution_provided' => 'Wirodev membangun sistem Point of Sale (POS) cloud berbasis web & tablet yang terintegrasi langsung dengan manajemen stok sentral, resep otomatis (BOM/Bill of Materials), dan dashboard omset real-time untuk owner.',
                'features_built' => [
                    'Sistem Kasir (POS) Offline-First dengan sinkronisasi otomatis',
                    'Manajemen Bahan Baku & Resep Otomatis (Deduction per cup)',
                    'Multi-Outlet Live Analytics & Notifikasi WhatsApp untuk laporan harian',
                    'Program Loyalty Pelanggan & QR Code Membership'
                ],
                'tech_stack' => ['Laravel 11', 'Vue.js 3', 'Tailwind CSS', 'MySQL', 'Redis', 'WebSockets'],
                'gallery_images' => [
                    'clients/galleries/pos-preview-1.jpg',
                    'clients/galleries/pos-preview-2.jpg'
                ],
                'testimonial_quote' => 'Semenjak menggunakan sistem POS kustom dari Wirodev, selisih stok kami turun hingga 0% dan saya bisa memantau penjualan seluruh cabang langsung dari smartphone kapan saja.',
                'client_person_name' => 'Rendra Pratama',
                'client_role' => 'Founder & Managing Director',
                'is_published' => true,
                'is_featured' => true,
            ]
        );

        // 2. Klien: Logistik Nusantara Express (Logistics & Supply Chain)
        $client2 = Client::firstOrCreate(
            ['name' => 'Nusantara Cargo & Logistics'],
            [
                'company_name' => 'CV Nusantara Express Mandiri',
                'email' => 'support@nusantaracargo.co.id',
                'phone' => '081298765432',
                'address' => 'Kawasan Industri Ketapang, Banyuwangi'
            ]
        );

        $article2 = <<<'HTML'
<h3>Jembatan Logistik Andal Antara Jawa, Bali, dan Nusa Tenggara</h3>
<p>Nusantara Cargo telah melayani lebih dari 15.000 rute pengiriman logistik B2B dan ekspedisi kargo antarpulau. Memanfaatkan posisi strategis di koridor Pelabuhan Ketapang - Gilimanuk, Nusantara Cargo menjadi tulang punggung rantai pasok bagi distributor consumer goods, bahan konstruksi, dan komoditas UMKM.</p>

<h4>Keunggulan Armada & Standar Operasional</h4>
<p>Dengan armada truk fuso, wingbox, dan jaringan pergudangan transit di 6 titik kabupaten, kami menjamin ketepatan waktu pengiriman dan keamanan muatan. Seluruh penanganan muatan mengacu pada Standard Operating Procedure (SOP) pergudangan modern bersertifikasi.</p>

<h4>Modernisasi Alur Manifest Bersama Wirodev</h4>
<p>Sebelum digitalisasi, tim operasional mencatat surat jalan secara manual di kertas karbon, yang memicu keterlambatan rekonsiliasi resi. Bersama <strong>Wirodev</strong>, kami mentransformasikan seluruh alur pengiriman menjadi paperless dengan sistem ERP Manifest terintegrasi barcode scanner dan portal tracking resi mandiri untuk pelanggan.</p>
HTML;

        ClientProfile::updateOrCreate(
            ['slug' => 'nusantara-cargo'],
            [
                'client_id' => $client2->id,
                'name' => 'Nusantara Cargo',
                'slug' => 'nusantara-cargo',
                'category' => 'Logistik & Distribusi',
                'logo' => 'clients/logos/nusantara-cargo.png',
                'cover_image' => 'clients/covers/nusantara-cargo-cover.jpg',
                'description' => 'Perusahaan jasa pengiriman kargo darat dan laut antar-pulau terpercaya yang melayani rute Jawa - Bali - Nusa Tenggara dengan armada modern dan pergudangan terstandarisasi.',
                'article_content' => $article2,
                'website_url' => 'https://nusantaracargo.co.id',
                'social_links' => [
                    'linkedin' => 'https://linkedin.com/company/nusantara-cargo',
                    'instagram' => 'https://instagram.com/nusantaracargo'
                ],
                'location_maps' => 'https://maps.google.com/?q=Pelabuhan+Ketapang',
                'project_title' => 'End-to-End Fleet Tracking & Manifest Management ERP',
                'problem_statement' => 'Proses pembuatan resi pengiriman kargo masih manual menggunakan kertas, rentan salah sortir barang di gudang transit, dan pelanggan sering menanyakan status pengiriman via telepon berulang kali.',
                'solution_provided' => 'Wirodev merancang ERP Logistik lengkap dengan sistem tracking barcode QR, modul penugasan kurir & armada truk, serta portal publik untuk lacak resi mandiri oleh pelanggan.',
                'features_built' => [
                    'Penerbitan Resi Digital & Cetak Label Termal Otomatis',
                    'Public Tracking Portal dengan estimasi waktu tiba (ETA)',
                    'Manajemen Muatan Truk (Manifest Consolidation)',
                    'Integrasi WhatsApp Gateway untuk notifikasi status pengiriman ke penerima'
                ],
                'tech_stack' => ['Laravel 11', 'Livewire 3', 'Alpine.js', 'PostgreSQL', 'Docker'],
                'gallery_images' => [
                    'clients/galleries/cargo-preview-1.jpg'
                ],
                'testimonial_quote' => 'Efisiensi tim admin gudang meningkat lebih dari 60%. Customer kami sangat puas karena bisa melacak paket kargo mereka secara transparan setiap detik.',
                'client_person_name' => 'Hendra Wijaya',
                'client_role' => 'Operational Head',
                'is_published' => true,
                'is_featured' => true,
            ]
        );

        // 3. Klien: Medika Prima Farma (Healthcare & Pharmacy Retail)
        $client3 = Client::firstOrCreate(
            ['name' => 'Medika Prima Farma'],
            [
                'company_name' => 'PT Medika Prima Sehat',
                'email' => 'info@medikaprima.com',
                'phone' => '081345678900',
                'address' => 'Jl. Ahmad Yani No. 12, Banyuwangi'
            ]
        );

        $article3 = <<<'HTML'
<h3>Layanan Kefarmasian Tepercaya untuk Kesehatan Masyarakat</h3>
<p>Medika Prima Farma berdedikasi menyediakan akses obat-obatan resep dokter, suplemen kesehatan berstandar BPOM, serta alat kesehatan esensial dengan harga transparan dan terjangkau bagi seluruh lapisan masyarakat.</p>

<h4>Pelayanan Ramah & Apoteker Tersertifikasi</h4>
<p>Setiap gerai kami dipimpin oleh apoteker resmi bersertifikat STRA yang siap memberikan sesi konseling obat, skrining interaksi zat aktif, serta edukasi gaya hidup sehat secara cuma-cuma.</p>

<h4>Otomasi Manajemen Stok FEFO dan Resep Dokter</h4>
<p>Mengelola ribuan SKU obat dengan masa kedaluwarsa yang bervariasi membutuhkan akurasi tingkat tinggi. Melalui sistem farmasi pintar dari <strong>Wirodev</strong>, apotek kami kini menerapkan metode FEFO (First Expired First Out) secara otomatis dengan alert sistem jika ada batch obat yang mendekati 90 hari masa expired.</p>
HTML;

        ClientProfile::updateOrCreate(
            ['slug' => 'medika-prima-farma'],
            [
                'client_id' => $client3->id,
                'name' => 'Medika Prima Farma',
                'slug' => 'medika-prima-farma',
                'category' => 'Kesehatan & Farmasi',
                'logo' => 'clients/logos/medika-prima.png',
                'cover_image' => 'clients/covers/medika-prima-cover.jpg',
                'description' => 'Jaringan apotek modern dan klinik pratama yang berkomitmen menyediakan obat-obatan lengkap bersertifikasi BPOM, alat kesehatan, dan konsultasi apoteker profesional.',
                'article_content' => $article3,
                'website_url' => 'https://medikaprima.com',
                'social_links' => [
                    'instagram' => 'https://instagram.com/medikaprima.farma',
                    'facebook' => 'https://facebook.com/medikaprimafarma'
                ],
                'location_maps' => 'https://maps.google.com/?q=Banyuwangi+Kota',
                'project_title' => 'Smart Pharmacy & Batch Expiry Tracking System',
                'problem_statement' => 'Risiko kerugian obat kedaluwarsa karena tidak terdeteksinya tanggal expired secara dini dan pencatatan obat resep dokter yang masih terpisah dengan data stok apotek.',
                'solution_provided' => 'Wirodev membangun sistem manajemen apotek pintar dengan fitur FEFO (First Expired First Out), peringatan dini masa kedaluwarsa, dan pencatatan resep digital yang patuh regulasi.',
                'features_built' => [
                    'Sistem Penjualan & Kasir Apotek terstandarisasi BPOM',
                    'Peringatan Dini Kadaluwarsa Obat (H-90, H-60, H-30 hari)',
                    'Manajemen Resep Dokter & Riwayat Pembelian Pasien',
                    'Laporan Narkotika & Psikotropika Otomatis'
                ],
                'tech_stack' => ['Laravel 11', 'Tailwind CSS', 'MySQL', 'Chart.js'],
                'gallery_images' => [],
                'testimonial_quote' => 'Wirodev sangat memahami kebutuhan regulasi farmasi kami. Sistem yang dibuat sangat mudah digunakan oleh apoteker dan kasir kami.',
                'client_person_name' => 'apt. Dian Sasmita, S.Farm',
                'client_role' => 'Apoteker Penanggung Jawab',
                'is_published' => true,
                'is_featured' => true,
            ]
        );
    }
}
