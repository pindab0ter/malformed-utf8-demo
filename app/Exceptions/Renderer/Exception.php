<?php

declare(strict_types=1);

namespace App\Exceptions\Renderer;

use Illuminate\Foundation\Exceptions\Renderer\Exception as BaseException;
use Illuminate\Foundation\Exceptions\Renderer\Frame;

/**
 * Custom exception renderer that extends Laravel’s base Exception class.
 *
 * This class is functionally identical to {@see BaseException}, with one key difference:
 * it uses {@see ConfigurableFrame} instead of {@see Frame}, enabling configurable
 * vendor frame detection via ‘app.classes_treated_as_from_vendor’.
 *
 * This allows infrastructure classes to be collapsed in stack traces without
 * modifying vendor code or changing the application’s directory structure.
 */
class Exception extends BaseException
{
    /**
     * Get the application's SQL queries.
     *
     * @return array<int, array{connectionName: string, time: float, sql: string}>
     */
    public function applicationQueries(): array
    {
        /** @var array<int, array{connectionName: string, time: float, sql: string, bindings: array<array-key, mixed>}> $queries */
        $queries = $this->listener->queries();

        return array_map(function (array $query) {
            $sql = (string) $query['sql'];

            $sanitizedBindings = sanitize_bindings($query['bindings']);

            foreach ($sanitizedBindings as $binding) {
                $result = match (gettype($binding)) {
                    'integer', 'double' => preg_replace('/\?/', (string) $binding, $sql, 1),
                    'NULL' => preg_replace('/\?/', 'NULL', $sql, 1),
                    default => preg_replace('/\?/', "'{$binding}'", $sql, 1),
                };

                $sql = (string) $result;
            }

            return [
                'connectionName' => $query['connectionName'],
                'time' => $query['time'],
                'sql' => $sql,
            ];
        }, $queries);
    }
}
