<?php

/**
 * Description of clsdbo
 * @authors  - Vineeth, Habid PK, Revath S Kumar, Sreejith PM
 * @created  - 22-11-2010
 */

namespace dbos;

require ROOT_FOLDER . DS . 'atom' . DS . 'nucleus' . DS . 'nclspdo.php';
require ROOT_FOLDER . DS . 'prj' . DS . 'schemarules' . DS . 'clsschemarelations.php';
require ROOT_FOLDER . DS . 'atom' . DS . 'nucleus' . DS . 'nucleusvalidator.php';
//require ROOT_FOLDER . DS . 'prj' . DS . 'schemarules' . DS . 'clsschemaalias.php';
require ROOT_FOLDER . DS . 'atom' . DS . 'nucleus' . DS . 'errorhandler.php';
require ROOT_FOLDER . DS . 'prj' . DS . 'config' . DS . 'dbconfig.php';

class clsdbos extends \nclspdo {

    protected $arrSchemaRelations;
    protected $arrTableAlias;
    private $objAlias;
    private $objValidator;
    public $templateVars = array();
    public $data = array();
    private $configs = array();

	public $actionsPermitted = array();
    /**
     *
     * @var Array Error messages
     */
    protected $arrErrors = array();

    /**
     * Constructor function
     */
    function __construct() {
        $this->configs = \dbConfig::$configs;
        parent::__construct($this->configs);
        $relation = new \schema\relations\clsrelations();
        $this->arrSchemaRelations = $relation->getSchemaRelation();
        $this->objValidator = new \validator\nucleusvalidator();
        $this->objAlias = new \schema\alias\clsschemaalias();
        $this->arrTableAlias = $this->objAlias->getSchemaAlias();
        $this->errHandler = new \errors\errorhandler($_SERVER['REMOTE_ADDR']);
    }

    /**
     * Function to set values which is used to display in templates
     * @param <array> $values
     */
    protected function setVars($values = array()) {
        $this->templateVars['data'] = $this->data;
		$this->templateVars['vErrors'] = $this->arrErrors;
        $this->templateVars = array_merge($this->templateVars, $values);
    }

    /**
     * Function for server validation
     * @param String $strtablename 
     * @param Array $arrdata
     * @return Array
     */
    protected function validate($strtablename, $arrdata) {
        return $this->objValidator->checkValidation($strtablename, $arrdata);
    }

    /**
     * Function to replace the table alias with table name
     * @param <array> $arrData
     * @return <array>
     */
    protected function replaceTableAlias($arrData) {
        return $this->objAlias->replaceAlias($arrData);
    }

	/**
    *
    * @param string  $tableName
    * @return boolean
    */
    protected function trunc($tableName) {
        return parent::trunc($tableName);
    }


    /**
     * Function to replace the table name with alias
     * @param <array> $arrData
     * @return <array>
     */
    protected function replaceWithTableName($arrData) {
        return $this->objAlias->replaceSchemaNames($arrData);
    }

    /**
     * Function to check whether the array elements have value or not
     * @param <array> $arrErrors
     * @param <string> $tablename
     * @param <string> $fieldname
     * @return <string>
     */
    public function displayError($tablename, $fieldname) {
        if (count($this->arrErrors) > 0) {
            foreach ($this->arrErrors as $arrKey => $value) {
                if ($arrKey == $tablename && array_key_exists($fieldname, $value)) {
                    return $value[$fieldname];
                    break;
                }
            }
        }
    }

	/**
     * Function to get last insert id
     * @return Integer
     */
    function getLastInsertId(){
        return parent::getLastInsertId();
    }

    /**
     * Function to execute stored procedure
     * @param <string> $routineName
     * @param <array> $parameters
     * @return <array>
     * @access protected
     */
    protected function runSp($routineName, $parameters) {
        return parent::runSp($routineName, $parameters);
    }

    /**
     * Function to get schema of a table
     * @return <array>
     * @access protected
     */
    protected function getSchema($tablename){
        $arrSchema = parent::getSchema($tablename);
        $arrSchema = $this->replaceWithTableName($arrSchema);
        return $arrSchema;
    }

    /**
     * Function to insert values
     * @param string $tableName
     * @param array $data
     * @return boolean
     * @access protected
     */
    protected function insert($tableName, $data) {
        return parent::insert($tableName, $data);
    }

    /**
     * Function to update values
     * @param <string> $tableName
     * @param <mixed array> $data
     * @param <array> $params
     * @return <boolean>
     */
    protected function update($tableName, $data, $params = null) {
        return parent::update($tableName, $data, $params);
    }

    /**
     * Function to delete records
     * @param <string> $tableName
     * @param <array> $data
     * @return <boolean>
     * @access protected
     */
    protected function del($tableName, $data) {
        return parent::del($tableName, $data);
    }

    /**
     * Function to Begin transaction
     * @access protected
     */
    protected function begin() {
        parent::begin();
    }

    /**
     * Function to commit transaction
     * @access protected
     */
    protected function commit() {
        parent::commit();
    }

    /**
     * Function to rollback
     * @access protected
     */
    protected function rollback() {
        parent::rollback();
    }

    /**
     *
     * @param String $tableName
     * @param Array $params
     * @access protected
     */
    function get($tableName, $params = null) {
        return parent::get($tableName, $params);
    }

}

?>