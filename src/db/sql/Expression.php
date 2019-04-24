<?php

namespace j\db\sql;

/**
 * Class Expression
 * @package j\db\sql
 */
class Expression {

    // Raw expression string
    protected $_value;

    /**
     * @param $value
     */
    public function __construct($value) {
        // Set the expression string
        $this->_value = $value;
    }

    /**
     * Get the expression value as a string.
     *
     *     $sql = $expression->value();
     *
     * @return  string
     */
    public function value() {
        return (string) $this->_value;
    }

    /**
     * Return the value of the expression as a string.
     *
     *     echo $expression;
     *
     * @return  string
     * @uses    Database_Expression::value
     */
    public function __toString() {
        return $this->value();
    }

} // End Database_Expression

