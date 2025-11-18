<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Validator;

class PercentageHelper
{
    /**
     * Calculate percentage
     *
     * @param float|int $current
     * @param float|int $previous
     * @return float|int
     */

    public static function calculate($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }
}
