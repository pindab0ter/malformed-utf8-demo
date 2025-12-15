<?php

declare(strict_types=1);

use Illuminate\Contracts\View\Factory;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Exceptions\Renderer\Exception as RendererException;
use Illuminate\Foundation\Exceptions\Renderer\Renderer;
use Illuminate\Support\Facades\DB;

test('it sanitizes binary data in database error messages', function () {
    $binaryUuid = hex2bin('deadbeef');

    expect($binaryUuid)->not->toBeNull()
        ->and(mb_check_encoding($binaryUuid, 'UTF-8'))->toBeFalse('Binary UUID is valid UTF-8');

    // Attempt to insert with binary UUID but omit the required 'email' field
    try {
        DB::table('users')->insert([
            'uuid' => $binaryUuid,
            // required fields are omitted
        ]);

        $this->fail('Expected QueryException was not thrown');
    } catch (Throwable $e) {
        $message = $e->getMessage();

        expect(mb_check_encoding($message, 'UTF-8'))->toBeTrue('Exception message is not valid UTF-8')
            ->and($message)->toContain('0xDEADBEEF');
    }
});

test('it sanitizes binary data in exception page query section', function () {
    $binaryUuid = hex2bin('deadbeef');

    expect(mb_check_encoding($binaryUuid, 'UTF-8'))->toBeFalse('Binary UUID is valid UTF-8');

    // Execute a successful query with binary binding
    DB::table('users')->where('uuid', $binaryUuid)->get();

    $renderer = app(Renderer::class);
    $renderedExceptionPage = $renderer->render(new \Illuminate\Http\Request, new Exception('Test exception'));

    expect($renderedExceptionPage)->toContain("select * from \u0022users\u0022 where \u0022uuid\u0022 = \u00270xDEADBEEF\u0027")
        ->and(mb_check_encoding($renderedExceptionPage, 'UTF-8'))->toBeTrue();
});

test('it treats custom connection as vendor class in exception frames', function () {
    $binaryUuid = hex2bin('deadbeef');

    // Get the original factory
    $originalFactory = app(Factory::class);

    // Create a wrapper that intercepts make() calls
    $wrappedFactory = new class($originalFactory) extends \Illuminate\View\Factory
    {
        public RendererException $capturedException;

        public function __construct($original)
        {
            parent::__construct($original->engines, $original->finder, $original->events);
        }

        public function make($view, $data = [], $mergeData = [])
        {
            // Intercept make() calls to capture the exception object
            if (isset($data['exception'])) {
                $this->capturedException = $data['exception'];
            }

            return parent::make($view, $data, $mergeData);
        }
    };

    // Replace the factory in the container
    app()->instance(Factory::class, $wrappedFactory);

    try {
        // Trigger QueryException through custom connection
        DB::table('users')->insert([
            'uuid' => $binaryUuid,
            // required fields are omitted
        ]);

        $this->fail('Expected QueryException was not thrown');
    } catch (Throwable $e) {
        app(Renderer::class)->render(new \Illuminate\Http\Request, $e);

        $firstGroup = $wrappedFactory->capturedException->frameGroups()[0];
        $firstFrame = $firstGroup['frames'][0];

        expect(is_subclass_of($firstFrame->class(), ConnectionInterface::class))->toBeTrue('First frame should be a Connection class')
            ->and($firstFrame->isFromVendor())->toBeTrue('First frame should be marked as vendor')
            ->and($firstFrame->isMain())->toBeFalse('First frame should not be marked as main')
            ->and($firstGroup['is_vendor'])->toBeTrue('First frame group should be marked as vendor');
    }
});
