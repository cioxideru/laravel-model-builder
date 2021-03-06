<?php namespace Jimbolino\Laravel\ModelBuilder;

use ReflectionClass;
use Exception;

/**
 * Class Model, a representation of one Laravel model
 * @package Jimbolino\Laravel\ModelBuilder
 */
class Model
{

	// input
	private $baseModel = 'Model';
	private $table = '';
	private $foreignKeys = array();

	// the class and table names
	private $class = '';

	// auto detected the elements
	private $timestampFields = array();
	private $primaryKey = '';
	private $incrementing = false;
	private $timestamps = false;
	private $dates = array();
	private $hidden = array();
	private $fillable = array();
	private $rules = array();
	private $namespace;
	private $namespace_base;

	/**
	 * @var Relations
	 */
	private $relations;

	/**
	 * @var ValidationRuleGenerator
	 */
	private $validationGenerator;

	/**
	 * First build the model
	 * @param $table
	 * @param $baseModel
	 * @param $describes
	 * @param $foreignKeys
	 * @param string $namespace
	 * @param string $prefix
	 */
	public function buildModel($table, $baseModel, $describes, $foreignKeys, $validationGenerator, $namespace = '',
		$prefix = '')
	{
		$this->validationGenerator = $validationGenerator;
		$this->table = StringUtils::removePrefix($table, $prefix);
		$this->baseModel = $baseModel;
		$this->foreignKeys = $this->filterAndSeparateForeignKeys($foreignKeys['all'], $table);
		$foreignKeysByTable = $foreignKeys['ordered'];

		$this->namespace_base = $namespace.'\\Base';
		$this->namespace = $namespace;


		$this->class = StringUtils::prettifyTableName($table, $prefix);
		$this->timestampFields = $this->getTimestampFields($this->baseModel);

		$describe = $describes[$table];

		$this->rules = $this->validationGenerator->getRules($table);
		// main loop
		foreach ($describe as $field) {
			if ($this->isPrimaryKey($field)) {
				$this->primaryKey = $field->Field;
				$this->incrementing = $this->isIncrementing($field);
				continue;
			}

			if ($this->isTimestampField($field)) {
				$this->timestamps = true;
				continue;
			}

			if ($this->isDate($field)) {
				$this->dates[] = $field->Field;
			}

			if ($this->isHidden($field)) {
				$this->hidden[] = $field->Field;
				continue;
			}

			if ($this->isForeignKey($table, $field->Field)) {
				continue;
			}

			$this->fillable[] = $field->Field;
		}



		// relations
		$this->relations = new Relations(
			$table,
			$this->foreignKeys,
			$describes,
			$foreignKeysByTable,
			$prefix,
			$this->namespace
		);
	}

	/**
	 * Secondly, create the model
	 */
	public function getBaseModel()
	{

		$file = '<?php namespace '.$this->namespace_base.';'.CRLF.CRLF;

		$file .= '/**'.CRLF;
		$file .= ' * Eloquent class to describe the '.$this->table.' table'.CRLF;
		$file .= ' *'.CRLF;
		$file .= ' * @inheritdoc '.$this->baseModel.CRLF.CRLF;
		foreach($this->fillable as $field){ $file .= ' * @property string '.$field.CRLF; }
		$file .=CRLF;
		foreach($this->relations->relations as $relation){
			/**
			 * @var $relation Relation
			 */
			$file .= ' * @property \\'.$this->namespace.'\\'.$relation->remoteClass.' '.$relation->remoteFunction.
				($relation->type=='belongsTo'?'':'[]')
				.CRLF;
		}
		$file .= ' */'.CRLF;

		// a new class that extends the provided baseModel
		$file .= 'class '.$this->class.'Base extends '.$this->baseModel.CRLF;
		$file .= '{'.CRLF;

		// the name of the mysql table
		$file .= TAB.'protected $table = '.StringUtils::singleQuote($this->table).';'.CRLF.CRLF;

		// primary key defaults to "id"
		if ($this->primaryKey !== 'id') {
			$file .= TAB.'public $primaryKey = '.StringUtils::singleQuote($this->primaryKey).';'.CRLF.CRLF;
		}

		// timestamps defaults to true
		if (!$this->timestamps) {
			$file .= TAB.'public $timestamps = '.var_export($this->timestamps, true).';'.CRLF.CRLF;
		}

		// incrementing defaults to true
		if (!$this->incrementing) {
			$file .= TAB.'public $incrementing = '.var_export($this->incrementing, true).';'.CRLF.CRLF;
		}

		// all date fields
		if (!empty($this->dates)) {
			$file .= TAB.'public function getDates()'.CRLF;
			$file .= TAB.'{'.CRLF;
			$file .= TAB.TAB.'return array('.StringUtils::implodeAndQuote(', ', $this->dates).');'.CRLF;
			$file .= TAB.'}'.CRLF.CRLF;
		}

		if(!empty($this->rules)) {
			$file .= TAB.'public $rules = ['. CRLF;

			foreach ($this->rules as $field => $rules) {
				$file .= TAB . TAB. StringUtils::singleQuote($field).'=>'  .StringUtils::offsetTabs(strlen($field),5)
					. StringUtils::singleQuote
					($rules).','. CRLF ;
			}
			$file .= TAB.'];'. CRLF . CRLF;
		}

		// most fields are considered as fillable
		$wrap = TAB.'protected $fillable = array('.StringUtils::implodeAndQuote(', ', $this->fillable).');'.CRLF.CRLF;
		$file .= wordwrap($wrap, ModelGenerator::$lineWrap, CRLF.TAB.TAB);

		// except for the hidden ones
		if (!empty($this->hidden)) {
			$file .= TAB.'protected $hidden = array('.StringUtils::implodeAndQuote(', ', $this->hidden).');'.CRLF.CRLF;
		}

		// add all relations
		$file .= $this->relations;

		// close the class
		$file .= '}'.CRLF.CRLF;

		return $file;
	}


	public function getModel()
	{

		$file = '<?php namespace '.$this->namespace.';'.CRLF.CRLF;

		$file .= '/**'.CRLF;
		$file .= ' * Eloquent class to describe the '.$this->table.' table'.CRLF;
		$file .= ' *'.CRLF;
		$file .= ' * @inheritdoc \\'.$this->namespace_base.'\\'.$this->class.'Base'.CRLF;
		$file .= ' */'.CRLF;

		// a new class that extends the provided baseModel
		$file .= 'class '.$this->class.' extends \\'.$this->namespace_base.'\\'.$this->class.'Base'.CRLF;
		$file .= '{'.CRLF;

		// close the class
		$file .= '}'.CRLF.CRLF;

		return $file;
	}


	/**
	 * Thirdly, return the created string
	 * @return string
	 */
	public function __toString()
	{
		return $this->getBaseModel();
	}


	/**
	 * Detect if we have timestamp field
	 * TODO: not sure about this one yet
	 * @param $model
	 * @return array
	 */
	protected function getTimestampFields($model)
	{
		try {
			$baseModel = new ReflectionClass($model);
			$timestampFields = array(
				'created_at' => $baseModel->getConstant('CREATED_AT'),
				'updated_at' => $baseModel->getConstant('UPDATED_AT'),
				'deleted_at' => $baseModel->getConstant('DELETED_AT'),
			);
		} catch (Exception $e) {
			echo 'baseModel: '.$model.' not found'.CRLF;
			$timestampFields = array(
				'created_at' => 'created_at',
				'updated_at' => 'updated_at',
				'deleted_at' => 'deleted_at'
			);
		}
		return $timestampFields;
	}

	public function buildParent () {

	}


	protected function getValidatinRules($table){
		$this->validationGenerator->getRules($table);
	}

	/**
	 * Check if the field is primary key
	 * @param $field
	 * @return bool
	 */
	protected function isPrimaryKey($field)
	{
		if ($field->Key == 'PRI') {
			return true;
		}
		return false;
	}

	/**
	 * Check if the field (primary key) is auto incrementing
	 * @param $field
	 * @return bool
	 */
	protected function isIncrementing($field)
	{
		if ($field->Extra == 'auto_increment') {
			return true;
		}
		return false;
	}

	/**
	 * Check if we have timestamp field
	 * @param $field
	 * @return bool
	 */
	protected function isTimestampField($field)
	{
		if (array_search($field->Field, $this->timestampFields)) {
			return true;
		}
		return false;
	}

	/**
	 * Check if we have a date field
	 * @param $field
	 * @return bool
	 */
	protected function isDate($field)
	{
		if (StringUtils::strContains(array('date', 'time', 'year'), $field->Type)) {
			return true;
		}
		return false;
	}

	/**
	 * Check if we have a hidden field
	 * @param $field
	 * @return bool
	 */
	protected function isHidden($field)
	{
		if (StringUtils::strContains(array('hidden', 'secret'), $field->Comment)) {
			return true;
		}
		return false;
	}

	/**
	 * Check if we have a foreign key
	 * @param $table
	 * @param $field
	 * @return bool
	 */
	protected function isForeignKey($table, $field)
	{
		foreach ($this->foreignKeys['local'] as $entry) {
			if ($entry->COLUMN_NAME == $field && $entry->TABLE_NAME == $table) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Only show the keys where table is mentioned
	 * @param $foreignKeys
	 * @param $table
	 * @return array
	 */
	protected function filterAndSeparateForeignKeys($foreignKeys, $table)
	{
		$results = array('local' => array(), 'remote' => array());
		foreach ($foreignKeys as $foreignKey) {
			if ($foreignKey->TABLE_NAME == $table) {
				$results['local'][] = $foreignKey;
			}
			if ($foreignKey->REFERENCED_TABLE_NAME == $table) {
				$results['remote'][] = $foreignKey;
			}
		}
		return $results;
	}
}
