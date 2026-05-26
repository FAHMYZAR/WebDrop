<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('auth_user')) {
    function auth_user()
    {
        $CI =& get_instance();

        if (method_exists($CI, 'user')) {
            return $CI->user();
        }

        return null;
    }
}

if ( ! function_exists('is_authenticated')) {
    function is_authenticated()
    {
        return auth_user() !== null;
    }
}
