<?php

declare(strict_types=1);

if (! function_exists('sanitize_bindings')) {
    /**
     * Sanitize bindings to replace non-UTF-8 binary data with readable hex representation.
     *
     * @param  array<array-key, mixed>  $bindings
     * @return array<array-key, mixed>
     */
    function sanitize_bindings(array $bindings): array
    {
        return array_map(function ($binding) {
            if (! is_string($binding) || mb_check_encoding($binding, 'UTF-8')) {
                return $binding;
            }

            return '0x'.mb_strtoupper(bin2hex($binding));
        }, $bindings);
    }
}
