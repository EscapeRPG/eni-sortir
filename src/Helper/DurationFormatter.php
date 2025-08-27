<?php

namespace App\Helper;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DurationFormatter extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('duration', [$this, 'formatDuration']),
        ];
    }

    public function formatDuration($duration): string
    {
        $days = intdiv($duration, 24 * 60);
        $duration %= 24 * 60;
        $hours = intdiv($duration, 60);
        $minutes = $duration % 60;

        $sentence = [];

        if ($days > 0) {
            $sentence[] = $days . ' jour' . ($days > 1 ? 's' : '');
        }
        if ($hours > 0) {
            $sentence[] = $hours . ' heure' . ($hours > 1 ? 's' : '');
        }
        if ($minutes > 0) {
            $sentence[] = $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        }

        return implode(' ', $sentence);
    }
}