<?php

class PdfService
{
    private const PAGE_WIDTH = 595;
    private const PAGE_HEIGHT = 842;
    private const MARGIN = 50;
    private const WATERMARK_WIDTH = 260;

    public static function leaveRequest(array $request, array $steps): string
    {
        $writer = new self();
        $writer->officialLeaveForm($request, $steps);

        return $writer->output();
    }

    public static function leaveReport(array $filters, array $summary, array $requests): string
    {
        $writer = new self();
        $writer->officialLeaveReport($filters, $summary, $requests);

        return $writer->output();
    }

    private array $pages = [];
    private array $commands = [];
    private int $y = self::PAGE_HEIGHT - self::MARGIN;
    private ?array $watermark = null;
    private ?array $governmentArms = null;
    private bool $hroNameLoaded = false;
    private string $hroName = '';

    private function __construct()
    {
        $this->watermark = $this->loadWatermark();
        $this->governmentArms = $this->loadJpegImage(dirname(__DIR__, 2) . '/public/assets/images/government-arm.jpg');
    }

    private function officialLeaveForm(array $request, array $steps): void
    {
        $status = (string) ($request['status'] ?? '');
        $approved = $status === 'approved';
        $forfeited = $status === 'forfeited';
        $recalled = !empty($request['recalled_at']);
        $supervisorStep = $this->stepForRole($steps, 'supervisor');
        $statusLabel = $recalled ? 'Recalled' : status_label($status);
        $reportBackDate = $approved ? LeaveBalanceService::returnDateAfter((string) $request['end_date']) : '-';
        $this->pageFrame();
        $this->headerBlock((int) $request['id'], $recalled ? 'recalled' : $status);

        $this->sectionBand(700, 'PART A: STAFF DETAILS');
        $this->fieldBox(50, 660, 248, 34, 'Staff name', $request['employee_name'] ?? 'N/A');
        $this->fieldBox(298, 660, 247, 34, 'Payroll / ID number', $request['staff_id'] ?? 'N/A');
        $this->fieldBox(50, 626, 248, 34, 'Department', $request['directorate_name'] ?? 'N/A');
        $this->fieldBox(298, 626, 247, 34, 'Directorate', $request['department_name'] ?? 'N/A');
        $this->fieldBox(50, 592, 248, 34, 'Job group', $request['job_group'] ?? 'N/A');
        $this->fieldBox(298, 592, 247, 34, 'Contact number', format_kenyan_phone_number($request['contact_number'] ?? ($request['employee_phone'] ?? '')));

        $this->sectionBand(552, 'PART B: LEAVE DETAILS');
        $this->fieldBox(50, 512, 248, 34, 'Leave type', $request['leave_type_name'] ?? 'N/A');
        $this->fieldBox(298, 512, 247, 34, 'Working days', format_days($request['days_requested'] ?? null, 'N/A'));
        $this->fieldBox(50, 478, 165, 34, 'Start date', format_date($request['start_date'] ?? null));
        $this->fieldBox(215, 478, 165, 34, 'End date', format_date($request['end_date'] ?? null));
        $this->fieldBox(380, 478, 165, 34, 'Report back', $approved ? format_date($reportBackDate) : '-');
        $this->fieldBox(50, 444, 248, 34, 'Request status', $statusLabel);
        $this->fieldBox(298, 444, 247, 34, 'Submitted on', format_date($request['submitted_at'] ?? null));
        $this->multiLineBox(50, 374, 495, 70, 'Reason for leave', $request['reason'] ?? 'N/A');

        $this->sectionBand(334, 'PART C: HANDOVER / DUTY COVER');
        $this->multiLineBox(50, 254, 495, 80, 'Handover notes', $request['handover_notes'] ?? 'N/A');

        $this->sectionBand(224, $forfeited ? 'PART D: FORFEITURE CERTIFICATE' : 'PART D: APPROVAL CERTIFICATE');
        $this->textAt(58, 205, $forfeited
            ? 'This section confirms that the leave was forfeited and a payout record has been captured in the system.'
            : ($recalled
                ? 'This section confirms that the leave was officially recalled and the employee return has been recorded.'
                : 'This section confirms the official decision recorded in the online leave system.'), 9);

        if ($forfeited) {
            $this->checkbox(58, 187, false, 'Approved');
            $this->checkbox(145, 187, true, 'Forfeited');
            $this->checkbox(245, 187, false, 'Pending');
            $this->fieldBox(50, 150, 248, 35, 'Recorded by', $request['forfeited_by_name'] ?? 'HR');
            $this->fieldBox(298, 150, 247, 35, 'Recorded on', format_date($request['forfeited_at'] ?? null));
            $this->fieldBox(50, 114, 248, 36, 'Forfeiture note', $request['forfeiture_notes'] ?? 'N/A', 8);
            $this->fieldBox(298, 114, 247, 36, 'Payroll records', 'Payout record saved for payroll processing');
        } else {
            if ($recalled) {
                $this->checkbox(58, 187, true, 'Approved');
                $this->checkbox(145, 187, true, 'Recalled');
                $this->checkbox(232, 187, false, 'Pending');
                $this->fieldBox(50, 150, 248, 35, 'Recalled by', $request['recalled_by_name'] ?? 'Immediate supervisor');
                $this->fieldBox(298, 150, 247, 35, 'Recall date', format_date($request['recalled_at'] ?? null));
                $this->fieldBox(50, 114, 248, 36, 'Recall reason', $request['recall_reason'] ?? 'N/A', 8);
                $this->fieldBox(298, 114, 247, 36, 'HR records', 'Recall notice filed with employee record');
            } else {
                $this->checkbox(58, 187, $approved, 'Approved');
                $this->checkbox(145, 187, ($request['status'] ?? '') === 'rejected', 'Rejected');
                $this->checkbox(232, 187, str_starts_with((string) ($request['status'] ?? ''), 'pending_'), 'Pending');
                $this->fieldBox(50, 150, 248, 35, 'Approving officer', $supervisorStep['approver_name'] ?? 'Pending supervisor action');
                $this->fieldBox(298, 150, 247, 35, 'Approval date', format_date($supervisorStep['acted_at'] ?? null));
                $this->fieldBox(50, 114, 248, 36, 'Supervisor comments', $supervisorStep['comments'] ?? 'N/A', 8);
                $this->fieldBox(298, 114, 247, 36, 'HR records', $approved ? 'Approved form available for record keeping' : 'Pending final approval');
            }
        }

        $this->hroConfirmationLine(50, 99);

        $this->sectionBand(74, $forfeited ? 'PART E: FORFEITURE / PAYOUT' : ($recalled ? 'PART E: OFFICIAL RECALL' : 'PART E: RETURN / RESUMPTION'));
        if ($recalled) {
            $this->fieldBox(50, 42, 165, 30, 'Recall date', format_date($request['recalled_at'] ?? null));
            $this->fieldBox(215, 42, 165, 30, 'Recalled by', $request['recalled_by_name'] ?? 'Immediate supervisor');
            $this->fieldBox(380, 42, 165, 30, 'Status', 'Leave withdrawn');
            $this->stamp(430, 43, 'RECALLED');
        } elseif ($approved) {
            $this->fieldBox(50, 42, 360, 30, 'Expected report-back date', format_date($reportBackDate));
            $this->stamp(430, 43, 'APPROVED');
        } elseif ($forfeited) {
            $this->fieldBox(50, 42, 165, 30, 'Forfeited days', format_days($request['days_forfeited'] ?? null, 'N/A'));
            $this->fieldBox(215, 42, 165, 30, 'Payout amount', format_currency($request['payout_amount'] ?? null));
            $this->fieldBox(380, 42, 165, 30, 'Payroll note', 'Awaiting payroll settlement');
        } else {
            $this->fieldBox(50, 42, 495, 30, 'Expected report-back date', '-');
        }

        $this->textAt(50, 37, 'Generated on ' . date('d M Y H:i') . ' by ' . app_config('name'), 6);
    }

    private function officialLeaveReport(array $filters, array $summary, array $requests): void
    {
        $reference = 'LR-' . date('YmdHis');
        $title = 'DEPARTMENT / DIRECTORATE LEAVE REPORT';
        $from = !empty($filters['from']) ? format_date((string) $filters['from']) : 'All time';
        $to = !empty($filters['to']) ? format_date((string) $filters['to']) : 'All time';
        $directorate = $filters['directorate']['name'] ?? 'All departments';
        $department = $filters['department']['name'] ?? 'All directorates';
        $generatedBy = $filters['generated_by'] ?? 'System user';
        $generatedOn = date('d M Y H:i');

        $this->reportPageHeader($title, $reference);

        $this->sectionBand(684, 'PART A: REPORT SCOPE');
        $this->fieldBox(50, 644, 248, 34, 'Department', $directorate);
        $this->fieldBox(298, 644, 247, 34, 'Directorate', $department);
        $this->fieldBox(50, 610, 165, 34, 'From', $from);
        $this->fieldBox(215, 610, 165, 34, 'To', $to);
        $this->fieldBox(380, 610, 165, 34, 'Generated on', $generatedOn);
        $this->fieldBox(50, 576, 248, 34, 'Generated by', $generatedBy);
        $this->fieldBox(298, 576, 247, 34, 'Approved records', format_days(count($requests), '0') . ' leave requests');

        $this->sectionBand(536, 'PART B: SUMMARY BY LEAVE TYPE');
        $summaryColumns = [
            ['label' => 'LEAVE TYPE', 'width' => 270],
            ['label' => 'APPROVED REQUESTS', 'width' => 125],
            ['label' => 'TOTAL DAYS', 'width' => 100],
        ];
        $this->reportTableHeader(50, 510, $summaryColumns, 22);

        $rowY = 488;
        if (!$summary) {
            $this->reportMessageBox(50, 454, 495, 34, 'No approved leave records found for the selected filters.');
            $rowY = 432;
        } else {
            foreach ($summary as $index => $row) {
                if ($rowY < 382) {
                    $this->reportFooter($reference);
                    $this->newPage();
                    $this->reportPageHeader($title, $reference, 'SUMMARY CONTINUED');
                    $this->sectionBand(684, 'PART B: SUMMARY BY LEAVE TYPE');
                    $this->reportTableHeader(50, 660, $summaryColumns, 22);
                    $rowY = 638;
                }

                $this->reportTableRow(50, $rowY, $summaryColumns, [
                    $row['leave_type_name'] ?? 'N/A',
                    format_days($row['request_count'] ?? null, '0'),
                    format_days($row['total_days'] ?? null, '0'),
                ], 22, $index % 2 === 0 ? [1, 1, 1] : [0.97, 0.99, 0.96]);
                $rowY -= 22;
            }
        }

        $detailSectionY = $rowY - 34;
        $detailColumns = [
            ['label' => 'STAFF / PAYROLL-ID', 'width' => 122],
            ['label' => 'DEPARTMENT / DIRECTORATE', 'width' => 136],
            ['label' => 'LEAVE TYPE', 'width' => 91],
            ['label' => 'DATES', 'width' => 101],
            ['label' => 'DAYS', 'width' => 45],
        ];

        if ($detailSectionY < 170) {
            $this->reportFooter($reference);
            $this->newPage();
            $this->reportPageHeader($title, $reference, 'RECORDS');
            $detailSectionY = 684;
        }

        $this->sectionBand($detailSectionY, 'PART C: APPROVED LEAVE RECORDS');
        $this->reportTableHeader(50, $detailSectionY - 24, $detailColumns, 20);
        $rowY = $detailSectionY - 56;

        if (!$requests) {
            $this->reportMessageBox(50, $rowY - 2, 495, 34, 'No approved leave records found for this report.');
            $this->reportFooter($reference);
            return;
        }

        foreach ($requests as $index => $request) {
            if ($rowY < 104) {
                $this->reportFooter($reference);
                $this->newPage();
                $this->reportPageHeader($title, $reference, 'RECORDS CONTINUED');
                $this->sectionBand(684, 'PART C: APPROVED LEAVE RECORDS');
                $this->reportTableHeader(50, 660, $detailColumns, 20);
                $rowY = 628;
            }

            $this->reportTableRow(50, $rowY, $detailColumns, [
                trim(($request['employee_name'] ?? 'N/A') . ' / ' . ($request['staff_id'] ?? 'N/A') . ' / ' . ($request['job_group'] ?? 'N/A')),
                trim(($request['directorate_name'] ?? 'N/A') . ' / ' . ($request['department_name'] ?? 'N/A')),
                $request['leave_type_name'] ?? 'N/A',
                format_date($request['start_date'] ?? null) . ' to ' . format_date($request['end_date'] ?? null),
                format_days($request['days_requested'] ?? null, '0'),
            ], 32, $index % 2 === 0 ? [1, 1, 1] : [0.97, 0.99, 0.96]);
            $rowY -= 32;
        }

        $this->reportFooter($reference);
    }

    private function reportPageHeader(string $title, string $reference, ?string $suffix = null): void
    {
        $this->pageFrame();
        $this->reportHeaderBlock($title, $reference, $suffix);
    }

    private function reportHeaderBlock(string $title, string $reference, ?string $suffix = null): void
    {
        $displayTitle = $suffix ? $title . ' - ' . $suffix : $title;

        $this->rect(50, 724, 495, 70, false, [0.96, 0.99, 0.94]);
        $this->rect(50, 724, 495, 70);
        $this->drawImage($this->governmentArms, 'GovArms', 58, 739, 50);
        $this->drawImage($this->watermark, 'WmLogo', 494, 739, 43);
        $this->centerText(780, 'COUNTY GOVERNMENT OF BUSIA', 13, 'F2');
        $this->centerText(764, strtoupper((string) app_config('name', 'Staff Online Leave Application System')), 9, 'F2');
        $this->centerText(744, $displayTitle, 15, 'F2');
        $this->centerText(731, 'Ref: ' . $reference . ' | Generated: ' . date('d M Y'), 8);
        $this->colorLine(50, 724, 495, 3);
    }

    private function reportTableHeader(float $x, float $y, array $columns, float $height = 20): void
    {
        $cursor = $x;

        foreach ($columns as $column) {
            $width = (float) $column['width'];
            $this->rect($cursor, $y, $width, $height, true, [0.08, 0.42, 0.27], [0.08, 0.42, 0.27], 0.5);
            $this->textAt($cursor + 5, $y + 7, (string) $column['label'], 7, 'F2', [1, 1, 1]);
            $cursor += $width;
        }
    }

    private function reportTableRow(float $x, float $y, array $columns, array $values, float $height = 24, ?array $fill = null): void
    {
        $cursor = $x;
        $fill = $fill ?? [1, 1, 1];

        foreach ($columns as $index => $column) {
            $width = (float) $column['width'];
            $this->rect($cursor, $y, $width, $height, true, $fill, [0.78, 0.84, 0.73], 0.4);

            $lines = $this->wrapForWidth((string) ($values[$index] ?? 'N/A'), $width - 10, 8);
            $maxLines = max(1, (int) floor(($height - 8) / 9));
            foreach (array_slice($lines, 0, $maxLines) as $lineIndex => $line) {
                $this->textAt($cursor + 5, $y + $height - 12 - ($lineIndex * 9), $line, 8);
            }

            $cursor += $width;
        }
    }

    private function reportMessageBox(float $x, float $y, float $w, float $h, string $message): void
    {
        $this->rect($x, $y, $w, $h, true, [1, 1, 1], [0.78, 0.84, 0.73]);
        foreach ($this->wrapForWidth($message, $w - 12, 9) as $index => $line) {
            $this->textAt($x + 6, $y + $h - 14 - ($index * 10), $line, 9);
        }
    }

    private function reportFooter(string $reference): void
    {
        $this->hroConfirmationLine(50, 62);
        $this->drawLine(50, 48, 545, 48, [0.78, 0.84, 0.73], 0.5);
        $this->textAt(50, 37, 'Generated on ' . date('d M Y H:i') . ' by ' . app_config('name') . ' | ' . $reference, 6);
    }

    private function pageFrame(): void
    {
        $this->rect(36, 36, 523, 770);
    }

    private function headerBlock(int $requestId, string $status): void
    {
        $this->rect(50, 724, 495, 70, false, [0.96, 0.99, 0.94]);
        $this->rect(50, 724, 495, 70);
        $this->drawImage($this->governmentArms, 'GovArms', 58, 739, 50);
        $this->drawImage($this->watermark, 'WmLogo', 494, 739, 43);
        $this->centerText(780, 'COUNTY GOVERNMENT OF BUSIA', 13, 'F2');
        $this->centerText(764, 'STAFF ONLINE LEAVE APPLICATION SYSTEM', 10, 'F2');
        $title = $status === 'approved'
            ? 'APPROVED LEAVE FORM'
            : ($status === 'forfeited' ? 'FORFEITED LEAVE FORM' : 'LEAVE APPLICATION FORM');
        $this->centerText(744, $title, 15, 'F2');
        $this->centerText(731, 'Form No: LAF-' . $requestId . ' | Status: ' . status_label($status), 8);
        $this->colorLine(50, 724, 495, 3);
    }

    private function sectionBand(float $y, string $title): void
    {
        $this->rect(50, $y, 495, 22, false, [0.08, 0.42, 0.27]);
        $this->textAt(58, $y + 7, $title, 10, 'F2', [1, 1, 1]);
    }

    private function fieldBox(float $x, float $y, float $w, float $h, string $label, ?string $value, int $valueSize = 9): void
    {
        $this->rect($x, $y, $w, $h, false, [1, 1, 1]);
        $this->rect($x, $y, $w, $h);
        $this->textAt($x + 6, $y + $h - 11, strtoupper($label), 7, 'F2', [0.22, 0.30, 0.42]);

        $lines = $this->wrapForWidth(($value !== null && trim($value) !== '') ? $value : 'N/A', $w - 12, $valueSize);
        $maxLines = max(1, (int) floor(($h - 16) / ($valueSize + 2)));
        foreach (array_slice($lines, 0, $maxLines) as $index => $line) {
            $this->textAt($x + 6, $y + $h - 23 - ($index * ($valueSize + 2)), $line, $valueSize, 'F1');
        }
    }

    private function multiLineBox(float $x, float $y, float $w, float $h, string $label, ?string $value): void
    {
        $this->rect($x, $y, $w, $h, false, [1, 1, 1]);
        $this->rect($x, $y, $w, $h);
        $this->textAt($x + 6, $y + $h - 12, strtoupper($label), 7, 'F2', [0.22, 0.30, 0.42]);

        $lines = $this->wrapForWidth(($value !== null && trim($value) !== '') ? $value : 'N/A', $w - 12, 9);
        $maxLines = max(1, (int) floor(($h - 18) / 11));
        foreach (array_slice($lines, 0, $maxLines) as $index => $line) {
            $this->textAt($x + 6, $y + $h - 26 - ($index * 11), $line, 9);
        }
    }

    private function checkbox(float $x, float $y, bool $checked, string $label): void
    {
        $this->rect($x, $y, 10, 10);
        if ($checked) {
            $this->drawLine($x + 2, $y + 5, $x + 4, $y + 2);
            $this->drawLine($x + 4, $y + 2, $x + 9, $y + 9);
        }
        $this->textAt($x + 15, $y + 1, $label, 9);
    }

    private function stamp(float $x, float $y, string $text, ?array $fill = null, ?array $strokeColor = null, ?array $textColor = null): void
    {
        $fill = $fill ?? [0.86, 0.96, 0.89];
        $strokeColor = $strokeColor ?? [0.07, 0.39, 0.19];
        $textColor = $textColor ?? [0.07, 0.39, 0.19];
        $this->rect($x, $y, 92, 28, false, $fill);
        $this->rect($x, $y, 92, 28, true, null, $strokeColor, 1.4);
        $this->textAt($x + 16, $y + 9, $text, 13, 'F2', $textColor);
    }

    private function rect(float $x, float $y, float $w, float $h, bool $stroke = true, ?array $fill = null, ?array $strokeColor = null, float $lineWidth = 0.5): void
    {
        $parts = ['q'];
        if ($fill !== null) {
            $parts[] = sprintf('%.3F %.3F %.3F rg', $fill[0], $fill[1], $fill[2]);
        }
        if ($strokeColor !== null) {
            $parts[] = sprintf('%.3F %.3F %.3F RG', $strokeColor[0], $strokeColor[1], $strokeColor[2]);
        }
        $parts[] = sprintf('%.2F w %.2F %.2F %.2F %.2F re %s', $lineWidth, $x, $y, $w, $h, $fill !== null && $stroke ? 'B' : ($fill !== null ? 'f' : 'S'));
        $parts[] = 'Q';
        $this->commands[] = implode(' ', $parts);
    }

    private function drawLine(float $x1, float $y1, float $x2, float $y2, ?array $color = null, float $lineWidth = 0.6): void
    {
        $color = $color ?? [0.1, 0.1, 0.1];
        $this->commands[] = sprintf(
            'q %.3F %.3F %.3F RG %.2F w %.2F %.2F m %.2F %.2F l S Q',
            $color[0],
            $color[1],
            $color[2],
            $lineWidth,
            $x1,
            $y1,
            $x2,
            $y2
        );
    }

    private function dottedLine(float $x1, float $y, float $x2, ?array $color = null): void
    {
        for ($x = $x1; $x < $x2; $x += 6) {
            $this->drawLine($x, $y, min($x + 2.6, $x2), $y, $color ?? [0.1, 0.1, 0.1], 0.45);
        }
    }

    private function hroConfirmationLine(float $x, float $y): void
    {
        $label = 'CONFIRMED BY HRO (DEPARTMENT OF PUBLIC SERVICE MANAGEMENT):';
        $name = $this->hroConfirmationName();
        $lineStart = max(
            $x + 330,
            $x + $this->approxTextWidth($label, 8) + 24
        );
        $lineEnd = 545;

        $this->textAt($x, $y + 3, $label, 8, 'F2');
        $this->dottedLine($lineStart, $y + 1.5, $lineEnd);

        if ($name !== '') {
            $this->textAt($lineStart + 4, $y + 3, $name, 7, 'F1');
        }
    }

    private function hroConfirmationName(): string
    {
        if ($this->hroNameLoaded) {
            return $this->hroName;
        }

        $configuredName = trim((string) app_config('hro_confirmation_name', ''));
        if ($configuredName !== '') {
            $this->hroNameLoaded = true;
            $this->hroName = $configuredName;

            return $this->hroName;
        }

        $currentUser = function_exists('current_user') ? current_user() : null;
        if ($currentUser && ($currentUser['role'] ?? '') === 'hr' && !empty($currentUser['full_name'])) {
            $this->hroNameLoaded = true;
            $this->hroName = (string) $currentUser['full_name'];

            return $this->hroName;
        }

        $hrUser = User::firstActiveByRole('hr');
        $this->hroNameLoaded = true;
        $this->hroName = $hrUser['full_name'] ?? '';

        return $this->hroName;
    }

    private function colorLine(float $x, float $y, float $w, float $h): void
    {
        $segments = [
            [[0.47, 0.72, 0.16], 0.34],
            [[0.79, 0.58, 0.12], 0.18],
            [[0.70, 0.13, 0.09], 0.20],
            [[0.14, 0.13, 0.37], 0.28],
        ];
        $cursor = $x;
        foreach ($segments as [$color, $ratio]) {
            $segmentWidth = $w * $ratio;
            $this->rect($cursor, $y, $segmentWidth, $h, false, $color);
            $cursor += $segmentWidth;
        }
    }

    private function textAt(float $x, float $y, string $text, int $size = 10, string $font = 'F1', ?array $color = null): void
    {
        $prefix = '';
        $suffix = '';
        if ($color !== null) {
            $prefix = sprintf('q %.3F %.3F %.3F rg ', $color[0], $color[1], $color[2]);
            $suffix = ' Q';
        }

        $this->commands[] = $prefix . 'BT /' . $font . ' ' . $size . ' Tf '
            . sprintf('%.2F %.2F Td ', $x, $y)
            . '(' . $this->escape($text) . ') Tj ET' . $suffix;
    }

    private function centerText(float $y, string $text, int $size = 10, string $font = 'F1'): void
    {
        $x = (self::PAGE_WIDTH - $this->approxTextWidth($text, $size)) / 2;
        $this->textAt($x, $y, $text, $size, $font);
    }

    private function drawImage(?array $image, string $resourceName, float $x, float $y, float $width): void
    {
        $command = $this->imageCommand($image, $resourceName, $x, $y, $width);
        if ($command !== '') {
            $this->commands[] = $command;
        }
    }

    private function imageCommand(?array $image, string $resourceName, float $x, float $y, float $width): string
    {
        if ($image === null) {
            return '';
        }

        $height = $width * ((int) $image['height'] / max(1, (int) $image['width']));

        return sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /%s Do Q', $width, $height, $x, $y, $resourceName);
    }

    private function wrapForWidth(string $text, float $width, int $size): array
    {
        $chars = max(10, (int) floor($width / max(1, $size * 0.52)));
        return $this->wrap($text, $chars);
    }

    private function approxTextWidth(string $text, int $size): float
    {
        return strlen($text) * $size * 0.52;
    }

    private function stepForRole(array $steps, string $role): ?array
    {
        foreach ($steps as $step) {
            if (($step['role'] ?? '') === $role) {
                return $step;
            }
        }

        return null;
    }

    private function heading(string $text): void
    {
        $this->text($text, 18, 'F2');
        $this->line('', 8);
    }

    private function section(string $text): void
    {
        $this->text($text, 13, 'F2');
    }

    private function field(string $label, ?string $value): void
    {
        $this->paragraph($label . ': ' . (($value !== null && $value !== '') ? $value : 'N/A'), 11);
    }

    private function paragraph(string $text, int $size = 11): void
    {
        $width = $size >= 11 ? 86 : 96;
        foreach ($this->wrap($text, $width) as $line) {
            $this->text($line, $size, 'F1');
        }
    }

    private function space(): void
    {
        $this->line('', 10);
    }

    private function line(string $text, int $size = 11): void
    {
        $this->text($text, $size, 'F1');
    }

    private function text(string $text, int $size, string $font): void
    {
        if ($this->y < self::MARGIN + 30) {
            $this->newPage();
        }

        if ($text !== '') {
            $this->commands[] = 'BT /' . $font . ' ' . $size . ' Tf ' . self::MARGIN . ' ' . $this->y
                . ' Td (' . $this->escape($text) . ') Tj ET';
        }

        $this->y -= $size + 6;
    }

    private function newPage(): void
    {
        $this->pages[] = implode("\n", $this->commands);
        $this->commands = [];
        $this->y = self::PAGE_HEIGHT - self::MARGIN;
    }

    private function output(): string
    {
        if ($this->commands || !$this->pages) {
            $this->newPage();
        }

        return $this->build($this->pages);
    }

    private function wrap(string $text, int $width): array
    {
        $text = preg_replace('/\s+/', ' ', trim($text)) ?? '';
        if ($text === '') {
            return ['N/A'];
        }

        return explode("\n", wordwrap($text, $width, "\n", true));
    }

    private function escape(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $text) ?? '';

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                $text = $converted;
            }
        }

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function build(array $pageStreams): string
    {
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
        ];
        $kids = [];
        $objectId = 5;
        $watermarkImageId = null;
        $watermarkStateId = null;
        $governmentArmsImageId = null;

        if ($this->watermark !== null) {
            $watermarkImageId = $objectId++;
            $watermarkStateId = $objectId++;
            $objects[$watermarkImageId] = $this->imageObject($this->watermark);
            $objects[$watermarkStateId] = '<< /Type /ExtGState /CA 0.08 /ca 0.08 >>';
        }

        if ($this->governmentArms !== null) {
            $governmentArmsImageId = $objectId++;
            $objects[$governmentArmsImageId] = $this->imageObject($this->governmentArms);
        }

        foreach ($pageStreams as $stream) {
            $pageId = $objectId++;
            $contentId = $objectId++;
            $kids[] = $pageId . ' 0 R';
            $resources = '<< /Font << /F1 3 0 R /F2 4 0 R >>';
            $content = $this->watermarkCommand() . $stream;
            $xObjects = [];

            if ($watermarkImageId !== null) {
                $xObjects[] = '/WmLogo ' . $watermarkImageId . ' 0 R';
            }

            if ($governmentArmsImageId !== null) {
                $xObjects[] = '/GovArms ' . $governmentArmsImageId . ' 0 R';
            }

            if ($xObjects) {
                $resources .= ' /XObject << ' . implode(' ', $xObjects) . ' >>';
            }

            if ($watermarkStateId !== null) {
                $resources .= ' /ExtGState << /WmState ' . $watermarkStateId . ' 0 R >>';
            }

            $resources .= ' >>';

            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '
                . self::PAGE_WIDTH . ' ' . self::PAGE_HEIGHT
                . '] /Resources '
                . $resources
                . ' /Contents '
                . $contentId . ' 0 R >>';
            $objects[$contentId] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
        }

        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($id = 1; $id <= count($objects); $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xref . "\n%%EOF";

        return $pdf;
    }

    private function imageObject(array $image): string
    {
        return "<< /Type /XObject /Subtype /Image /Width "
            . (int) $image['width']
            . " /Height "
            . (int) $image['height']
            . " /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length "
            . strlen($image['data'])
            . " >>\nstream\n"
            . $image['data']
            . "\nendstream";
    }

    private function loadWatermark(): ?array
    {
        return $this->loadJpegImage(dirname(__DIR__, 2) . '/public/assets/images/busia-logo.jpg');
    }

    private function loadJpegImage(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }

        $size = @getimagesize($path);
        if (!$size || ($size['mime'] ?? '') !== 'image/jpeg') {
            return null;
        }

        $data = file_get_contents($path);
        if ($data === false) {
            return null;
        }

        return [
            'width' => (int) $size[0],
            'height' => (int) $size[1],
            'data' => $data,
        ];
    }

    private function watermarkCommand(): string
    {
        if ($this->watermark === null) {
            return '';
        }

        $width = self::WATERMARK_WIDTH;
        $height = $width * ((int) $this->watermark['height'] / max(1, (int) $this->watermark['width']));
        $x = (self::PAGE_WIDTH - $width) / 2;
        $y = (self::PAGE_HEIGHT - $height) / 2;

        return sprintf(
            "q /WmState gs %.2F 0 0 %.2F %.2F %.2F cm /WmLogo Do Q\n",
            $width,
            $height,
            $x,
            $y
        );
    }
}
