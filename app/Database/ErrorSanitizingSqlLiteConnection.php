<?php

declare(strict_types=1);

namespace App\Database;

use Closure;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\UniqueConstraintViolationException;

class ErrorSanitizingSqlLiteConnection extends SQLiteConnection
{
    /**
     * Run a SQL statement.
     *
     * Overrides the parent to sanitize bindings before creating QueryException.
     *
     * @param  string  $query
     * @param  array<array-key, mixed>  $bindings
     *
     * @throws QueryException
     */
    protected function runQueryCallback($query, $bindings, Closure $callback): mixed
    {
        // To execute the statement, we’ll simply call the callback, which will actually
        // run the SQL against the PDO connection. Then we can calculate the time it
        // took to execute and log the query SQL, bindings and time in our memory.
        try {
            return $callback($query, $bindings);
        }

        // If an exception occurs when attempting to run a query, we’ll format the error
        // message to include the bindings with SQL, which will make this exception a
        // lot more helpful to the developer instead of just the database’s errors.
        catch (Exception $e) {
            // Sanitize bindings before creating the exception
            $sanitizedBindings = sanitize_bindings($this->prepareBindings($bindings));

            if ($this->isUniqueConstraintError($e)) {
                throw new UniqueConstraintViolationException(
                    connectionName: (string) $this->getName(),
                    sql: $query,
                    bindings: $sanitizedBindings,
                    previous: $e
                );
            }

            throw new QueryException(
                connectionName: (string) $this->getName(),
                sql: $query,
                bindings: $sanitizedBindings,
                previous: $e
            );
        }
    }
}
