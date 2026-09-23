<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class SlaClock
{
    /**
     * Cache des jours fériés indexés par année puis par date Y-m-d.
     *
     * @var array<int, array<string, true>>
     */
    private static array $holidaysByYear = [];

    /**
     * Jour ouvré SIBEA : lundi–samedi, hors dimanche et jours fériés ivoiriens.
     */
    public static function isWorkingDay(CarbonInterface $date): bool
    {
        if ($date->dayOfWeek === Carbon::SUNDAY) {
            return false;
        }

        return ! self::isHoliday($date);
    }

    /**
     * Jour férié officiel (fixes + chrétiens mobiles + extras config, ex. Korité / Tabaski).
     */
    public static function isHoliday(CarbonInterface $date): bool
    {
        return isset(self::holidaysForYear($date->year)[$date->format('Y-m-d')]);
    }

    /**
     * Dates fériées d'une année civile, clés Y-m-d.
     *
     * @return array<string, true>
     */
    public static function holidaysForYear(int $year): array
    {
        if (isset(self::$holidaysByYear[$year])) {
            return self::$holidaysByYear[$year];
        }

        $dates = [];

        foreach ([
            sprintf('%04d-01-01', $year), // Nouvel An
            sprintf('%04d-05-01', $year), // Fête du Travail
            sprintf('%04d-08-07', $year), // Fête nationale
            sprintf('%04d-08-15', $year), // Assomption
            sprintf('%04d-11-01', $year), // Toussaint
            sprintf('%04d-11-15', $year), // Journée nationale de la Paix
            sprintf('%04d-12-25', $year), // Noël
        ] as $fixed) {
            $dates[$fixed] = true;
        }

        // Lundi de Pâques (+1), Ascension (+39), lundi de Pentecôte (+50).
        $easter = Carbon::createFromTimestamp(easter_date($year))->startOfDay();

        foreach ([1, 39, 50] as $offsetDays) {
            $dates[$easter->copy()->addDays($offsetDays)->format('Y-m-d')] = true;
        }

        foreach (config('leads.holidays', []) as $extra) {
            if (is_string($extra) && str_starts_with($extra, sprintf('%04d-', $year))) {
                $dates[$extra] = true;
            }
        }

        return self::$holidaysByYear[$year] = $dates;
    }

    /**
     * Remet le cache (tests / changement de config en runtime).
     */
    public static function flushHolidayCache(): void
    {
        self::$holidaysByYear = [];
    }

    /**
     * Deadline = $from + $hours en ne comptant que les jours ouvrés.
     * Ex. samedi 10h + 24h → lundi 10h (dimanche sauté).
     */
    public static function addWorkingHours(CarbonInterface $from, int $hours = 24): Carbon
    {
        $deadline = Carbon::parse($from->toDateTimeString());
        $remainingMinutes = $hours * 60;

        while ($remainingMinutes > 0) {
            if (! self::isWorkingDay($deadline)) {
                $deadline = $deadline->copy()->addDay()->startOfDay();

                continue;
            }

            $startOfNextDay = $deadline->copy()->addDay()->startOfDay();
            $available = (int) $deadline->diffInMinutes($startOfNextDay);

            // Journée entière disponible : on avance par pas de 24h max.
            if ($available >= $remainingMinutes) {
                return $deadline->copy()->addMinutes($remainingMinutes);
            }

            if ($available > 0) {
                $remainingMinutes -= $available;
                $deadline = $deadline->copy()->addDay()->startOfDay();
            } else {
                $deadline = $deadline->copy()->addDay()->startOfDay();
            }

            // Saute dimanches et fériés d'un coup.
            while (! self::isWorkingDay($deadline)) {
                $deadline = $deadline->copy()->addDay()->startOfDay();
            }
        }

        return $deadline;
    }

    /**
     * Minutes ouvrées entre deux instants (dimanches et fériés exclus).
     */
    public static function workingMinutesBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        $fromCarbon = Carbon::parse($from->toDateTimeString());
        $toCarbon = Carbon::parse($to->toDateTimeString());

        if ($toCarbon->lessThanOrEqualTo($fromCarbon)) {
            return 0;
        }

        $total = 0;
        $cursor = $fromCarbon->copy();

        while ($cursor->lessThan($toCarbon)) {
            if (! self::isWorkingDay($cursor)) {
                $cursor = $cursor->copy()->addDay()->startOfDay();

                continue;
            }

            $startOfNextDay = $cursor->copy()->addDay()->startOfDay();
            $segmentEnd = $toCarbon->lessThan($startOfNextDay) ? $toCarbon->copy() : $startOfNextDay;
            $total += (int) $cursor->diffInMinutes($segmentEnd);
            $cursor = $segmentEnd->copy();
        }

        return $total;
    }
}
