<?php
/*
 * Dataset is an hybrid Query/Statement. It behaves like a query (it's a wrapper) but will autoexec when
 * necessary and behave like an iterator or array.
 * The query can be edited and ran again even after execution.
 */

namespace avalon\database;

use avalon\database\pdo\Statement;
use avalon\database\pdo\Query;

class Dataset implements \IteratorAggregate, \ArrayAccess, \Countable, \JsonSerializable
{
	private $query;
	private $dataset = null;

	public function __construct(Query $query)
	{
		$this->query = $query;
	}

	// Query factory
	public function __call($func, $vars)
	{
		$query = $this->query->$func(...$vars);
		if ($query instanceOf Query) {
			return $this;
		} else {
			return $query;
		}
	}

	// Query factory
	public function __toString()
	{
		return (string)$this->query;
	}

	public function data()
	{
		if ($this->dataset === null) {
			$this->dataset = $this->query->exec()->fetch_all();
		}
        return $this->dataset;
	}

	public function getIterator()
	{
        return new \ArrayIterator($this->data());
	}

	public function count()
	{
		return count($this->data() ?: []);
	}

	public function offsetSet($offset, $value)
	{
	}

	public function offsetExists($offset)
	{
		return isset($this->dataset[$offset]);
	}

	public function offsetUnset($offset)
	{
	}

	public function offsetGet($offset)
	{
		return isset($this->dataset[$offset]) ? $this->dataset[$offset] : null;
	}

	public function jsonSerialize()
	{
        return $this->data();
    }
}
