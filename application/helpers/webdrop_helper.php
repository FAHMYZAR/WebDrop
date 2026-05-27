<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('webdrop_slug')) {
    function webdrop_slug($value)
    {
        $value = strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        $value = trim($value, '-');

        return $value !== '' ? $value : 'project';
    }
}

if ( ! function_exists('format_bytes')) {
    function format_bytes($bytes)
    {
        $bytes = (float) $bytes;
        $units = array('B', 'KB', 'MB', 'GB');
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return number_format($bytes, $index === 0 ? 0 : 2) . ' ' . $units[$index];
    }
}

if ( ! function_exists('format_datetime_id')) {
    function format_datetime_id($value)
    {
        if (empty($value)) {
            return '-';
        }

        return date('d M Y H:i', strtotime($value));
    }
}

if ( ! function_exists('project_status_class')) {
    function project_status_class($status)
    {
        switch ($status) {
            case 'published':
                return 'is-success';
            default:
                return 'is-muted';
        }
    }
}
