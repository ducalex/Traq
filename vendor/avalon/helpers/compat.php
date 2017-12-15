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