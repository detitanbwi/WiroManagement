<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\TestCase;
use App\Models\TaskBug;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QcExportService
{
    /**
     * Generate the QA/QC Excel Spreadsheet containing Kanban, Test Case, and Bug sheets.
     */
    public function generate(Project $project): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        // Sheet 1: Kanban Tasks
        $kanbanSheet = $spreadsheet->getActiveSheet();
        $kanbanSheet->setTitle('Kanban');
        $this->buildKanbanSheet($kanbanSheet, $project);

        // Sheet 2: Test Cases
        $testCaseSheet = $spreadsheet->createSheet();
        $testCaseSheet->setTitle('Test Case');
        $this->buildTestCaseSheet($testCaseSheet, $project);

        // Sheet 3: Bugs
        $bugSheet = $spreadsheet->createSheet();
        $bugSheet->setTitle('Bug');
        $this->buildBugSheet($bugSheet, $project);

        // Reset active sheet to first sheet
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Build the Kanban Tasks Worksheet.
     */
    protected function buildKanbanSheet(Worksheet $sheet, Project $project): void
    {
        $tasks = $project->tasks()->with([
            'assignee',
            'testCases',
            'bugs.testCase',
            'comments'
        ])->get();

        $columns = [
            'A' => ['label' => 'No', 'width' => 6, 'align' => Alignment::HORIZONTAL_CENTER],
            'B' => ['label' => 'Kode Task', 'width' => 14, 'align' => Alignment::HORIZONTAL_CENTER],
            'C' => ['label' => 'Judul Task', 'width' => 32, 'align' => Alignment::HORIZONTAL_LEFT],
            'D' => ['label' => 'Status / Kolom', 'width' => 18, 'align' => Alignment::HORIZONTAL_CENTER],
            'E' => ['label' => 'Assignee', 'width' => 22, 'align' => Alignment::HORIZONTAL_LEFT],
            'F' => ['label' => 'Terkait Test Case', 'width' => 20, 'align' => Alignment::HORIZONTAL_LEFT],
            'G' => ['label' => 'Jml TC', 'width' => 10, 'align' => Alignment::HORIZONTAL_CENTER],
            'H' => ['label' => 'Jml Bug', 'width' => 10, 'align' => Alignment::HORIZONTAL_CENTER],
            'I' => ['label' => 'Ada Bug Aktif?', 'width' => 16, 'align' => Alignment::HORIZONTAL_CENTER],
            'J' => ['label' => 'Deskripsi', 'width' => 45, 'align' => Alignment::HORIZONTAL_LEFT, 'wrap' => true],
            'K' => ['label' => 'Lampiran', 'width' => 12, 'align' => Alignment::HORIZONTAL_CENTER],
            'L' => ['label' => 'Komentar', 'width' => 12, 'align' => Alignment::HORIZONTAL_CENTER],
            'M' => ['label' => 'Tanggal Dibuat', 'width' => 18, 'align' => Alignment::HORIZONTAL_CENTER],
        ];

        $columnStatusLabels = [
            'todo' => 'To Do',
            'in_progress' => 'In Progress',
            'ready_for_qc' => 'Ready for QC',
            'qc_in_progress' => 'QC In Progress',
            'done' => 'Done',
        ];

        $doneCount = $tasks->where('column_id', 'done')->count();
        $inProgressCount = $tasks->whereIn('column_id', ['in_progress', 'ready_for_qc', 'qc_in_progress'])->count();
        $todoCount = $tasks->where('column_id', 'todo')->count();

        $metaText = sprintf(
            'Proyek: %s | Total Task: %d | Done: %d | In Progress: %d | To Do: %d | Diekspor: %s',
            $project->title,
            $tasks->count(),
            $doneCount,
            $inProgressCount,
            $todoCount,
            now()->format('d M Y, H:i')
        );

        $this->applyHeaderLayout($sheet, 'KANBAN BOARD TASKS', $metaText, $columns);

        $row = 5;
        $no = 1;

        foreach ($tasks as $task) {
            $directTc = $task->testCases;
            $bugTc = $task->bugs->map(fn($b) => $b->testCase)->filter();
            $allTc = $directTc->concat($bugTc)->unique('id');

            $hasActiveBug = $allTc->where('status', 'failed')->count() > 0 
                || $task->bugs->whereIn('status', ['open', 'in_progress'])->count() > 0;

            $tcCodes = $allTc->pluck('code')->implode(', ');
            $columnLabel = $columnStatusLabels[$task->column_id] ?? ucfirst(str_replace('_', ' ', $task->column_id));

            $sheet->setCellValue("A{$row}", $no);
            $sheet->setCellValue("B{$row}", $task->code);
            $sheet->setCellValue("C{$row}", $task->title);
            $sheet->setCellValue("D{$row}", $columnLabel);
            $sheet->setCellValue("E{$row}", $task->assignee ? $task->assignee->name : 'Unassigned');
            $sheet->setCellValue("F{$row}", $tcCodes ?: '-');
            $sheet->setCellValue("G{$row}", $allTc->count());
            $sheet->setCellValue("H{$row}", $task->bugs->count());
            $sheet->setCellValue("I{$row}", $hasActiveBug ? 'Ya (Blocked)' : 'Tidak');
            $sheet->setCellValue("J{$row}", $task->description ?: '-');
            $sheet->setCellValue("K{$row}", $task->attachment_path ? 'Ada' : '-');
            $sheet->setCellValue("L{$row}", $task->comments->count());
            $sheet->setCellValue("M{$row}", $task->created_at ? $task->created_at->format('d/m/Y H:i') : '-');

            // Format specific cells
            $sheet->getStyle("B{$row}")->getFont()->setBold(true);

            // Column status badge styling
            $statusStyle = $sheet->getStyle("D{$row}");
            $statusStyle->getFont()->setBold(true);
            if ($task->column_id === 'done') {
                $this->setCellFillAndText($statusStyle, 'DCFCE7', '166534');
            } elseif (in_array($task->column_id, ['in_progress', 'qc_in_progress'])) {
                $this->setCellFillAndText($statusStyle, 'DBEAFE', '1E40AF');
            } elseif ($task->column_id === 'ready_for_qc') {
                $this->setCellFillAndText($statusStyle, 'FEF3C7', '92400E');
            } else {
                $this->setCellFillAndText($statusStyle, 'F3F4F6', '4B5563');
            }

            // Has active bug badge
            if ($hasActiveBug) {
                $bugStyle = $sheet->getStyle("I{$row}");
                $this->setCellFillAndText($bugStyle, 'FEE2E2', '991B1B');
                $bugStyle->getFont()->setBold(true);
            }

            $this->applyDataRowBorders($sheet, "A{$row}:M{$row}", $no % 2 === 0);

            $row++;
            $no++;
        }

        $this->finalizeSheet($sheet, $columns, $row);
    }

    /**
     * Build the Test Cases Worksheet.
     */
    protected function buildTestCaseSheet(Worksheet $sheet, Project $project): void
    {
        $testCases = $this->getFlattenedTestCases($project->id);

        $columns = [
            'A' => ['label' => 'No', 'width' => 8, 'align' => Alignment::HORIZONTAL_CENTER],
            'B' => ['label' => 'Kode TC', 'width' => 14, 'align' => Alignment::HORIZONTAL_CENTER],
            'C' => ['label' => 'Modul / Parent', 'width' => 30, 'align' => Alignment::HORIZONTAL_LEFT],
            'D' => ['label' => 'Sub Test Case / Skenario (Anak)', 'width' => 38, 'align' => Alignment::HORIZONTAL_LEFT],
            'E' => ['label' => 'Tipe Uji', 'width' => 15, 'align' => Alignment::HORIZONTAL_CENTER],
            'F' => ['label' => 'Prioritas', 'width' => 14, 'align' => Alignment::HORIZONTAL_CENTER],
            'G' => ['label' => 'Kompleksitas', 'width' => 14, 'align' => Alignment::HORIZONTAL_CENTER],
            'H' => ['label' => 'Otomasi', 'width' => 16, 'align' => Alignment::HORIZONTAL_CENTER],
            'I' => ['label' => 'Status Uji', 'width' => 15, 'align' => Alignment::HORIZONTAL_CENTER],
            'J' => ['label' => 'Pra-Kondisi', 'width' => 30, 'align' => Alignment::HORIZONTAL_LEFT, 'wrap' => true],
            'K' => ['label' => 'Langkah-Langkah Pengujian', 'width' => 45, 'align' => Alignment::HORIZONTAL_LEFT, 'wrap' => true],
            'L' => ['label' => 'Hasil yang Diharapkan', 'width' => 40, 'align' => Alignment::HORIZONTAL_LEFT, 'wrap' => true],
            'M' => ['label' => 'Test Data / Payload', 'width' => 30, 'align' => Alignment::HORIZONTAL_LEFT, 'wrap' => true],
            'N' => ['label' => 'Terkait Task', 'width' => 22, 'align' => Alignment::HORIZONTAL_LEFT],
            'O' => ['label' => 'Bug Terkait', 'width' => 18, 'align' => Alignment::HORIZONTAL_CENTER],
            'P' => ['label' => 'Tanggal Dibuat', 'width' => 18, 'align' => Alignment::HORIZONTAL_CENTER],
        ];

        $passedCount = collect($testCases)->where('status', 'passed')->count();
        $failedCount = collect($testCases)->where('status', 'failed')->count();
        $pendingCount = collect($testCases)->where('status', 'pending')->count();

        $metaText = sprintf(
            'Proyek: %s | Total Test Case: %d | Passed: %d | Failed: %d | Pending: %d | Diekspor: %s',
            $project->title,
            count($testCases),
            $passedCount,
            $failedCount,
            $pendingCount,
            now()->format('d M Y, H:i')
        );

        $this->applyHeaderLayout($sheet, 'PROJECT TEST CASES', $metaText, $columns);

        $row = 5;

        foreach ($testCases as $index => $tc) {
            // Steps formatting
            $stepsText = '-';
            if (is_array($tc->steps) && count($tc->steps) > 0) {
                $formattedSteps = [];
                foreach ($tc->steps as $idx => $step) {
                    $formattedSteps[] = ($idx + 1) . '. ' . $step;
                }
                $stepsText = implode("\n", $formattedSteps);
            } elseif (is_string($tc->steps) && !empty($tc->steps)) {
                $stepsText = $tc->steps;
            }

            $taskText = $tc->projectTask ? ($tc->projectTask->code . ' - ' . $tc->projectTask->title) : '-';
            $bugsText = $tc->bugs->pluck('code')->implode(', ') ?: '-';
            $isParent = ($tc->tree_level ?? 0) === 0;

            $sheet->setCellValue("A{$row}", $tc->tree_number ?? ($index + 1));
            $sheet->setCellValue("B{$row}", $tc->code);

            // Parent goes in Col C (Modul / Parent), Child goes in Col D (Sub Test Case / Skenario (Anak))
            if ($isParent) {
                $sheet->setCellValue("C{$row}", $tc->title);
                $sheet->setCellValue("D{$row}", '-');
            } else {
                $indentPrefix = ($tc->tree_level > 1) ? str_repeat('   ', $tc->tree_level - 1) . '↳ ' : '↳ ';
                $sheet->setCellValue("C{$row}", '');
                $sheet->setCellValue("D{$row}", $indentPrefix . $tc->title);
                $sheet->getStyle("D{$row}")->getAlignment()->setIndent($tc->tree_level);
            }

            $sheet->setCellValue("E{$row}", $tc->test_type ?: 'Functional');
            $sheet->setCellValue("F{$row}", $tc->priority ?: 'Medium');
            $sheet->setCellValue("G{$row}", $tc->complexity ?: 'Medium');
            $sheet->setCellValue("H{$row}", $tc->automation_status ?: 'Manual');
            $sheet->setCellValue("I{$row}", ucfirst($tc->status ?: 'Pending'));
            $sheet->setCellValue("J{$row}", $tc->preconditions ?: '-');
            $sheet->setCellValue("K{$row}", $stepsText);
            $sheet->setCellValue("L{$row}", $tc->expected ?: '-');
            $sheet->setCellValue("M{$row}", $tc->payload ?: '-');
            $sheet->setCellValue("N{$row}", $taskText);
            $sheet->setCellValue("O{$row}", $bugsText);
            $sheet->setCellValue("P{$row}", $tc->created_at ? $tc->created_at->format('d/m/Y H:i') : '-');

            $sheet->getStyle("B{$row}")->getFont()->setBold(true);

            // Status Badge
            $statusStyle = $sheet->getStyle("I{$row}");
            $statusStyle->getFont()->setBold(true);
            if ($tc->status === 'passed') {
                $this->setCellFillAndText($statusStyle, 'DCFCE7', '166534');
            } elseif ($tc->status === 'failed') {
                $this->setCellFillAndText($statusStyle, 'FEE2E2', '991B1B');
            } else {
                $this->setCellFillAndText($statusStyle, 'FEF3C7', '92400E');
            }

            // Priority styling
            if ($tc->priority === 'Critical') {
                $prioStyle = $sheet->getStyle("F{$row}");
                $this->setCellFillAndText($prioStyle, 'FEE2E2', '991B1B');
                $prioStyle->getFont()->setBold(true);
            } elseif ($tc->priority === 'High') {
                $prioStyle = $sheet->getStyle("F{$row}");
                $this->setCellFillAndText($prioStyle, 'FFEDD5', 'C2410C');
            }

            // Distinguish Parent row visually with soft background
            if ($isParent) {
                $sheet->getStyle("A{$row}:P{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
                $sheet->getStyle("C{$row}")->getFont()->setBold(true);
                $this->applyDataRowBorders($sheet, "A{$row}:P{$row}", false);
            } else {
                $this->applyDataRowBorders($sheet, "A{$row}:P{$row}", ($index + 1) % 2 === 0);
            }

            $row++;
        }

        $this->finalizeSheet($sheet, $columns, $row);
    }

    /**
     * Get test cases flattened in hierarchical depth-first tree order.
     */
    protected function getFlattenedTestCases(int $projectId): array
    {
        $allCases = TestCase::where('project_id', $projectId)
            ->with(['parent', 'projectTask', 'bugs'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        // Safeguard for orphan test cases whose parent_id does not exist in this project
        $validIds = $allCases->pluck('id')->all();
        foreach ($allCases as $tc) {
            if ($tc->parent_id && !in_array($tc->parent_id, $validIds)) {
                $tc->parent_id = null;
            }
        }

        return $this->flattenTestCaseTree($allCases, null, 0, '');
    }

    /**
     * Recursively flatten test cases tree (Parent first, then its children directly beneath it).
     */
    protected function flattenTestCaseTree($cases, $parentId = null, int $level = 0, string $prefix = ''): array
    {
        $result = [];
        $index = 1;

        $matching = $cases->filter(fn($c) => $c->parent_id == $parentId);

        foreach ($matching as $case) {
            $numStr = $prefix === '' ? (string)$index : "{$prefix}.{$index}";
            $case->tree_level = $level;
            $case->tree_number = $numStr;
            $result[] = $case;

            $children = $this->flattenTestCaseTree($cases, $case->id, $level + 1, $numStr);
            foreach ($children as $child) {
                $result[] = $child;
            }

            $index++;
        }

        return $result;
    }

    /**
     * Build the Bugs Worksheet.
     */
    protected function buildBugSheet(Worksheet $sheet, Project $project): void
    {
        $bugs = TaskBug::where('project_id', $project->id)
            ->with(['testCase', 'projectTask'])
            ->orderBy('created_at', 'desc')
            ->get();

        $columns = [
            'A' => ['label' => 'No', 'width' => 6, 'align' => Alignment::HORIZONTAL_CENTER],
            'B' => ['label' => 'Kode Bug', 'width' => 14, 'align' => Alignment::HORIZONTAL_CENTER],
            'C' => ['label' => 'Severity', 'width' => 14, 'align' => Alignment::HORIZONTAL_CENTER],
            'D' => ['label' => 'Status', 'width' => 16, 'align' => Alignment::HORIZONTAL_CENTER],
            'E' => ['label' => 'Deskripsi Defect / Bug', 'width' => 36, 'align' => Alignment::HORIZONTAL_LEFT, 'wrap' => true],
            'F' => ['label' => 'Hasil Aktual (Actual Result)', 'width' => 36, 'align' => Alignment::HORIZONTAL_LEFT, 'wrap' => true],
            'G' => ['label' => 'Langkah Reproduksi (Steps to Reproduce)', 'width' => 45, 'align' => Alignment::HORIZONTAL_LEFT, 'wrap' => true],
            'H' => ['label' => 'Environment', 'width' => 18, 'align' => Alignment::HORIZONTAL_CENTER],
            'I' => ['label' => 'Asal Test Case', 'width' => 24, 'align' => Alignment::HORIZONTAL_LEFT],
            'J' => ['label' => 'Ditugaskan pada Task', 'width' => 24, 'align' => Alignment::HORIZONTAL_LEFT],
            'K' => ['label' => 'Lampiran', 'width' => 12, 'align' => Alignment::HORIZONTAL_CENTER],
            'L' => ['label' => 'Tanggal Dilaporkan', 'width' => 18, 'align' => Alignment::HORIZONTAL_CENTER],
            'M' => ['label' => 'Terakhir Diperbarui', 'width' => 18, 'align' => Alignment::HORIZONTAL_CENTER],
        ];

        $openCount = $bugs->where('status', 'open')->count();
        $inProgressCount = $bugs->where('status', 'in_progress')->count();
        $resolvedCount = $bugs->where('status', 'resolved')->count();

        $metaText = sprintf(
            'Proyek: %s | Total Bug: %d | Open: %d | In Progress: %d | Resolved: %d | Diekspor: %s',
            $project->title,
            $bugs->count(),
            $openCount,
            $inProgressCount,
            $resolvedCount,
            now()->format('d M Y, H:i')
        );

        $this->applyHeaderLayout($sheet, 'DEFECTS & BUG TRACKER', $metaText, $columns);

        $row = 5;
        $no = 1;

        foreach ($bugs as $bug) {
            $tcText = $bug->testCase ? ($bug->testCase->code . ' - ' . $bug->testCase->title) : '-';
            $taskText = $bug->projectTask ? ($bug->projectTask->code . ' - ' . $bug->projectTask->title) : 'Belum Di-assign';

            $sheet->setCellValue("A{$row}", $no);
            $sheet->setCellValue("B{$row}", $bug->code);
            $sheet->setCellValue("C{$row}", $bug->severity ?: 'Medium');
            $sheet->setCellValue("D{$row}", ucfirst(str_replace('_', ' ', $bug->status ?: 'open')));
            $sheet->setCellValue("E{$row}", $bug->description ?: '-');
            $sheet->setCellValue("F{$row}", $bug->actual_result ?: '-');
            $sheet->setCellValue("G{$row}", $bug->steps_to_reproduce ?: '-');
            $sheet->setCellValue("H{$row}", $bug->environment ?: '-');
            $sheet->setCellValue("I{$row}", $tcText);
            $sheet->setCellValue("J{$row}", $taskText);
            $sheet->setCellValue("K{$row}", $bug->attachment_path ? 'Ada' : '-');
            $sheet->setCellValue("L{$row}", $bug->created_at ? $bug->created_at->format('d/m/Y H:i') : '-');
            $sheet->setCellValue("M{$row}", $bug->updated_at ? $bug->updated_at->format('d/m/Y H:i') : '-');

            $sheet->getStyle("B{$row}")->getFont()->setBold(true);

            // Severity Badge
            $sevStyle = $sheet->getStyle("C{$row}");
            $sevStyle->getFont()->setBold(true);
            if ($bug->severity === 'Critical') {
                $this->setCellFillAndText($sevStyle, 'FEE2E2', '991B1B');
            } elseif ($bug->severity === 'High') {
                $this->setCellFillAndText($sevStyle, 'FFEDD5', 'C2410C');
            } elseif ($bug->severity === 'Medium') {
                $this->setCellFillAndText($sevStyle, 'FEF3C7', '92400E');
            } else {
                $this->setCellFillAndText($sevStyle, 'F1F5F9', '475569');
            }

            // Status Badge
            $statusStyle = $sheet->getStyle("D{$row}");
            $statusStyle->getFont()->setBold(true);
            if ($bug->status === 'resolved') {
                $this->setCellFillAndText($statusStyle, 'DCFCE7', '166534');
            } elseif ($bug->status === 'in_progress') {
                $this->setCellFillAndText($statusStyle, 'DBEAFE', '1E40AF');
            } else {
                $this->setCellFillAndText($statusStyle, 'FEE2E2', '991B1B');
            }

            $this->applyDataRowBorders($sheet, "A{$row}:M{$row}", $no % 2 === 0);

            $row++;
            $no++;
        }

        $this->finalizeSheet($sheet, $columns, $row);
    }

    /**
     * Apply the uniform, executive header banner layout.
     */
    protected function applyHeaderLayout(Worksheet $sheet, string $title, string $metaText, array $columns): void
    {
        $lastCol = array_key_last($columns);

        // Row 1: Title Banner
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', ' ' . $title);
        $sheet->getRowDimension(1)->setRowHeight(32);
        $titleStyle = $sheet->getStyle("A1:{$lastCol}1");
        $titleStyle->getFont()->setName('Segoe UI')->setSize(14)->setBold(true)->getColor()->setRGB('FFFFFF');
        $titleStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E3A8A'); // Dark Navy
        $titleStyle->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Row 2: Metadata / KPI summary
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A2', ' ' . $metaText);
        $sheet->getRowDimension(2)->setRowHeight(22);
        $metaStyle = $sheet->getStyle("A2:{$lastCol}2");
        $metaStyle->getFont()->setName('Segoe UI')->setSize(9.5)->setItalic(true)->getColor()->setRGB('334155');
        $metaStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9'); // Light Slate
        $metaStyle->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Row 3: Spacer
        $sheet->getRowDimension(3)->setRowHeight(8);

        // Row 4: Column Headers
        $sheet->getRowDimension(4)->setRowHeight(26);
        foreach ($columns as $colLetter => $colDef) {
            $cell = "{$colLetter}4";
            $sheet->setCellValue($cell, $colDef['label']);
            $colStyle = $sheet->getStyle($cell);
            $colStyle->getFont()->setName('Segoe UI')->setSize(10)->setBold(true)->getColor()->setRGB('FFFFFF');
            $colStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E293B'); // Slate 800
            $colStyle->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Set column width
            if (isset($colDef['width'])) {
                $sheet->getColumnDimension($colLetter)->setWidth($colDef['width']);
            }
        }

        // Apply borders on header
        $sheet->getStyle("A4:{$lastCol}4")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('475569');
    }

    /**
     * Finalize sheet configuration (auto-filter, freeze panes, alignments, gridlines).
     */
    protected function finalizeSheet(Worksheet $sheet, array $columns, int $lastRow): void
    {
        $lastCol = array_key_last($columns);
        $dataEndRow = max(5, $lastRow - 1);

        // Set alignment and text wrapping for data columns
        foreach ($columns as $colLetter => $colDef) {
            $colRange = "{$colLetter}5:{$colLetter}{$dataEndRow}";
            $style = $sheet->getStyle($colRange);
            $style->getFont()->setName('Segoe UI')->setSize(9.5);
            $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

            if (isset($colDef['align'])) {
                $style->getAlignment()->setHorizontal($colDef['align']);
            }
            if (!empty($colDef['wrap'])) {
                $style->getAlignment()->setWrapText(true);
            }
        }

        // Auto-filter
        $sheet->setAutoFilter("A4:{$lastCol}4");

        // Freeze header pane (Row 5 downwards scrolls)
        $sheet->freezePane('A5');

        // Show grid lines explicitly
        $sheet->setShowGridLines(true);
    }

    /**
     * Set background fill and font color for status badge cells.
     */
    protected function setCellFillAndText($style, string $bgColorRgb, string $textColorRgb): void
    {
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($bgColorRgb);
        $style->getFont()->getColor()->setRGB($textColorRgb);
    }

    /**
     * Apply subtle cell borders and optional alternating zebra striping.
     */
    protected function applyDataRowBorders(Worksheet $sheet, string $range, bool $isEvenRow): void
    {
        $style = $sheet->getStyle($range);
        $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');

        if ($isEvenRow) {
            // Apply slight alternating row background if not already colored by badge
            $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F8FAFC');
        }
    }
}
