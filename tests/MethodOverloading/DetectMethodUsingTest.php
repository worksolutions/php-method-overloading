<?php
/**
 * @author Maxim Sokolovsky
 */

namespace MethodOverloading;

use PHPUnit\Framework\TestCase;

class DetectMethodUsingTest extends TestCase
{
    /**
     * @test
     */
    public function useWithPrimitives(): void
    {
        $detector = SignatureDetector::of(Param::INT, Param::FLOAT);

        $expectedTrue = $detector
            ->detect([1, 2.0]);

        $expectedFalse = $detector
            ->detect([1, 2]);

        $this->assertTrue($expectedTrue, 'Detection of: 1, 2.0 is expected');
        $this->assertFalse($expectedFalse, 'Detection of: 1, 2.0 is unexpected');
    }
}
