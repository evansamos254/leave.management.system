<?php

class HolidaySyncService
{
    private const COUNTRY_CODE = 'KE';
    private const API_URL = 'https://date.nager.at/api/v3/PublicHolidays/%d/%s';

    public static function syncKenyaPublicHolidays(int $year): array
    {
        if ($year < 2000 || $year > 2100) {
            throw new InvalidArgumentException('Please choose a valid holiday year.');
        }

        $holidays = self::mergeHolidays(
            self::fetchPublicHolidays($year),
            self::kenyaCalendarHolidays($year)
        );
        $inserted = 0;
        $updated = 0;

        foreach ($holidays as $holiday) {
            $date = (string) ($holiday['date'] ?? '');
            $name = trim((string) ($holiday['localName'] ?? $holiday['name'] ?? ''));

            if (!self::isValidHolidayDate($date, $year) || $name === '') {
                continue;
            }

            if (self::saveHoliday($name, $date)) {
                $inserted++;
            } else {
                $updated++;
            }
        }

        return [
            'year' => $year,
            'total' => $inserted + $updated,
            'inserted' => $inserted,
            'updated' => $updated,
        ];
    }

    private static function fetchPublicHolidays(int $year): array
    {
        $url = sprintf(self::API_URL, $year, self::COUNTRY_CODE);
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Accept: application/json\r\n",
                'timeout' => 15,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false || trim($response) === '') {
            throw new RuntimeException('Could not connect to the public holiday service.');
        }

        $statusLine = $http_response_header[0] ?? '';
        if (!str_contains($statusLine, '200')) {
            throw new RuntimeException('The public holiday service did not return holidays for that year.');
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new RuntimeException('The public holiday service returned an invalid response.');
        }

        return $data;
    }

    private static function mergeHolidays(array ...$holidayGroups): array
    {
        $merged = [];

        foreach ($holidayGroups as $holidayGroup) {
            foreach ($holidayGroup as $holiday) {
                $date = (string) ($holiday['date'] ?? '');
                if ($date === '') {
                    continue;
                }

                $merged[$date] = $holiday;
            }
        }

        ksort($merged);

        return array_values($merged);
    }

    private static function kenyaCalendarHolidays(int $year): array
    {
        $holidays = [
            self::holiday($year . '-01-01', "New Year's Day"),
            self::holiday($year . '-05-01', 'Labour Day'),
            self::holiday($year . '-06-01', 'Madaraka Day'),
            self::holiday($year . '-10-10', 'Mazingira Day'),
            self::holiday($year . '-10-20', 'Mashujaa Day'),
            self::holiday($year . '-12-12', 'Jamhuri Day'),
            self::holiday($year . '-12-25', 'Christmas Day'),
            self::holiday($year . '-12-26', 'Boxing Day'),
        ];

        $easterSunday = self::easterSunday($year);
        if ($easterSunday !== null) {
            $holidays[] = self::holiday(self::relativeDate($easterSunday, -2), 'Good Friday');
            $holidays[] = self::holiday(self::relativeDate($easterSunday, 1), 'Easter Monday');
        }

        foreach (self::knownLunarHolidays($year) as $date => $name) {
            $holidays[] = self::holiday($date, $name);
        }

        return $holidays;
    }

    private static function knownLunarHolidays(int $year): array
    {
        return [
            2026 => [
                '2026-03-20' => 'Idd-ul-Fitr',
                '2026-05-27' => 'Eid al-Adha',
            ],
        ][$year] ?? [];
    }

    private static function holiday(string $date, string $name): array
    {
        return [
            'date' => $date,
            'localName' => $name,
            'name' => $name,
        ];
    }

    private static function easterSunday(int $year): ?string
    {
        if (!function_exists('easter_date')) {
            return null;
        }

        return date('Y-m-d', easter_date($year));
    }

    private static function relativeDate(string $date, int $days): string
    {
        $parsed = new DateTime($date);
        $parsed->modify(($days >= 0 ? '+' : '') . $days . ' days');

        return $parsed->format('Y-m-d');
    }

    private static function saveHoliday(string $name, string $date): bool
    {
        $lookup = db()->prepare('SELECT id FROM holidays WHERE holiday_date = ? LIMIT 1');
        $lookup->execute([$date]);
        $existingId = $lookup->fetchColumn();

        if ($existingId) {
            $stmt = db()->prepare('UPDATE holidays SET name = ? WHERE id = ?');
            $stmt->execute([$name, (int) $existingId]);

            return false;
        }

        $stmt = db()->prepare('INSERT INTO holidays (name, holiday_date) VALUES (?, ?)');
        $stmt->execute([$name, $date]);

        return true;
    }

    private static function isValidHolidayDate(string $date, int $year): bool
    {
        $parsed = DateTime::createFromFormat('Y-m-d', $date);

        return $parsed
            && $parsed->format('Y-m-d') === $date
            && (int) $parsed->format('Y') === $year;
    }
}
