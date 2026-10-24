<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskBug extends Model
{
    protected $guarded = [];

    /**
     * Get the steps to reproduce as an array of step strings.
     * Supports both new JSON array format and legacy newline-separated text.
     */
    public function getStepsToReproduceAttribute($value)
    {
        if (empty($value)) {
            return [];
        }

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_values(array_filter(array_map(function ($s) {
                return is_array($s) ? ($s['text'] ?? '') : (string)$s;
            }, $decoded), fn ($s) => trim($s) !== ''));
        }

        // Parse legacy newline-separated string
        $lines = preg_split('/\r\n|\r|\n/', (string)$value);
        $steps = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }
            // Strip leading list symbols/numbers like "1. ", "2) ", "- "
            $clean = preg_replace('/^(\d+[\.\)]\s*|[-*•]\s*)/u', '', $trimmed);
            $steps[] = $clean !== '' ? $clean : $trimmed;
        }

        return $steps;
    }

    /**
     * Set the steps to reproduce, ensuring clean JSON storage.
     */
    public function setStepsToReproduceAttribute($value)
    {
        if (is_array($value)) {
            $cleaned = array_values(array_filter(array_map(function ($s) {
                return is_array($s) ? ($s['text'] ?? '') : (string)$s;
            }, $value), fn ($s) => trim($s) !== ''));

            $this->attributes['steps_to_reproduce'] = !empty($cleaned) ? json_encode($cleaned) : null;
        } elseif (is_string($value)) {
            $trimmed = trim($value);
            if (empty($trimmed)) {
                $this->attributes['steps_to_reproduce'] = null;
                return;
            }

            // Check if string is already JSON encoded array
            if (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) {
                $decoded = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $cleaned = array_values(array_filter(array_map(function ($s) {
                        return is_array($s) ? ($s['text'] ?? '') : (string)$s;
                    }, $decoded), fn ($s) => trim($s) !== ''));
                    $this->attributes['steps_to_reproduce'] = !empty($cleaned) ? json_encode($cleaned) : null;
                    return;
                }
            }

            // If it's a newline separated string, normalize it to JSON array
            $lines = preg_split('/\r\n|\r|\n/', $trimmed);
            $steps = [];
            foreach ($lines as $line) {
                $l = trim($line);
                if ($l === '') continue;
                $clean = preg_replace('/^(\d+[\.\)]\s*|[-*•]\s*)/u', '', $l);
                $steps[] = $clean !== '' ? $clean : $l;
            }

            $this->attributes['steps_to_reproduce'] = !empty($steps) ? json_encode($steps) : null;
        } else {
            $this->attributes['steps_to_reproduce'] = null;
        }
    }

    public function projectTask()
    {
        return $this->belongsTo(ProjectTask::class);
    }

    public function testCase()
    {
        return $this->belongsTo(TestCase::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
