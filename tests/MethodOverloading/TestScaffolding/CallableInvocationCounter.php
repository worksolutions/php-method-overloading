<?php

namespace MethodOverloading\TestScaffolding;

/**
 * @author Maxim Sokolovsky
 */

class CallableInvocationCounter
{
    private $invocations = [];
    private $defaultResult;
    private $conditionResults = [];

    /**
     * @param mixed ...$args
     * @return mixed|void
     */
    public function __invoke(...$args)
    {
        $this->invocations[] = $args;
        if ($this->hasResult()) {
            return $this->getResult($args);
        }
    }

    public function willReturn($value): CallableInvocationCounter
    {
        $this->defaultResult = $value;

        return $this;
    }

    public function willReturnWhen(callable $predicate, $value): CallableInvocationCounter
    {
        $this->conditionResults[] = [$predicate, $value];

        return $this;
    }

    public function getInvocations(): array
    {
        return $this->invocations;
    }

    public function countOfInvocations(): int
    {
        return count($this->invocations);
    }

    public function isCalledWith(...$args): bool
    {
        foreach ($this->invocations as $invocationArgs) {
            if ($invocationArgs === $args) {
                return true;
            }
        }
        return false;
    }

    private function hasResult(): bool
    {
        return $this->defaultResult !== null || count($this->conditionResults) > 0;
    }

    /**
     * @param array $args
     * @return mixed|void
     */
    private function getResult(array $args)
    {
        foreach ($this->conditionResults as [$predicate, $result]) {
            if ($predicate($args)) {
                return $result;
            }
        }
        if ($this->defaultResult) {
            return $this->defaultResult;
        }
    }
}
