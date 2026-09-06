<?php

declare(strict_types=1);

namespace App\Services;

class DateParser
{
    private const DATE_FORMATS = [
        'Y-m-d',      // 2024-01-15 (ISO)
        'd/m/Y',      // 15/01/2024 (UK/European)
        'm/d/Y',      // 01/15/2024 (US)
        'd.m.Y',      // 15.01.2024 (German)
        'd-M-Y',      // 15-Jan-2024 (Common)
        'M d, Y',     // Jan 15, 2024 (US Written)
        'd M Y',      // 15 Jan 2024 (UK Written)
        'Ymd',         // 20240115 (Compact)
    ];

    public function parse(string $dateString): ?\DateTimeImmutable
    {
        $dateString = trim($dateString);
        
        if (empty($dateString)) {
            return null;
        }

        // Try each format in order
        foreach (self::DATE_FORMATS as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $dateString);
            
            if ($date !== false) {
                // Validate the date is actually valid (e.g., not Feb 30)
                if ($this->isValidDate($date, $format, $dateString)) {
                    return $date;
                }
            }
        }

        // Try strtotime as last resort
        $timestamp = strtotime($dateString);
        if ($timestamp !== false) {
            $date = \DateTimeImmutable::createFromFormat('U', (string)$timestamp);
            if ($date !== false) {
                return $date;
            }
        }

        return null;
    }

    private function isValidDate(\DateTimeImmutable $date, string $format, string $originalString): bool
    {
        // Check if the formatted date matches the original
        $formatted = $date->format($format);
        
        // Normalize for comparison (handle different separators)
        $normalizedOriginal = preg_replace('/[\s\-\.\/]+/', $format[0], $originalString);
        $normalizedFormatted = preg_replace('/[\s\-\.\/]+/', $format[0], $formatted);
        
        return $normalizedOriginal === $normalizedFormatted;
    }

    public function detectFormat(string $dateString): ?string
    {
        $dateString = trim($dateString);
        
        foreach (self::DATE_FORMATS as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $dateString);
            if ($date !== false && $this->isValidDate($date, $format, $dateString)) {
                return $format;
            }
        }
        
        return null;
    }
}
