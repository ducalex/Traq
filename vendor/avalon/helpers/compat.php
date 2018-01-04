<?php
if (!function_exists('password_verify'))  {
    function password_verify($password, $hash) {
        return crypt($password, $hash) === $hash;
    }
}

if (!function_exists('password_hash'))  {
    function password_hash($password, $hash) {
        return crypt($password, '$2a$10$' . random_hash() . '$');
    }
}

if (!function_exists('array_column')) {
    function array_column($input, $column_key, $index_key = null) {
        return array_get_column($input, $column_key, $index_key);
    }
}
