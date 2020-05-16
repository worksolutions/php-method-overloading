<?php

namespace WS\Utils\MethodOverloading;

use IteratorAggregate;
use WS\Utils\MethodOverloading\Constraints\InvocationCounterIsCalledTimes;
use WS\Utils\MethodOverloading\Constraints\InvocationCounterWasCalledWithArgs;
use WS\Utils\MethodOverloading\Constraints\InvocationCounterWasNotCalledWithArgs;
use WS\Utils\MethodOverloading\TestScaffolding\CallableInvocationCounter;
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

    /**
     * @test
     */
    public function useVariableNumberOfParameters(): void
    {
        $detector = SignatureDetector::of(Param::INT, Param::VARIABLE_NUMBERS);

        $invocationCounter = new CallableInvocationCounter();

        $detector
            ->executeWhen([1, '2', 3], $invocationCounter);

        $detector
            ->executeWhen([1], $invocationCounter);

        $detector->executeWhen(['0', 1], $invocationCounter);

        $this->assertThat($invocationCounter, InvocationCounterIsCalledTimes::create(2));
        $this->assertThat($invocationCounter, InvocationCounterWasCalledWithArgs::create(1, '2', 3));
        $this->assertThat($invocationCounter, InvocationCounterWasCalledWithArgs::create(1));
    }

    /**
     * @test
     */
    public function defineWrongVariableNumberOfParameters(): void
    {
        $this->expectException(CompileException::class);
        SignatureDetector::of(Param::INT, Param::VARIABLE_NUMBERS, Param::INT);
    }

    /**
     * @test
     */
    public function defineIterableParameter(): void
    {
        $detector = SignatureDetector::of(Param::INT, Param::ITERABLE, Param::INT);

        $invocationCounter = new CallableInvocationCounter();

        $detector
            ->executeWhen([1, [], 1], $invocationCounter);

        $iterable = new class implements IteratorAggregate {
            public function getIterator(): array
            {
                return [];
            }
        };

        $detector
            ->executeWhen([1, $iterable, 3], $invocationCounter);

        $detector
            ->executeWhen([1, null, 2], $invocationCounter);

        $this->assertThat($invocationCounter, InvocationCounterIsCalledTimes::create(2));
        $this->assertThat($invocationCounter, InvocationCounterWasCalledWithArgs::create(1, [], 1));
        $this->assertThat($invocationCounter, InvocationCounterWasCalledWithArgs::create(1, $iterable, 3));
    }
}
