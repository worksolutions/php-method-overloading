
# Определитель сигнатуры

В PHP в отличие от многих других языков программирования отсутствует перегрузка методов, это скорее всего связано с отсутствием строгой типизации в ранних версиях языка. Но иногда бывает очень полезно воспользоваться перегрузкой, не захламляя исходный код функциями ожидаемый результат выполнения которых един и отличаются они только обработкой входных параметров.

Некоторые библиотеки или фреймворки несмотря на отсутствие перегрузки пользуются переменным числом аргументов для реализации единой логики выполнения метода. Но мягко говоря смотрится это не безопасно, не хватает некой строгости. А для выявления разных сигнатур параметров используются условные операторы, которые часто бывают источниками ошибок при рефакторинге и развитии функционала. 

Пример из библиотеки Laravel класс Illuminate\Database\Query\Builder
```php
<?php

class Builder {
    /**
     * Add a basic where clause to the query.
     *
     * @param  string|array|\Closure  $column
     * @param  mixed   $operator
     * @param  mixed   $value
     * @param  string  $boolean
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        // If the column is an array, we will assume it is an array of key-value pairs
        // and can add them each as a where clause. We will maintain the boolean we
        // received when the method was called and pass it into the nested where.
        if (is_array($column)) {
            return $this->addArrayOfWheres($column, $boolean);
        }

        // Here we will make some assumptions about the operator. If only 2 values are
        // passed to the method, we will assume that the operator is an equals sign
        // and keep going. Otherwise, we'll require the operator to be passed in.
        [$value, $operator] = $this->prepareValueAndOperator(
            $value, $operator, func_num_args() === 2
        );

        // If the columns is actually a Closure instance, we will assume the developer
        // wants to begin a nested where statement which is wrapped in parenthesis.
        // We'll add that Closure to the query then return back out immediately.
        if ($column instanceof Closure) {
            return $this->whereNested($column, $boolean);
        }

        // If the given operator is not found in the list of valid operators we will
        // assume that the developer is just short-cutting the '=' operators and
        // we will set the operators to '=' and set the values appropriately.
        if ($this->invalidOperator($operator)) {
            [$value, $operator] = [$operator, '='];
        }

        // If the value is a Closure, it means the developer is performing an entire
        // sub-select within the query and we will need to compile the sub-select
        // within the where clause to get the appropriate query record results.
        if ($value instanceof Closure) {
            return $this->whereSub($column, $operator, $value, $boolean);
        }

        // If the value is "null", we will just assume the developer wants to add a
        // where null clause to the query. So, we will allow a short-cut here to
        // that method for convenience so the developer doesn't have to check.
        if (is_null($value)) {
            return $this->whereNull($column, $boolean, $operator !== '=');
        }

        // .........

        return $this;
    }
}
```

Разработчики понимают, что исходным кодом в такой ситуации управлять сложно, поэтому такая множество комментариев перед каждым условным оператором потока выполения. Библиотека MethodOveloading служит для того чтобы решать подобные проблемы более устойчивым способом. Опираясь на сигнатуры параметров, как и делается во многих популярных языках программирования.

Разберем тот же пример фреймворка Illuminate, только с использованием определения сигнатуры, с использованием некоторых упрощений, так как демонстрируемый способ влияет на микроархитектуру, делая ее таким образом чище.
```php
class Builder {    
       /**
        * Add a basic where clause to the query.
        *
        * @param  string|array|\Closure  $column
        * @param  mixed   $operator
        * @param  mixed   $value
        * @param  string  $boolean
        * @return $this
        */
       public function where($column, $operator = null, $value = null, $boolean = 'and')
       {
        $args = func_get_args();
        
        $res = SignatureDetector::of(Param::ARRAY, Param::MIXED, Param::MIXED, Param::STRING)
            ->executeWhen($args, function () use ($column, $boolean) {
                return $this->addArrayOfWheres($column, $boolean);
            });
        
        $res === null && $res = SignatureDetector::of(Param::FUN, Param::MIXED, Param::MIXED, Param::STRING)
            ->executeWhen($args, function () use ($column, $boolean) {
                return $this->whereNested($column, $boolean);
            });
        
        $res === null && $res = SignatureDetector::of(Param::STRING, Param::FUN, Param::MIXED, Param::MIXED)
            ->executeWhen($args, function ($column, $value, $_, $boolean) use ($column, $boolean) {
                return $this->whereSub($column, '=', $value, $boolean);
            });
        
        $res === null && $res = SignatureDetector::of(Param::STRING, Param::NULL, Param::MIXED, Param::MIXED)
            ->executeWhen($args, function ($column, $_, $operator, $boolean) use ($column, $boolean) {
                return $this->whereNull($column, $boolean, $operator !== '=');
            });
   
        if ($res !== null) {
            return $res;
        }
        
        return $this;
    }
}

```

 Код получился более строгий и последовательный. Для каждой обработки используется только одно условие, такой код легче в создании и поддержке.
 
В самом простом и основном случае библиотека используется примерно следующим образом:

```php
class UserRepository extends BaseReposityry
{
    /**
     * @param Company|CompanyUser $relatedModel
     * @param int $limit
     */
    public function getUsers(...$params)
    {
        $collection = Collection::make();

        SignatureDetector::of(Param::of(Company::class), Param::VARIABLE_NUMBERS)
            ->executeWhen($params, function (Company $company, $limit) use ($collection) {
                return $this->getUsersByCompany($company, $limit);
            });
        SignatureDetector::of(Param::of(CompanyUser::class), Param::VARIABLE_NUMBERS)
            ->executeWhen($params, function (CompanyUser $companyUser, $limit) use ($collection) {
                return $this->getUsersByCompanyUser($companyUser, $limit);
            });
        return $collection;
    }
}
```

Можно вместо функционального подхода использовать условный оператор, код получается не много короче, но не совсем чистый

```php
class UserRepository extends BaseReposityry
{
    /**
     * @param Company|CompanyUser $relatedModel
     * @param int $limit
     */
    public function getUsers(...$params)
    {
        $companyDetector = SignatureDetector::of(Param::of(CompanyUser::class), Param::VARIABLE_NUMBERS);
        $companyUserDetector = SignatureDetector::of(Param::of(Company::class), Param::VARIABLE_NUMBERS);
        
        if ($companyDetector->detect($params)) {
            return $this->getUsersByCompany($params[0], $params[1]);
        }

        if ($companyUserDetector->detect($params)) {
            return $this->getUsersByCompanyUser($params[0], $params[1]);
        }
    }
}
```
