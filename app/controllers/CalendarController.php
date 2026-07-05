<?php

class CalendarController
{
    public function index(): void
    {
        require_role(['admin', 'supervisor', 'hr', 'director', 'chief_officer']);

        $user = current_user();
        $employeeId = !empty($user['employee_id']) ? (int) $user['employee_id'] : null;
        $month = trim($_GET['month'] ?? date('Y-m'));

        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        try {
            $firstOfMonth = new DateTime($month . '-01');
        } catch (Throwable) {
            $firstOfMonth = new DateTime(date('Y-m-01'));
            $month = $firstOfMonth->format('Y-m');
        }

        $lastOfMonth = (clone $firstOfMonth)->modify('last day of this month');
        $gridStart = (clone $firstOfMonth)->modify('-' . ((int) $firstOfMonth->format('N') - 1) . ' days');
        $gridEnd = (clone $lastOfMonth)->modify('+' . (7 - (int) $lastOfMonth->format('N')) . ' days');

        $calendarEvents = LeaveRequest::approvedBetween(
            $gridStart->format('Y-m-d'),
            $gridEnd->format('Y-m-d'),
            $user['role'],
            $employeeId
        );
        $monthEvents = LeaveRequest::approvedBetween(
            $firstOfMonth->format('Y-m-d'),
            $lastOfMonth->format('Y-m-d'),
            $user['role'],
            $employeeId
        );

        $eventsByDate = [];
        foreach ($calendarEvents as $event) {
            $start = new DateTime(max($event['start_date'], $gridStart->format('Y-m-d')));
            $end = new DateTime(min($event['end_date'], $gridEnd->format('Y-m-d')));

            while ($start <= $end) {
                $date = $start->format('Y-m-d');
                $eventsByDate[$date][] = $event;
                $start->modify('+1 day');
            }
        }

        $weeks = [];
        $cursor = clone $gridStart;
        while ($cursor <= $gridEnd) {
            $week = [];
            for ($day = 0; $day < 7; $day++) {
                $date = $cursor->format('Y-m-d');
                $week[] = [
                    'date' => $date,
                    'day' => $cursor->format('j'),
                    'in_month' => $cursor->format('Y-m') === $month,
                    'is_today' => $date === date('Y-m-d'),
                    'events' => $eventsByDate[$date] ?? [],
                ];
                $cursor->modify('+1 day');
            }
            $weeks[] = $week;
        }

        view('leave/calendar', [
            'title' => 'Leave Calendar',
            'month' => $month,
            'monthLabel' => $firstOfMonth->format('F Y'),
            'previousMonth' => (clone $firstOfMonth)->modify('-1 month')->format('Y-m'),
            'nextMonth' => (clone $firstOfMonth)->modify('+1 month')->format('Y-m'),
            'weeks' => $weeks,
            'events' => $monthEvents,
            'today' => date('Y-m-d'),
        ]);
    }
}
