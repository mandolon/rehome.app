<?php

namespace App\Traits;

trait HasApiDateFormat
{
    /**
     * Prepare a date for array / JSON serialization.
     * Returns ISO-8601 format for consistent API responses
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->toISOString(); // 2025-06-21T17:30:00.000Z
    }
}
