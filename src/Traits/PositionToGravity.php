<?php

declare(strict_types=1);

namespace Intervention\Image\Drivers\Vips\Traits;

trait PositionToGravity
{
    /**
     * Convert position string to gravity string.
    */
    public static function positionToGravity(string $position): string
    {
        return match (strtolower($position)) {
            'top', 'top-center', 'top-middle', 'center-top', 'middle-top' => 'north',
            'top-right', 'right-top' => 'north-east',
            'left', 'left-center', 'left-middle', 'center-left', 'middle-left' => 'west',
            'right', 'right-center', 'right-middle', 'center-right', 'middle-right' => 'east',
            'bottom-left', 'left-bottom' => 'south-west',
            'bottom', 'bottom-center', 'bottom-middle', 'center-bottom', 'middle-bottom' => 'south',
            'bottom-right', 'right-bottom' => 'south-east',
            'top-left', 'left-top' => 'north-west',
            default => 'centre'
        };
    }
}
