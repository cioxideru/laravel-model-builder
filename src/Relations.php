<?php namespace Jimbolino\Laravel\ModelBuilder;

/**
 * Class Relations, manages all relations of one model
 * @package Jimbolino\Laravel\ModelBuilder
 */
class Relations
{

    // input
    private $localTable = '';
    private $foreignKeys = array();
    private $describes = array();
    private $foreignKeysByTable = array();
    private $prefix = '';

    // temporary
    private $manyToMany = array();

    /**
     * @var Relation[]
     */
    private $relations = array();

    /**
     * This is where the magic happens
     * @param $localTable
     * @param $foreignKeys
     * @param $describes
     */
    public function __construct($localTable, $foreignKeys, $describes, $foreignKeysByTable, $prefix)
    {
        // save
        $this->localTable = $localTable;
        $this->foreignKeys = $foreignKeys;
        $this->describes = $describes;
        $this->foreignKeysByTable = $foreignKeysByTable;
        $this->prefix = $prefix;


        $remoteField = '';
        $remoteTable = '';
        $localField = '';

        // do local keys
        foreach ($foreignKeys['local'] as $foreignKey) {
            $type = $this->findType($foreignKey, false);
            $remoteField = $foreignKey->REFERENCED_COLUMN_NAME;
            $remoteTable = $foreignKey->REFERENCED_TABLE_NAME;
            $localField = $foreignKey->COLUMN_NAME;
            $this->relations[] = new Relation($type, $remoteField, $remoteTable, $localField, $prefix);
        }

        // do remote keys
        foreach ($foreignKeys['remote'] as $foreignKey) {

            $type = $this->findType($foreignKey, true);
            if ($type == 'belongsToMany') {
                $this->manyToMany[] = $foreignKey;
                continue;
            }
            $remoteField = $foreignKey->COLUMN_NAME;
            $remoteTable = $foreignKey->TABLE_NAME;
            $localField = $foreignKey->REFERENCED_COLUMN_NAME;
            $this->relations[] = new Relation($type, $remoteField, $remoteTable, $localField, $prefix);
        }

        // many to many last
        foreach ($this->manyToMany as $foreignKey) {

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

			$rel = $this->relations[] = new Relation($type, $remoteField, $remoteTable, $localField, $prefix,
				$junctionTable);
			$print = $rel->__toString();
			/*if($this->localTable == 'users' && $junctionTable == 'user_notes')
			dd(compact('fields','relations','localField','remoteField','localTable','foreignKey','type','junctionTable','print'));*/
        }

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
        $count = 0;
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
