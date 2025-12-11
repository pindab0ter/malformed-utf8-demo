<?php

declare(strict_types=1);

namespace App\Exceptions\Renderer;

use Illuminate\Foundation\Exceptions\Renderer\Frame as BaseFrame;
use Override;

use function Illuminate\Filesystem\join_paths;

/**
 * Custom frame that extends Laravel's base Frame class.
 *
 * This class is functionally identical to {@see BaseFrame}, with one key difference:
 * it allows configuring additional classes to be treated as vendor code via the
 * 'app.classes_treated_as_from_vendor' config key.
 *
 * Infrastructure classes listed in the config will be collapsed in rendered
 * exception pages, just like actual vendor code, cleaning up stack traces.
 */
class ConfigurableFrame extends BaseFrame
{
    /**
     * Determine if the frame is from the vendor directory or is a class that should be treated as from a vendor to
     * clean up the rendered stack trace.
     *
     * Extends parent behavior to also check against `config('app.classes_treated_as_from_vendor')`.
     */
    #[Override]
    public function isFromVendor(): bool
    {
        return ! str_starts_with($this->frame['file'], $this->basePath)
            || str_starts_with($this->frame['file'], join_paths($this->basePath, 'vendor'))
            || array_any(
                config('app.classes_treated_as_from_vendor', []),
                fn ($ignored) => $this->class() === $ignored || ($this->frame['class'] ?? null) === $ignored
            );
    }
}
