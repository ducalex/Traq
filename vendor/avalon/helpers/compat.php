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
    function array_column($input, $column_key) {
        return array_map(function($item) use($column_key){
            return is_object($item) ? $item->$column_key : $item[$column_key];}, 
            $input
        );
    }
}
