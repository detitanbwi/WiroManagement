<!DOCTYPE html>
<html lang="id" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>[QA/QC Summary] {{ $metrics['project']['code'] }} - {{ $project->title }}</title>
    <style>
        /* Base Resets */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        
        /* Mobile styles */
        @media screen and (max-width: 600px) {
            .mobile-full-width { width: 100% !important; max-width: 100% !important; }
            .mobile-stack { display: block !important; width: 100% !important; box-sizing: border-box !important; }
            .mobile-padding { padding-left: 16px !important; padding-right: 16px !important; }
            .mobile-metric-col { width: 50% !important; display: inline-block !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 24px 0; background-color: #f1f5f9; color: #1e293b;">

    <!-- Wrapper Table -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td align="center" style="padding: 12px;">
                
                <!-- Main Container (600px) -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                    
                    <!-- Header Section -->
                    <tr>
                        <td style="background-color: #0f172a; padding: 28px 32px 24px 32px; text-align: left;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td>
                                        <div style="font-size: 11px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: #10b981; margin-bottom: 6px;">
                                            WIRO MANAGEMENT &bull; QA/QC BOARD
                                        </div>
                                        <h1 style="margin: 0 0 6px 0; font-size: 20px; font-weight: 800; color: #ffffff; line-height: 1.3;">
                                            {{ $project->title }}
                                        </h1>
                                        <div style="font-size: 13px; color: #94a3b8;">
                                            Project Code: <span style="display: inline-block; background-color: #1e293b; color: #38bdf8; font-weight: 600; padding: 2px 8px; border-radius: 4px; font-family: monospace;">{{ $metrics['project']['code'] }}</span>
                                            &nbsp;&bull;&nbsp;
                                            <span>{{ $metrics['generated_at'] }}</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Personal Greeting & Project Meta Bar -->
                    <tr>
                        <td style="padding: 24px 32px 16px 32px; background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                            <p style="margin: 0 0 12px 0; font-size: 15px; color: #334155; line-height: 1.5;">
                                Halo <strong style="color: #0f172a;">Tim Proyek</strong>,
                            </p>
                            <p style="margin: 0; font-size: 13px; color: #64748b; line-height: 1.5;">
                                Berikut adalah rekapitulasi status penugasan Kanban, hasil pengujian Test Cases, dan pelacak Bug terkini untuk proyek ini.
                            </p>

                            <!-- Mini Meta Info Table -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 14px; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px;">
                                <tr>
                                    <td style="padding: 8px 12px; font-size: 12px; color: #64748b; border-right: 1px solid #f1f5f9; width: 33.3%;">
                                        <div style="font-size: 10px; text-transform: uppercase; color: #94a3b8; font-weight: 700;">Klien</div>
                                        <div style="font-weight: 600; color: #1e293b; margin-top: 2px;">{{ $metrics['project']['client_name'] }}</div>
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 12px; color: #64748b; border-right: 1px solid #f1f5f9; width: 33.3%;">
                                        <div style="font-size: 10px; text-transform: uppercase; color: #94a3b8; font-weight: 700;">Project Manager</div>
                                        <div style="font-weight: 600; color: #1e293b; margin-top: 2px;">{{ $metrics['project']['pm_name'] }}</div>
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 12px; color: #64748b; width: 33.3%;">
                                        <div style="font-size: 10px; text-transform: uppercase; color: #94a3b8; font-weight: 700;">Status Proyek</div>
                                        <div style="font-weight: 600; color: #0284c7; margin-top: 2px;">{{ $metrics['project']['status'] }}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Section 1: Kanban Tasks -->
                    <tr>
                        <td style="padding: 24px 32px 16px 32px;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td style="padding-bottom: 12px;">
                                        <div style="font-size: 14px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">
                                            1. Kanban Tasks Summary
                                            <span style="font-size: 12px; font-weight: 500; color: #64748b; text-transform: none;">(Total: {{ $metrics['kanban']['total'] }} Tasks)</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Kanban 4 Status Cards -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <!-- Todo -->
                                    <td width="25%" class="mobile-metric-col" style="padding: 4px;">
                                        <div style="background-color: #f8fafc; border: 1px solid #cbd5e1; border-top: 3px solid #64748b; border-radius: 8px; padding: 12px 8px; text-align: center;">
                                            <div style="font-size: 20px; font-weight: 800; color: #334155;">{{ $metrics['kanban']['todo'] }}</div>
                                            <div style="font-size: 11px; font-weight: 600; color: #64748b; margin-top: 4px; text-transform: uppercase;">To Do</div>
                                        </div>
                                    </td>
                                    <!-- In Progress -->
                                    <td width="25%" class="mobile-metric-col" style="padding: 4px;">
                                        <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-top: 3px solid #3b82f6; border-radius: 8px; padding: 12px 8px; text-align: center;">
                                            <div style="font-size: 20px; font-weight: 800; color: #1d4ed8;">{{ $metrics['kanban']['in_progress'] }}</div>
                                            <div style="font-size: 11px; font-weight: 600; color: #2563eb; margin-top: 4px; text-transform: uppercase;">In Progress</div>
                                        </div>
                                    </td>
                                    <!-- Ready for QC -->
                                    <td width="25%" class="mobile-metric-col" style="padding: 4px;">
                                        <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-top: 3px solid #f59e0b; border-radius: 8px; padding: 12px 8px; text-align: center;">
                                            <div style="font-size: 20px; font-weight: 800; color: #b45309;">{{ $metrics['kanban']['ready_for_qc'] }}</div>
                                            <div style="font-size: 11px; font-weight: 600; color: #d97706; margin-top: 4px; text-transform: uppercase;">Ready QC</div>
                                        </div>
                                    </td>
                                    <!-- QC In Progress -->
                                    <td width="25%" class="mobile-metric-col" style="padding: 4px;">
                                        <div style="background-color: #f5f3ff; border: 1px solid #ddd6fe; border-top: 3px solid #8b5cf6; border-radius: 8px; padding: 12px 8px; text-align: center;">
                                            <div style="font-size: 20px; font-weight: 800; color: #6d28d9;">{{ $metrics['kanban']['qc_in_progress'] }}</div>
                                            <div style="font-size: 11px; font-weight: 600; color: #7c3aed; margin-top: 4px; text-transform: uppercase;">QC Progress</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            
                            @if($metrics['kanban']['done'] > 0)
                            <div style="margin-top: 8px; font-size: 12px; color: #64748b; text-align: right;">
                                Selesai (Done): <strong style="color: #059669;">{{ $metrics['kanban']['done'] }}</strong> tasks
                            </div>
                            @endif
                        </td>
                    </tr>

                    <!-- Section 2: Test Cases Health -->
                    <tr>
                        <td style="padding: 12px 32px 16px 32px;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td style="padding-bottom: 12px;">
                                        <div style="font-size: 14px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">
                                            2. Test Cases Execution
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <div style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px;">
                                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                    <tr>
                                        <td width="33.3%" style="text-align: center; border-right: 1px solid #f1f5f9;">
                                            <div style="font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase;">Total Test Cases</div>
                                            <div style="font-size: 22px; font-weight: 800; color: #0f172a; margin-top: 4px;">{{ $metrics['test_cases']['total'] }}</div>
                                        </td>
                                        <td width="33.3%" style="text-align: center; border-right: 1px solid #f1f5f9;">
                                            <div style="font-size: 11px; color: #059669; font-weight: 600; text-transform: uppercase;">&#10004; Passed</div>
                                            <div style="font-size: 22px; font-weight: 800; color: #059669; margin-top: 4px;">{{ $metrics['test_cases']['passed'] }}</div>
                                        </td>
                                        <td width="33.3%" style="text-align: center;">
                                            <div style="font-size: 11px; color: #dc2626; font-weight: 600; text-transform: uppercase;">&#10008; Fail / Failed</div>
                                            <div style="font-size: 22px; font-weight: 800; color: #dc2626; margin-top: 4px;">{{ $metrics['test_cases']['failed'] }}</div>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Pass Rate Progress Bar -->
                                <div style="margin-top: 14px; padding-top: 12px; border-top: 1px solid #f1f5f9;">
                                    <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 6px;">
                                        <span style="color: #64748b;">Tingkat Kelulusan (Pass Rate):</span>
                                        <strong style="color: {{ $metrics['test_cases']['pass_rate'] >= 80 ? '#059669' : ($metrics['test_cases']['pass_rate'] >= 50 ? '#d97706' : '#dc2626') }};">
                                            {{ $metrics['test_cases']['pass_rate'] }}%
                                        </strong>
                                    </div>
                                    <div style="width: 100%; height: 8px; background-color: #e2e8f0; border-radius: 4px; overflow: hidden;">
                                        <div style="width: {{ $metrics['test_cases']['pass_rate'] }}%; height: 100%; background-color: {{ $metrics['test_cases']['pass_rate'] >= 80 ? '#10b981' : ($metrics['test_cases']['pass_rate'] >= 50 ? '#f59e0b' : '#ef4444') }};"></div>
                                    </div>
                                    @if($metrics['test_cases']['pending'] > 0)
                                    <div style="font-size: 11px; color: #94a3b8; margin-top: 6px; text-align: right;">
                                        Pending / Belum Diuji: {{ $metrics['test_cases']['pending'] }} test cases
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>

                    <!-- Section 3: Bug Tracker Breakdown -->
                    <tr>
                        <td style="padding: 12px 32px 24px 32px;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td style="padding-bottom: 12px;">
                                        <div style="font-size: 14px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px;">
                                            3. Bug Tracker Breakdown
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #fff1f2; border: 1px solid #fecdd3; border-radius: 8px; overflow: hidden;">
                                <tr>
                                    <td width="33.3%" style="padding: 14px 10px; text-align: center; border-right: 1px solid #fecdd3;">
                                        <div style="font-size: 11px; font-weight: 700; color: #9f1239; text-transform: uppercase;">Bug Aktif</div>
                                        <div style="font-size: 24px; font-weight: 800; color: #be123c; margin-top: 4px;">{{ $metrics['bugs']['active'] }}</div>
                                        <div style="font-size: 10px; color: #e11d48; margin-top: 2px;">(Open + In Progress)</div>
                                    </td>
                                    <td width="33.3%" style="padding: 14px 10px; text-align: center; border-right: 1px solid #fecdd3; background-color: #ffffff;">
                                        <div style="font-size: 11px; font-weight: 700; color: #1e40af; text-transform: uppercase;">Sudah Di-assign</div>
                                        <div style="font-size: 24px; font-weight: 800; color: #2563eb; margin-top: 4px;">{{ $metrics['bugs']['assigned'] }}</div>
                                        <div style="font-size: 10px; color: #64748b; margin-top: 2px;">(Terkait Task)</div>
                                    </td>
                                    <td width="33.3%" style="padding: 14px 10px; text-align: center; background-color: #fffbeb;">
                                        <div style="font-size: 11px; font-weight: 700; color: #92400e; text-transform: uppercase;">Belum Di-assign</div>
                                        <div style="font-size: 24px; font-weight: 800; color: #d97706; margin-top: 4px;">{{ $metrics['bugs']['unassigned'] }}</div>
                                        <div style="font-size: 10px; color: #b45309; margin-top: 2px;">(Perlu Tugas)</div>
                                    </td>
                                </tr>
                            </table>

                            @if($metrics['bugs']['resolved'] > 0 || $metrics['bugs']['closed'] > 0)
                            <div style="margin-top: 8px; font-size: 11px; color: #64748b; text-align: right;">
                                Bug Terselesaikan (Resolved/Closed): <strong style="color: #059669;">{{ $metrics['bugs']['resolved'] + $metrics['bugs']['closed'] }}</strong>
                            </div>
                            @endif
                        </td>
                    </tr>

                    <!-- CTA Action Button -->
                    <tr>
                        <td align="center" style="padding: 8px 32px 28px 32px;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="border-radius: 8px; background-color: #0284c7;">
                                        <a href="{{ $metrics['project']['qc_url'] }}" target="_blank" style="font-size: 14px; font-weight: 700; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 8px; display: inline-block; background-color: #0284c7; border: 1px solid #0284c7;">
                                            Buka QC Board di Sistem &rarr;
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <div style="font-size: 11px; color: #94a3b8; margin-top: 10px;">
                                Klik tautan di atas untuk melihat detail item pekerjaan, log defect, dan riwayat test cases.
                            </div>
                        </td>
                    </tr>

                    <!-- Footer Section -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 20px 32px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8;">
                            <div style="font-weight: 600; color: #64748b; margin-bottom: 4px;">Wirodev Internal Management System</div>
                            <div>Pemberitahuan otomatis terkait Proyek {{ $project->title }}.</div>
                            <div style="margin-top: 8px; font-size: 11px; color: #cbd5e1;">&copy; {{ date('Y') }} Wirodev. All rights reserved.</div>
                        </td>
                    </tr>

                </table>
                <!-- /Main Container -->

            </td>
        </tr>
    </table>
    <!-- /Wrapper Table -->

</body>
</html>
