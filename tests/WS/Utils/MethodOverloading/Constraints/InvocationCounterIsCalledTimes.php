<?php

namespace WS\Utils\MethodOverloading\Constraints;

use WS\Utils\MethodOverloading\TestScaffolding\CallableInvocationCounter;
use PHPUnit\Framework\Constraint\Constraint;
use RuntimeException;
use function sprintf;

/**
 * @author Maxim Sokolovsky
 */

class InvocationCounterIsCalledTimes extends Constraint
{

    /**
     * @var int
     */
    private $times;

    public function __construct($times = 1)
    {
        $this->times = $times;
    }

    public static function create($times = 1): InvocationCounterIsCalledTimes
    {
        return new self($times);
    }

    protected function matches($other): bool
    {
        if (!$other instanceof CallableInvocationCounter) {
            throw new RuntimeException('Value of comparision need to be instance of CallableInvocationCounter');
        }

        return $other->countOfInvocations() === $this->times;
    }

    /**
     * @param CallableInvocationCounter $other
     * @return string
     */
    protected function failureDescription($other): string
    {
        return "Expected that counter was called {$this->times} times, but {$other->countOfInvocations()}";
    }

    public function toString(): string
    {
        return sprintf(
            'is accepted by %s',
            self::class
        );
    }
}