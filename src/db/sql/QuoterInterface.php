<?php

namespace j\db\sql;

/**
 * Interface QuoterInterface
 * @package j\db\sql
 */
interface QuoterInterface{

	public static function getInstance();

	public function quoteTable($value);

	public function quoteIdentifier($value);

	public function quote($value);

}
