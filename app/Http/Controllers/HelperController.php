<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

class HelperController extends Controller
{
    /**
     * Converts each line of the given text into a paragraph element.
     *
     * This function splits the input text into individual lines and wraps each line
     * in a paragraph (`<p>`) tag. If a string of classes is provided, it adds them
     * as the class attribute of the paragraph tags.
     *
     * @param  string  $text  The text to be converted into paragraphs.
     * @param  string|null  $classes  Optional string of classes to be added to each paragraph tag.
     * @return string The converted text with each line wrapped in a paragraph tag.
     *                If classes are provided, each paragraph tag will include them.
     */
    public static function linesToParagraphs(string $text, ?string $classes = null): string
    {
        $lines = explode("\n", trim($text));
        $paragraphs = array_map(function ($line) use ($classes) {
            $classAttribute = $classes ? " class='".e($classes)."'" : '';

            return '<p'.$classAttribute.'>'.e($line).'</p>';
        }, $lines);

        return implode('', $paragraphs);
    }

    /**
     * Returns the end date of a given period.
     *
     * This function calculates the end date of a period based on the latest date
     * and the number of days from today. If the calculated end date is greater
     * than the latest date, the latest date is returned instead.
     *
     * @param  string  $latestDate  The latest date of the period.
     * @param  int  $numDaysFromToday  The number of days from today to calculate the end date.
     * @return Carbon The end date of the period.
     */
    public static function getEndDate($latestDate, $numDaysFromToday): Carbon
    {
        $latestDate = Carbon::parse($latestDate);
        $end = now()->addDays($numDaysFromToday);

        return $end->greaterThan($latestDate) ? $latestDate : $end;
    }

    /**
     * Formats a JSON string into a readable HTML structure.
     *
     * This method takes an escaped JSON string and converts it into a hierarchical HTML
     * structure with proper indentation and formatting. The output includes:
     * - Indented elements using margin-left
     * - Bold keys for associative arrays
     * - Escaped HTML characters for security
     * - Nested array/object support
     *
     * @param  string  $json  The JSON string to be formatted
     * @return string HTML formatted representation of the JSON
     * @throws \JsonException If JSON decoding fails
     */
    public static function prettyPrintJson(string $json): string
    {
        // Decode JSON with error handling
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return '<div class="error">Invalid JSON format</div>';
        }

        // Recursive function to process the decoded JSON
        $buildHtml = function ($data, int $indent = 0) use (&$buildHtml): string {
            if ($data === null) {
                return '<div style="margin-left:' . ($indent * 20) . 'px;">null</div>';
            }

            if (!is_array($data)) {
                return '<div style="margin-left:' . ($indent * 20) . 'px;">' . 
                    htmlspecialchars((string)$data) . '</div>';
            }

            $html = '';
            $indentStyle = 'margin-left:' . ($indent * 20) . 'px;';
            $isAssoc = array_keys($data) !== range(0, count($data) - 1);

            foreach ($data as $key => $value) {
                $html .= "<div style='{$indentStyle}'>";
                
                if ($isAssoc) {
                    $html .= '<strong>' . htmlspecialchars((string)$key) . ':</strong> ';
                }

                if (is_array($value)) {
                    $html .= $isAssoc ? '</div>' : '';
                    $html .= $buildHtml($value, $indent + 1);
                    $html .= $isAssoc ? '' : '</div>';
                } else {
                    $html .= htmlspecialchars((string)$value) . '</div>';
                }
            }

            return $html;
        };

        return $buildHtml($data);
    }
}
