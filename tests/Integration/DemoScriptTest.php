<?php

declare(strict_types=1);

namespace Alcor\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class DemoScriptTest extends TestCase
{
    public function test_the_demo_replays_the_scenario_to_the_expected_final_value(): void
    {
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/../../bin/demo.php') . ' 2>&1', $output, $exitCode);
        $text = implode("\n", $output);

        self::assertSame(0, $exitCode, $text);
        self::assertMatchesRegularExpression('/^System value \(frozen\)\s+\$1,050\.00/m', $text);
        self::assertMatchesRegularExpression('/^Adjustment 5\s+\+\$0\.20\s+Correcting mistake in adjustment #4/m', $text);
        self::assertMatchesRegularExpression('/^Current value\s+\$1,104\.45$/m', $text);
    }
}
