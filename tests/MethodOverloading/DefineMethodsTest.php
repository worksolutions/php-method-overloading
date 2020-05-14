<?php

namespace MethodOverloading;

use MethodOverloading\TestScaffolding\CallableInvocationCounter;
use PHPUnit\Framework\TestCase;
use SplObjectStorage;

/**
 * @author Maxim Sokolovsky
 */

class DefineMethodsTest extends TestCase
{

    /**
     * @test
     */
    public function usePrimitives(): void
    {
        $invocationCounter = new CallableInvocationCounter();
        SignatureDetector::of(Param::INT, Param::STR)
            ->executeWhen([1, 2], $invocationCounter);

        SignatureDetector::of(Param::INT, Param::INT)
            ->executeWhen([1, 3], $invocationCounter);

        $this->assertEquals(1, $invocationCounter->countOfInvocations(), 'Expected one invocation');
        $this->assertTrue($invocationCounter->isCalledWith(1, 3), 'Expected invocation with args: 1, 3');
        $this->assertFalse($invocationCounter->isCalledWith(1, 2), 'Unexpected invocation with args: 1, 2');
    }

    /**
     * @test
     */
    public function useObjects(): void
    {
        $object = new SplObjectStorage();

        $invocationCounter = new CallableInvocationCounter();

        SignatureDetector::of(Param::instanceOf(SplObjectStorage::class))
            ->executeWhen([$object], $invocationCounter);

        $this->assertEquals(1, $invocationCounter->countOfInvocations(), 'Expected one invocation');
        $this->assertTrue($invocationCounter->isCalledWith($object), 'Expected invocation with args: object');
    }

    /**
     * @test
     */
    public function expectResultOfEvaluation(): void
    {
        $invocationCounter = new CallableInvocationCounter();
        $invocationCounter->willReturn($expectedInvocationResult = 'return string');

        $invocationResult = SignatureDetector::of(Param::STR, Param::OBJ)
            ->executeWhen(['', new SplObjectStorage()], $invocationCounter);

        $this->assertSame($expectedInvocationResult, $invocationResult, 'Unexpected invocation result was occurred');
    }
}
