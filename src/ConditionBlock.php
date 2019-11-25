<?php

namespace Dynamo\ORM;

/**
 * Represents a block of SQL conditions.
 * @author Eduardo Rodriguez Da Silva
 */
class ConditionBlock
{
    protected $conditions;
    protected $and;
    protected $bindings;

    public function __construct(array $conditions, bool $and = true)
    {
        $this->and = $and;
        $this->bindings = [];
        $this->conditions = [];

        foreach($conditions as $key => $value){
            if(\is_numeric($key)){
                if(is_string($value)){
                    $this->conditions[] = $value;
                } else if(is_array($value) && count($value) == 3){
                    //caso [ columna, operador, valorDeseado ]
                    $this->conditions[] = new SqlCondition(...$value);
                } else {
                    throw new \Exception("Unexpected condition format");
                }
            } else if(is_string($key)) {

                $field = $key;

                // Determine operator
                if(
                    \strpos($key, ' ') > 0 &&
                    \preg_match(
                        '/\s+(!=|<>|>|>=|<|<=|IN|NOT IN|LIKE|NOT LIKE|BETWEEN)\s*$/i',
                        $key,
                        $matches
                    )
                ){
                    $field = \substr($key, 0, -\strlen($matches[0]));
                    $operator = $matches[1];
                } else if($value === null){
                    $operator = 'IS';
                } else if(\is_array($value)){
                    $operator = 'IN';
                } else {
                    $operator = '=';
                }

                $this->conditions[] = new SqlCondition($field, $operator, $value);
            } else {
                throw new \Exception("Not implemented 2!");
            }
        }
    }

    public function toString(bool $prepare = true){
        return implode(
            ( $this->and ? ' AND ' : ' OR ' ),
            array_map(
                function($condition) use ($prepare){
                    return $condition->toString($prepare);
                },
                $this->conditions
            )
        );
    }

    public function __toString() : string
    {
        return '(' .
            implode(
                ( $this->and ? ' AND ' : ' OR '),
                $this->parse($this->conditions)
            ) .
            ')';
    }

    public function isAnd() : bool
    {
        return $this->and;
    }

    /**
     * Converts $conditions into an associative array of conditions
     * @param string|array $conditions
     */
    protected function parse($conditions)
    {
        $result = [];

            
        

        return $result;
    }

    /**
     * Adds new conditions to the existing ones
     */
    public function append($conditions)
    {
        if(is_string($conditions)){
            $conditions = [ $conditions ];
        }
        $this->conditions = array_merge($this->conditions, $conditions);
    }
}