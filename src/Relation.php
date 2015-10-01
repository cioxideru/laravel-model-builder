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
		$junctionTable = '', $m2mDuplicates = 0)
    {
		$this->namepace = $namepace;
		$this->type = $type;
        $this->remoteField = $remoteField;
        $this->localField = $localField;
        $this->remoteFunction = StringUtils::underscoresToCamelCase(StringUtils::removePrefix($remoteTable, $prefix));
        $this->remoteClass = StringUtils::prettifyTableName($remoteTable, $prefix);
        $this->junctionTable = StringUtils::removePrefix($junctionTable, $prefix);

        if ($this->type == 'belongsToMany') {
            $this->remoteFunction = StringUtils::safePlural($this->remoteFunction).($m2mDuplicates>1?$m2mDuplicates:'');
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

        //if(!NamingConvention::primaryKey($this->localField)) {
            $string .= ', '.StringUtils::singleQuote($this->localField);
        //}

        //if(!NamingConvention::foreignKey($this->remoteField, $this->remoteTable, $this->remoteField)) {
            $string .= ', '.StringUtils::singleQuote($this->remoteField);
        //}

        $string .= ');'.CRLF;
        $string .= TAB.'}'.CRLF.CRLF;
        return $string;
    }
}
