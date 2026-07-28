<?php
/**
 * Global helper functions for URTH MVC Framework
 */

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string {
        static $baseUrl = null;
        if ($baseUrl === null) {
            $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
            $baseUrl = rtrim(str_replace('\\', '/', $scriptDir), '/');
        }
        $path = ltrim($path, '/');
        return $path === '' ? ($baseUrl === '' ? '/' : $baseUrl) : $baseUrl . '/' . $path;
    }
}
