<?php namespace Jimbolino\Laravel\ModelBuilder;

/**
 * Class Relations, manages all relations of one model
 * @package Jimbolino\Laravel\ModelBuilder
 */
class Relations
{

	// input
	public $localTable = '';
	public $foreignKeys = array();
	public $describes = array();
	public $foreignKeysByTable = array();
	public $prefix = '';
	public $namespace = '';

	/**
	 * @var Relation[]
	 */
	public $relations = array();

	/**
	 * This is where the magic happens
	 * @param $localTable
	 * @param $foreignKeys
	 * @param $describes
	 */
	public function __construct($localTable, $foreignKeys, $describes, $foreignKeysByTable, $prefix,$namespace)
	{
		// save
		$this->localTable = $localTable;
		$this->foreignKeys = $foreignKeys;
		$this->describes = $describes;
		$this->foreignKeysByTable = $foreignKeysByTable;
		$this->prefix = $prefix;
		$this->namespace = $namespace;


		$remoteField = '';
		$remoteTable = '';
		$localField = '';
		$duplicates = $this->getDuplicates($foreignKeys);



		// do local keys
		foreach ($foreignKeys['local'] as $foreignKey) {
			$type = $this->findType($foreignKey, false);
			$remoteField = $foreignKey->REFERENCED_COLUMN_NAME;
			$remoteTable = $foreignKey->REFERENCED_TABLE_NAME;
			$localField = $foreignKey->COLUMN_NAME;

			$this->relations[] = new Relation($type, $remoteField, $remoteTable, $localField,$this->namespace,
				$prefix, '', $duplicates['local'][$remoteTable]);
		}

		// do remote keys
		foreach ($foreignKeys['remote'] as $foreignKey) {
			$type = $this->findType($foreignKey, true);
			if ($type == 'belongsToMany') {
				continue;
			}
			$remoteField = $foreignKey->COLUMN_NAME;
			$remoteTable = $foreignKey->TABLE_NAME;
			$localField = $foreignKey->REFERENCED_COLUMN_NAME;

			$this->relations[] = new Relation($type, $remoteField, $remoteTable, $localField, $this->namespace,
				$prefix, '', $duplicates['remote'][$remoteTable]);
		}

		// many to many last
		foreach ($foreignKeys['remote'] as $foreignKey) {
			$type = $this->findType($foreignKey, true);
			if ($type != 'belongsToMany') {
				continue;
			}

			$fields = $this->describes[$foreignKey->TABLE_NAME];
			$relations = $this->foreignKeysByTable[$foreignKey->TABLE_NAME];
			$localField = $foreignKey->COLUMN_NAME;

			foreach ($relations as $relation) {
				if ($relation->REFERENCED_TABLE_NAME !== $this->localTable) {
					$remoteTable = $relation->REFERENCED_TABLE_NAME;
					$remoteField = $relation->COLUMN_NAME;
				}
			}
			$type = $this->findType($foreignKey, true);
			$junctionTable = $foreignKey->TABLE_NAME;

			$this->relations[] = new Relation(
				$type,
				$remoteField,
				$remoteTable,
				$localField,
				$this->namespace,
				$prefix,
				$junctionTable,
				$duplicates['m2m'][$remoteTable]
			);
		}

	}


	private function getDuplicates($foreignKeys){
		$duplicates=[
			'remote'=>[],
			'local'=>[],
			'm2m'=>[],
		];

		$save_key = 'local';
		foreach($foreignKeys['local'] as $foreignKey){
			$remoteTable = $foreignKey->REFERENCED_TABLE_NAME;
			if(!isset($duplicates[$save_key][$remoteTable]))
				$duplicates[$save_key][$remoteTable] = 0;
			else
				$duplicates[$save_key][$remoteTable]++;
		}

		foreach($foreignKeys['remote'] as $foreignKey){
			$type = $this->findType($foreignKey, true);
			if ($type == 'belongsToMany') {
				$save_key = 'm2m';
				foreach ($this->foreignKeysByTable[$foreignKey->TABLE_NAME] as $relation) {
					if ($relation->REFERENCED_TABLE_NAME !== $this->localTable) {
						$remoteTable = $relation->REFERENCED_TABLE_NAME;
					}
				}
			}else{
				$remoteTable = $foreignKey->TABLE_NAME;
				$save_key = 'remote';
			}
			if(!isset($duplicates[$save_key][$remoteTable]))
				$duplicates[$save_key][$remoteTable] = 0;
			else
				$duplicates[$save_key][$remoteTable]++;
		}

		foreach($duplicates['m2m'] as $table=>$v){
			if(isset($duplicates['remote'][$table])){
				$duplicates['m2m'][$table] ++;
			}
		}


		return $duplicates;
	}

	/**
	 * Try to determine the type of the relation
	 * @param $foreignKey
	 * @param $remote
	 * @return string
	 */
	protected function findType($foreignKey, $remote)
	{
		if ($remote) {
			if ($this->isBelongsToMany($foreignKey)) {
				return 'belongsToMany';
			}
			if ($this->isHasOne($foreignKey)) {
				return 'hasOne';
			}
			return 'hasMany';

		} else {
			return 'belongsTo';
		}
	}

	/**
	 * One to one: The relationship is from a primary key to another primary key
	 * @param $foreignKey
	 * @return bool
	 */
	protected function isHasOne($foreignKey)
	{
		$remote = $this->describes[$foreignKey->TABLE_NAME];
		foreach ($remote as $field) {
			if ($field->Key == 'PRI') {
				if ($field->Field == $foreignKey->COLUMN_NAME) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Many to many
	 * @param $foreignKey
	 * @return bool
	 */
	protected function isBelongsToMany($foreignKey)
	{
		$remote = $this->foreignKeysByTable[$foreignKey->TABLE_NAME];
		if(count($remote) == 2)
		{
			return true;
		}
		return false;
	}


	/**
	 * Outputs all relations to a string
	 * @return string
	 */
	public function __toString()
	{
		$res = '';
		foreach ($this->relations as $relation) {
			$res .= $relation->__toString();
		}
		return $res;
	}
}
