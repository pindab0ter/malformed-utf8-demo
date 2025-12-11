<?php

declare(strict_types=1);

namespace App\Exceptions\Renderer;

use Illuminate\Foundation\Exceptions\Renderer\Renderer as BaseRenderer;
use Illuminate\Http\Request;
use Throwable;

/**
 * Custom renderer that extends Laravel’s base Renderer class.
 *
 * This class is functionally identical to {@see BaseRenderer}, with one key difference:
 * it uses {@see Exception} (which in turn uses {@see ConfigurableFrame}),
 * enabling configurable vendor frame detection via 'app.classes_treated_as_from_vendor'.
 *
 * This allows infrastructure classes to be collapsed in rendered exception pages
 * without modifying vendor code or changing the application’s directory structure.
 */
class Renderer extends BaseRenderer
{
    /**
     * Render the given exception as an HTML string.
     *
     * This is an exact copy of {@see parent::render()} except for using
     * {@see Exception} instead of {@see Exception}.
     */
    public function render(Request $request, Throwable $throwable): string
    {
        $flattenException = $this->bladeMapper->map(
            $this->htmlErrorRenderer->render($throwable),
        );

        $exception = new Exception($flattenException, $request, $this->listener, $this->basePath);

        $exceptionAsMarkdown = $this->viewFactory->make('laravel-exceptions-renderer::markdown', [
            'exception' => $exception,
        ])->render();

        return $this->viewFactory->make('laravel-exceptions-renderer::show', [
            'exception' => $exception,
            'exceptionAsMarkdown' => $exceptionAsMarkdown,
        ])->render();
    }
}
