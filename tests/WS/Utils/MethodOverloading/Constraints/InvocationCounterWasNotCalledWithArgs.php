<?php

namespace WS\Utils\MethodOverloading\Constraints;

use WS\Utils\MethodOverloading\TestScaffolding\CallableInvocationCounter;
use PHPUnit\Framework\Constraint\Constraint;
use RuntimeException;
use function sprintf;

/**
 * @author Maxim Sokolovsky
 */

class InvocationCounterWasNotCalledWithArgs extends Constraint
{
    /**
     * @var int
     */
    private $args;

    public function __construct(...$args)
    {
        $this->args = $args;
    }

    public static function create(...$args): InvocationCounterWasNotCalledWithArgs
    {
        return new self(...$args);
    }

    protected function matches($other): bool
    {
        if (!$other instanceof CallableInvocationCounter) {
            throw new RuntimeException('Value of comparision need to be instance of CallableInvocationCounter');
        }

        return !$other->isCalledWith(...$this->args);
    }

    /**
     * @param CallableInvocationCounter $other
     * @return string
     */
    protected function failureDescription($other): string
    {
        $exporter = $this->exporter();

        return "Expected that counter was not called  with: {$exporter->export($this->args)} arguments";
    }

    public function toString(): string
    {
        return sprintf(
            'is accepted by %s',
            self::class
        );
    }
}
