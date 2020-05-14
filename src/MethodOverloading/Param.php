<?php
/**
 * @author Maxim Sokolovsky
 */

namespace MethodOverloading;

class Param
{
    public const INT = 'int';
    public const FLOAT = 'float';
    public const STR = 'str';
    public const BOOL = 'bool';
    public const OBJ = 'obj';
    public const FUN = 'fun';
    public const ARRAY = 'array';

    public static function instanceOf($class): array
    {
        if (!class_exists($class)) {
            throw new CompileException("Class {$class} is not exist");
        }

        return ['instanceOf', $class];
    }
}
