<?php

declare(strict_types=1);

namespace App\Exceptions\Renderer;

use Composer\Autoload\ClassLoader;
use Illuminate\Foundation\Bootstrap\HandleExceptions;
use Illuminate\Foundation\Exceptions\Renderer\Exception as BaseException;
use Illuminate\Foundation\Exceptions\Renderer\Frame;
use Illuminate\Support\Collection;
use Override;

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
     * Get the exception’s frames.
     *
     * This is an exact copy of {@see parent::frames()} except for using
     * {@see ConfigurableFrame} instead of {@see Frame}.
     *
     * @return Collection<int, Frame>
     */
    #[Override]
    public function frames(): Collection
    {
        return once(function () {
            $classMap = array_map(
                fn (string $path) => (string) realpath($path),
                array_values(ClassLoader::getRegisteredLoaders())[0]->getClassMap()
            );

            $trace = array_values(array_filter(
                $this->exception->getTrace(),
                fn (array $trace) => isset($trace['file']),
            ));

            if (($trace[1]['class'] ?? '') === HandleExceptions::class) {
                array_shift($trace);
                array_shift($trace);
            }

            $frames = [];
            $previousFrame = null;

            foreach (array_reverse($trace) as $frameData) {
                $frame = new ConfigurableFrame($this->exception, $classMap, $frameData, $this->basePath, $previousFrame);
                $frames[] = $frame;
                $previousFrame = $frame;
            }

            $frames = array_reverse($frames);

            foreach ($frames as $frame) {
                if (! $frame->isFromVendor()) {
                    $frame->markAsMain();

                    break;
                }
            }

            return new Collection($frames);
        });
    }

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
