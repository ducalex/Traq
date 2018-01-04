<?php
namespace avalon\database;

interface Driver {
	/* Driver methods */
	public function halt($error = 'Unknown error');

	public function quote($string, $type = \PDO::PARAM_STR);

	public function exec($query);

	public function prepare($query);

	public function query($query);

	public function select($cols = ['*']);

	public function update($table);

	public function delete();

	public function insert(array $data);

	public function replace(array $data);

	public function last_insert_id();
}