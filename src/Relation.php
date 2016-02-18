<?php namespace Jimbolino\Laravel\ModelBuilder;

/**
 * Class Relation, defines one single Relation entry
 * @package Jimbolino\Laravel\ModelBuilder
 */
class Relation
{

	public $type;
	public $remoteField;
	public $localField;
	public $remoteFunction;
	public $remoteClass;
	public $junctionTable;
	public $namespace;

	/**
	 * Create a relation object
	 * @param $type
	 * @param $remoteField
	 * @param $remoteTable
	 * @param $localField
	 * @param string $prefix
	 * @param string $junctionTable
	 */
	public function __construct($type, $remoteField, $remoteTable, $localField,$namepace, $prefix = '',
		$junctionTable = '', $duplicates = 0)
	{
		$this->namepace = $namepace;
		$this->type = $type;
		$this->remoteField = $remoteField;
		$this->localField = $localField;
		$this->remoteFunction = StringUtils::underscoresToCamelCase(StringUtils::removePrefix($remoteTable, $prefix));
		$this->remoteClass = StringUtils::prettifyTableName($remoteTable, $prefix);
		$this->junctionTable = StringUtils::removePrefix($junctionTable, $prefix);

		if (!$this->type == 'belongsToMany') {
			$this->remoteFunction = StringUtils::safePlural($this->remoteFunction);
		}

		if($duplicates != 0){
			switch($this->type){
				case 'hasMany':
					$this->remoteFunction .='By'.ucfirst(StringUtils::prettifyTableName($this->remoteField));
					break;
				case 'belongsToMany':
					$this->remoteFunction .='By'.ucfirst(StringUtils::prettifyTableName($this->junctionTable));
					break;
				case 'belongsTo':
					$this->remoteFunction .='By'.ucfirst(StringUtils::prettifyTableName($this->localField));
					break;
			}
		}
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		$string = TAB.'public function '.$this->remoteFunction.'()'.CRLF;
		$string .= TAB.'{'.CRLF;
		$string .= TAB.TAB.'return $this->'.$this->type.'(';
		$string .= StringUtils::singleQuote($this->namepace.'\\'.$this->remoteClass);

		if ($this->type == 'belongsToMany') {
			$string .= ', '.StringUtils::singleQuote($this->junctionTable);
		}

		if($this->type == 'hasMany'){
			$string .= ', '.StringUtils::singleQuote($this->remoteField);
			$string .= ', '.StringUtils::singleQuote($this->localField);
		}else{
			$string .= ', '.StringUtils::singleQuote($this->localField);
			$string .= ', '.StringUtils::singleQuote($this->remoteField);
		}

		$string .= ');'.CRLF;
		$string .= TAB.'}'.CRLF.CRLF;
		return $string;
	}
}
