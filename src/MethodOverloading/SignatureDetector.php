<?php
/**
 * @author Maxim Sokolovsky
 */

namespace MethodOverloading;

use ReflectionClass;

class SignatureDetector
{
    private static $TYPE_DICTIONARY;
    private $expectedTypes;

    private function __construct($types)
    {
        $this->checkDefinedTypes($types);
        $this->checkVariableParameterNumber($types);
        $this->expectedTypes = $types;
    }

    /**
     * $args
     */
    public static function of(): SignatureDetector
    {
        return new self(func_get_args());
    }

    public function detect(array $argsValues): bool
    {
        if (!$this->definedVariableNumberOfParams() && $this->expectedTypesCount() !== count($argsValues)) {
            return false;
        }

        foreach ($this->expectedTypes as $signatureArgument) {
            $argValue = array_shift($argsValues);

            if ($signatureArgument === Param::VARIABLE_NUMBERS) {
                return true;
            }

            if ($signatureArgument === Param::MIXED) {
                continue;
            }

            if ($signatureArgument === Param::INT && !$this->isInt($argValue)) {
                return false;
            }

            if ($signatureArgument === Param::FLOAT && !$this->isFloat($argValue)) {
                return false;
            }

            if ($signatureArgument === Param::STR && !$this->isStr($argValue)) {
                return false;
            }

            if ($signatureArgument === Param::ARRAY && !$this->isArray($argValue)) {
                return false;
            }

            if ($signatureArgument === Param::BOOL && !$this->isBool($argValue)) {
                return false;
            }

            if ($signatureArgument === Param::ITERABLE && !$this->isIterable($argValue)) {
                return false;
            }

            if ($signatureArgument === Param::OBJ && !$this->isObject($argValue)) {
                return false;
            }

            if ($signatureArgument === Param::FUN && !$this->isCallable($argValue)) {
                return false;
            }

            if ($signatureArgument === Param::NULL && $argValue !== null) {
                return false;
            }

            if (is_array($signatureArgument) && $signatureArgument[0] === 'instanceOf' && !$this->isInstanceOf($argValue, $signatureArgument[1])) {
                return false;
            }
        }
        return true;
    }

    public function executeWhen(array $argValues, callable $call)
    {
        if (!$this->detect($argValues)) {
            return null;
        }
        return call_user_func_array($call, $argValues);
    }

    private function isInt($value): bool
    {
        return is_int($value);
    }

    private function isFloat($value): bool
    {
        return is_float($value);
    }

    private function isStr($value): bool
    {
        return is_string($value);
    }

    private function isArray($value): bool
    {
        return is_array($value);
    }

    private function isBool($value): bool
    {
        return is_bool($value);
    }

    private function isObject($value): bool
    {
        return is_object($value);
    }

    private function isCallable($value): bool
    {
        return is_callable($value);
    }

    private function isInstanceOf($object, $class): bool
    {
        if (!$this->isObject($object)) {
            return false;
        }
        if ($class === get_class($object)) {
            return true;
        }
        if (is_subclass_of($object, $class)) {
            return true;
        }
        return false;
    }

    private function isDefinedType($tested): bool
    {
        if (is_array($tested)) {
            return $tested[0] === 'instanceOf';
        }

        $definedTypes = $this->getDefinedTypes();

        return isset($definedTypes[$tested]);
    }

    private function getDefinedTypes(): array
    {
        if (self::$TYPE_DICTIONARY !== null) {
            return self::$TYPE_DICTIONARY;
        }

        $paramClass = new ReflectionClass(Param::class);
        self::$TYPE_DICTIONARY = array_flip($paramClass->getConstants());

        return self::$TYPE_DICTIONARY;
    }

    private function expectedTypesCount(): int
    {
        return count($this->expectedTypes);
    }

    private function definedVariableNumberOfParams(): bool
    {
        return $this->expectedTypes[count($this->expectedTypes) - 1] === Param::VARIABLE_NUMBERS;
    }

    private function checkDefinedTypes($types): void
    {
        foreach ($types as $type) {
            if (!$this->isDefinedType($type)) {
                throw new CompileException("Type {$type} is not defined");
            }
        }
    }

    private function checkVariableParameterNumber($types): void
    {
        if (count($types) <= 1) {
            return;
        }

        array_pop($types);
        if (in_array(Param::VARIABLE_NUMBERS, $types, true)) {
            throw new CompileException("Type Param::VARIABLE_NUMBERS must be only last");
        }
    }

    private function isIterable($argValue): bool
    {
        return is_iterable($argValue);
    }
}
