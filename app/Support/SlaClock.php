<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class SlaClock
{
    /**
     * Jours ouvrés SIBEA : lundi (1) au samedi (6). Dimanche (0) exclu.
     */
    public static function isWorkingDay(CarbonInterface $date): bool
    {
        return $date->dayOfWeek !== Carbon::SUNDAY;
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

            // Saute les dimanches d'un coup.
            while (! self::isWorkingDay($deadline)) {
                $deadline = $deadline->copy()->addDay()->startOfDay();
            }
        }

        return $deadline;
    }

    /**
     * Minutes ouvrées entre deux instants (dimanches exclus).
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
