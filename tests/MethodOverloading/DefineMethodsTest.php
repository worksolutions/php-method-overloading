<?php

namespace MethodOverloading;

use MethodOverloading\Constraints\InvocationCounterIsCalledTimes;
use MethodOverloading\Constraints\InvocationCounterWasCalledWithArgs;
use MethodOverloading\Constraints\InvocationCounterWasNotCalledWithArgs;
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

        $this->assertThat($invocationCounter, InvocationCounterIsCalledTimes::create(1));
        $this->assertThat($invocationCounter, InvocationCounterWasCalledWithArgs::create(1, 3));
        $this->assertThat($invocationCounter, InvocationCounterWasNotCalledWithArgs::create(1, 2));
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

        $this->assertThat($invocationCounter, InvocationCounterIsCalledTimes::create(1));
        $this->assertThat($invocationCounter, InvocationCounterWasCalledWithArgs::create($object));
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

    /**
     * @test
     */
    public function nullParamsUsing(): void
    {
        $invocationCounter = new CallableInvocationCounter();
        $detector = SignatureDetector::of(Param::INT, Param::NULL, Param::INT);

        $detector
            ->executeWhen([1, null, 2], $invocationCounter);

        $detector
            ->executeWhen([1, 2, 3], $invocationCounter);

        $this->assertThat($invocationCounter, InvocationCounterIsCalledTimes::create(1));
        $this->assertThat($invocationCounter, InvocationCounterWasCalledWithArgs::create(1, null, 2));
    }

    /**
     * @test
     */
    public function mixedParamsUsing():void
    {
        $invocationCounter = new CallableInvocationCounter();
        $detector = SignatureDetector::of(Param::INT, Param::MIXED, Param::INT);

        $detector
            ->executeWhen([1, null, 2], $invocationCounter);

        $detector
            ->executeWhen([1, 2, 3], $invocationCounter);

        $detector
            ->executeWhen([null, null, 1], $invocationCounter);

        $this->assertThat($invocationCounter, InvocationCounterIsCalledTimes::create(2));
        $this->assertThat($invocationCounter, InvocationCounterWasCalledWithArgs::create(1, null, 2));
        $this->assertThat($invocationCounter, InvocationCounterWasCalledWithArgs::create(1, 2, 3));
    }

    /**
     * @test
     */
    public function unexpectedDefinitionType(): void
    {
        $this->expectException(CompileException::class);
        SignatureDetector::of(Param::INT, 'tested', Param::INT);
    }
}
