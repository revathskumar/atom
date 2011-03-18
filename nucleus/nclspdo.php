<?php
/**
 * Description of nclspdo
 * @authors  - Vineeth, Habid PK, Revath S Kumar, Sreejith PM
 * @created  - 22-11-2010
 */

abstract class nclspdo{

    //all methods in core class should have protected access identifier.
   
    /**
     *
     * @var PDOStatement
     * @access private
     */
    private $objDbConn;
   
    private $dbConfigs = array();
   
    /**
     * Contains the Table Schema
     *
     * @var Array
     * @access private
     */
    private $schema;
    private $arrDataTypes = array();
    /**
     * Valid cache Time in seconds
     *
     * @var Int
     */
    private $iSchemaCacheDelayTime = 120;

    /**
     * Holds all the operators used in conditional statement
     *
     * @var Array
     */
    protected $_operators = array(
        '='  => array('multiple' => 'IN'),
        '<'  => array(),
        '>'  => array(),
        '<=' => array(),
        '>=' => array(),
        '!=' => array('multiple' => 'NOT IN'),
        '<>' => array('multiple' => 'NOT IN'),
        'between' => array('format' => 'BETWEEN ? AND ?'),
        'BETWEEN' => array('format' => 'BETWEEN ? AND ?')
    );

    /**
     * Holds all the aggregate and scalar functions
     *
     * @var Array
     */
    protected $_func = array(
        'MIN' => array(),
        'AVG' => array(),
        'COUNT' => array(),
        'FIRST' => array(),
        'LAST' => array(),
        'MAX' => array(),
        'SUM' => array(),
        'UCASE' => array(),
        'LCASE' => array(),
        'MID' => array(),
        'LEN' => array(),
        'ROUND' => array(),
        'NOW' => array(),
        'FORMAT' => array(),
        'CONVERT' => array(),
        'LOWER' => array(),
        'ISNULL' => array(),
        'ISNOTNULL' => array(),
        'MONTH' => array(),
        'YEAR' => array()
    );

    /**
     * Constructor Function
     *
     * @param Array $arrConfigs
     */

    protected function __construct($arrConfigs) {
        $this->dbConfigs = $arrConfigs;
        $this->loadDataTypes();
        $this->dbConnect($arrConfigs);
    }

    /**
     * Function to get schema array to the child classes
     * @return <array>
     */
    protected function getSchema($strtablename){
        if(!$this->schema || !array_key_exists($strtablename, $this->schema))$this->loadSchema($strtablename);
        return $this->schema;
    }


    /**
     * Function to get the schema of specified table
     * @param String $strTblName
     * @return Array
     */
    private function loadSchema($strTblName) {
        if (!$this->getFromCache($strTblName)) {
            $this->result = $this->query($this->getSchemaQuery($strTblName));
            $schema = $this->fetchValues($this->result);
            foreach ($schema as $value) {
                $this->schema[$strTblName][$value['Field']]['type'] = $value['Type'];
                $this->schema[$strTblName][$value['Field']]['length'] = $value['Length'];
            }
            $this->processSchemaCache($strTblName);
        }
    }

    /**
     * Function to ignore blank values while inserting
     * @param <array> $data
     * @return <array>
     */
    private function ignoreBlankValueFields($data){
        $arrData = array();
        if(!is_array($data)) return false;
        foreach($data as $valData => $value){
            if($value){
                foreach($value as $valSubKey => $val){
                    if(trim($val) != "") $arrData[$valData][$valSubKey] = $val;
                }
            }
        }
        return $arrData;
    }

    /**
     * Function to get last insert id
     * @return <integer>
     */
    protected function getLastInsertId(){
        $iLastInsertId = $this->objDbConn->lastInsertId();
        return $iLastInsertId;
    }

    /**
     *
     * To insert a new Record to the table
     * @param array $tableName
     * @param array $conditions
     * @param array $order
     * @param array $limit
     */
    protected function insert($tableName, $data) {
        if(!is_array($data) || !array_key_exists($tableName, $data))return FALSE;
        $data = $this->ignoreBlankValueFields($data);
        $data = $this->processData($tableName, $data);
        if(count($data[$tableName])< 1)return FALSE;
        $sql = "insert into {$tableName} (" . implode(',', array_keys($data[$tableName])) . ") values (" . implode(',', $data[$tableName]) . ")";
        $result = $this->query($sql);
           if(!$result){
            pr($sql);
            return false;
        }
        return $this->countAffecetedRows($result);
    }

    /**
     * To update a given record with a specific id
     * @param <string> $tableName
     * @param <mixed array> $data
     * @param <array> $params
     * @return <boolean>
     */
    protected function update($tableName, $data, $params=null) {
        $condition = "";
        if(!is_array($data) || !array_key_exists($tableName, $data))return FALSE;
        $data = $this->ignoreBlankValueFields($data);
        $data = $this->processData($tableName, $data);
        foreach ($data[$tableName] as $field => $value) {
            if ($field == 'id' || 'ID' === $field) continue;
            $temp[] = $field . " = " . $value;
        }
       
        if(is_array($params) && array_key_exists('condition', $params)){
            $arrFields = $this->formatWhereCondition($tableName, $params['condition']);
            $condition = $this->where($tableName,$arrFields);
//            pr($arrFields);
            $sql = "update {$tableName} set " . implode(',', $temp) . " {$condition} ;";
            if(isset($data[$tableName]['id'])) $sql .= " AND id = ".$data[$tableName]['id'];
            if(isset($data[$tableName]['ID']) and !isset($arrFields['ID'])) $sql .= " AND {$tableName}.ID = ".$data[$tableName]['ID'];
        }
        else{
            $sql = "update {$tableName} set " . implode(',', $temp) . " where ";//id=" . $data[$tableName]['id'] . ";";
            if(isset($data[$tableName]['id'])) $sql .= " id = ".$data[$tableName]['id'];
            if(isset($data[$tableName]['ID'])) $sql .= " {$tableName}.ID = ".$data[$tableName]['ID'];
        }

        $result = $this->query($sql);
        
        if(!$result){
                pr($sql);
                return FALSE;
        }
        return $this->countAffecetedRows($result);
    }


    /**
     * To delete  a record with a given id
     *
     * @param String $tableName
     * @param Array $data
     * @return boolean
     */
    protected function del($tableName, $data, $params=null) {
        if(!is_array($data) || (!array_key_exists($tableName, $data) && !array_key_exists('id', $data[$tableName]) && !array_key_exists('condition', $params))) return FALSE;
        if(is_array($params) && array_key_exists('condition', $params)){
            $arrFields = $this->formatWhereCondition($tableName, $params['condition']);
            $condition = $this->where($tableName,$arrFields);
            $sql = "delete from {$tableName} {$condition};";
            if(isset($data[$tableName]['id']) && !isset($params['condition']['id'])) $sql .= " AND {$tableName}.id = ".$data[$tableName]['id'];
        }
        else{
            $sql = "delete from {$tableName} where id = {$data[$tableName]['id']};";
        }

        $result = $this->query($sql);
        if(!$result){
            pr($sql);
            return FALSE;
        }
        return $this->countAffecetedRows($result);
    }

    /**
     * To TRUNCATE TABLE
     *
     * @param String $tableName
     * @return boolean
     */
    protected function trunc($tableName){
        if (strlen($tableName) <= 0 || $tableName==""){
            return false ;
        }else{
            $sql="TRUNCATE TABLE " . $tableName ;
            return $this->query($sql);
        }
    }
   
    /**
     * To begin a Transaction
     */
    protected function begin() {
        $this->objDbConn->beginTransaction();
    }

    /**
     * To Commit Transaction
     */
    protected function commit() {
        $this->objDbConn->commit();
    }

    /**
     * To rollback the Transaction
     */
    protected function rollback() {
        $this->objDbConn->rollBack();
    }

    /**
     *
     * @param <type> $result
     */
    private function countAffecetedRows(& $result) {
        if($result === FALSE)return FALSE;
        if ($result->rowCount() > 0) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Function to execute stored routines
     * @param <string> $routineName
     * @param <array> $parameters
     */
    protected function runSp($routineName, $parameters) {
        if(!is_array($parameters) || !$this->dbConfigs)return FALSE;
        $strSPQuery = "";
        switch ($this->dbConfigs["driver"]) {
            case "mysql";
                $strSPQuery = "CALL " . $routineName . "(" . implode(",", $parameters) . ");";
                break;
            case "mssql";
                $strSPQuery = "exec " . $routineName . " " . implode(",", $parameters) . " ";

                break;
            case "sqlsrv";
                $strSPQuery = "exec " . $routineName . " " . implode(",", $parameters) . " ";
                break;
        }

        $result = $this->query($strSPQuery);
        return $this->fetchValues($result);
    }

    /**
     * Function to check the table schema cache
     * @param <string> $strTblName
     * @return <boolean>
     */
    private function getFromCache($strTblName) {
        $strFileName = $strTblName . ".json";
        $strFileContents = "";
        $strFilePath = dirname(__FILE__) . "\..".DS."stash".DS.$strFileName;

        if (file_exists($strFilePath)) {
            //get file modification time
            $strFileCreatedTime = date("H:i:s", filemtime($strFilePath));
            $strCurrentTime = date("H:i:s");
            $strDiff = strtotime($strCurrentTime) - strtotime($strFileCreatedTime);
            if ($strDiff < $this->iSchemaCacheDelayTime) {
                $strFileContents = file_get_contents($strFilePath);
                if (json_decode($strFileContents, true)) {
                    $this->schema = json_decode($strFileContents, true);
                    return true;
                } else {
                    return false;
                }
            } else {
                unlink($strFilePath);
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Function to get the table schema
     * @param <type> $strTblName
     */
    private function processSchemaCache($strTblName) {
        $strJsonCode = "";
        $strFileName = $strTblName . ".json";
        $strFilePath = dirname(__FILE__) . "\..".DS."stash".DS.$strFileName;
        $strJsonCode = json_encode($this->schema);
        $fileHandler = fopen($strFilePath, 'w');
        fwrite($fileHandler, $strJsonCode);
        fclose($fileHandler);
    }

    /**
     * Function to change the custom date format to default sys format
     * @param <date/datetime> $val
     * @param <string> $format
     * @return date value
     */
    private function changeDateToDefaultFormat($val,$format){
        $strDateCustomFormat = $_SESSION["dateformat"];
        $strDate = "";
        if($val != ""){
            switch ($strDateCustomFormat){
                case "d-m-Y":
                    if($format == "date"){
                        $arrVals = explode("-", $val);
                        $strDate = $arrVals[0]."-".$arrVals[1]."-".$arrVals[2];
                    }
                    elseif($format == "datetime"){
                        $arrVals = explode(" ", $val);
                        $arrValDetails = explode("-", $arrVals[0]);
                        $strDate = $arrValDetails[0]."-".$arrValDetails[1]."-".$arrValDetails[2]." ".$arrVals[1];
                    }
                    break;
                case "m-d-Y":
                    if($format == "date"){
                        $arrVals = explode("-", $val);
                        $strDate = $arrVals[1]."-".$arrVals[0]."-".$arrVals[2];
                    }
                    elseif($format == "datetime"){
                        $arrVals = explode(" ", $val);
                        $arrValDetails = explode("-", $arrVals[0]);
                        $strDate = $arrValDetails[1]."-".$arrValDetails[0]."-".$arrValDetails[2]." ".$arrVals[1];
                    }
                    break;
                case "Y-m-d":
                    if($format == "date"){
                        $arrVals = explode("-", $val);
                        $strDate = $arrVals[2]."-".$arrVals[1]."-".$arrVals[0];
                    }
                    elseif($format == "datetime"){
                        $arrVals = explode(" ", $val);
                        $arrValDetails = explode("-", $arrVals[0]);
                        $strDate = $arrValDetails[2]."-".$arrValDetails[1]."-".$arrValDetails[0]." ".$arrVals[1];
                    }
                    break;
            }
        }
        return $strDate;
    }
   
    /**
     * Function to change the date format
     * @param <date value> $val
     * @param <date format> $format
     * @return <changed date value>
     */
    private function changeDateFormat($val,$format){
       
        $val = $this->changeDateToDefaultFormat($val, $format);
        if($val != "" && count($this->dbConfigs) > 0){
            switch ($this->dbConfigs['driver']){
                case "mysql":
                case "sqlsrv":
                    if($format == "date"){
                        $val = date("Y-m-d",  strtotime($val));
                    }
                    elseif($format == "datetime"){
                        $val = date("Y-m-d H:i:s",  strtotime($val));
                    }
                    break;
            }
            return $val;
        }
    }
    /**
     * To Proccess the input data and
     * add the quotes where ever needed
     *
     * @param mixed $data
     */
    private function processData($tableName, $data) {
        if(!$this->schema || !array_key_exists($tableName, $this->schema)) $this->loadSchema($tableName);
        if(!is_array($data) || !array_key_exists($tableName, $data))return FALSE;
        foreach ($data[$tableName] as $field => $value) {
            if (in_array($this->schema[$tableName][$field]['type'], $this->arrDataTypes)) {
                                if(in_array($this->schema[$tableName][$field]['type'],array('date','datetime','smalldatetime'))) $value = $this->changeDateFormat($value,$this->schema[$tableName][$field]['type']);
                $data[$tableName][$field] = "'" . addslashes($value) . "'";
            }
        }
        return $data;
    }
   
  /**
     * Function to format the fields for where condition
     * @param <string> $tableName
     * @param <array> $fieldsArr
     * @return <array>
     */
    private function formatWhereCondition($tableName,$fieldsArr) {   
        if (is_array($fieldsArr)) {
            if (count($fieldsArr) > 0) {
                foreach ($fieldsArr as $func => $field) {
                    if (is_string($func)) {
                        if(is_array($field)){
                            $fieldKey = key($field);
                            $value = $field[$fieldKey];
                        }
                        if (array_key_exists($func, $this->_func)) {
                            $func = strtoupper($func);
                            if($func == "ISNULL" || $func == "ISNOTNULL"){
                                $fields["{$tableName}.{$fieldKey}"] = $value;
                            }
                            else{
                                $fields["{$func}({$tableName}.{$fieldKey})"] = $value;
                            }

                        } else {
                            //$fields[] = "{$tableName}.{$field}";
                            $fields["{$func}"] = $field;
                        }
                    }
                }
            }
        }
//        pr($fields);
        return $fields;
    }

    /**
     * To Perform the select operation
     *
     * @param String $tableName
     * @param Array $params
     *    -- Possible Values
     *        $params['fields']
     *        $params['join']
     *        $params['condition']
     *        $params['order']
     *        $params['group']
     *        $params['limit']
     *       
     */
    protected  function get($tableName,$params = null) {
        if(!$this->schema || !array_key_exists($tableName,$this->schema))$this->loadSchema ($tableName);
        $fields =  array();
//        pr($params);
        $condition = $order = $limit = $group = $having = $join = $offset = $fieldsArr = $sql =   "";
        if(isset($params['fields'])) $fieldsArr = $params['fields'];
        $sql = "";
        $fields = $this->formatFields($tableName, $fieldsArr);

        $top = "";
        if($params != null){
            if(array_key_exists('condition', $params)){
                $arrFields = $this->formatWhereCondition($tableName, $params['condition']);
                $condition = $this->where($tableName,$arrFields);
            }
            if(array_key_exists('group', $params)){
                $field = $this->formatFields($tableName,  $params['group']);
                $groupBy = (is_array($field))?implode(', ', $field):$field;
                $group = " GROUP BY {$groupBy}";
            }
            if(array_key_exists('order', $params)){
                $orderBy = array();
                foreach($params['order'] as $dir => $field){
                    $field = $this->formatFields($tableName, $field);
                    $orderBy[] = implode(',', $field)." ".$dir;
                }
                 if(count($params['order']) > 0) $order = " ORDER BY ".implode(', ', $orderBy);
            }
            if(array_key_exists('limit', $params) && ($this->dbConfigs["driver"] == 'mssql' || $this->dbConfigs["driver"]== 'sqlsrv') && count($params['limit']) > 1){
                    $offset = $params['limit'][0];
                    $limit = (array_key_exists('page', $params) && array_key_exists('count', $params['page'])) ? $params['limit'][0]+$params['page']['count'] : $params['limit'][0]+$params['limit'][1]-1;
    //                pr($offset);
    //                pr($limit);
            }
            elseif(array_key_exists('limit', $params) || array_key_exists('page', $params)){
                if(is_array($params['limit'])){
                    $offset = $limit = "";
                    if($this->dbConfigs["driver"] == 'mssql' || $this->dbConfigs["driver"]== 'sqlsrv'){
                        $top = " TOP ". $params['limit'][0];
                    }
                    else{
                        if(count($params['limit'])>1){
                            $offset = $params['limit'][0];
                            $limit = (array_key_exists('page', $params) && array_key_exists('count', $params['page']))?$params['page']['count']:$params['limit'][1];
                        }
                        else{
                            $offset = 0;
                            $limit = $params['limit'][0];
                        }
                        $limit = " LIMIT {$offset},{$limit}";
                    }
                }
                elseif(array_key_exists('page', $params) && array_key_exists('count', $params['page'])){
                    $offset = 0;
                    if(array_key_exists('page', $params['page']))$offset = $params['page']['page'] * $params['page']['count'];
                    $limit = $params['page']['count'];
                    $limit = " LIMIT {$offset},{$limit}";
                }
            }

            if(array_key_exists('having', $params)){
                $having = "HAVING ".join(", ",$this->formatFields($tableName, $params['having']));
            }
            if(array_key_exists('join', $params)){
                $join  = $this->getjoinquery($tableName, $params['join']);
            }
        }
        if($params != null && array_key_exists('limit', $params) && ($this->dbConfigs["driver"] == 'mssql' || $this->dbConfigs["driver"]== 'sqlsrv') && count($params['limit']) > 1){
            if($order == "")$order = "ORDER BY  ID";
            $sql = "SELECT * FROM   (SELECT ROW_NUMBER() OVER({$order}) AS  rownum, ".implode(', ', $fields)." FROM {$tableName} {$join} {$condition} {$group}) AS temp WHERE  rownum >= {$offset} AND rownum <= {$limit}";
        }
        else{
            $sql = "SELECT {$top} ".implode(', ', $fields)." FROM {$tableName} {$join} {$condition} {$group} {$having} {$order} {$limit}";
        }
//        pr($sql);
        $result = $this->query($sql);
        if(!$result){
            pr($sql);
            return false;
        }
        return $this->fetchValues($result);
    }

   
    /**
     * To Format the Fields By adding Table name and functions
     * for Specifing fields and Having Clause
     *
     * @access Private
     * @param String $tableName
     * @param array $fieldsArr
     * @return Array
     */
    private function formatFields($tableName, $fieldsArr) {

        if (is_array($fieldsArr)) {
            if (count($fieldsArr) > 0) {
                foreach ($fieldsArr as $func => $field) {
                    if (is_array($field)) {
                        $condition = $this->where($tableName, $field, array('prepend' => FALSE));
                        $pos = -1;
                        foreach ($this->_operators as $key => $value) {
                            $pos = strpos($condition, $key);
                            if ($pos !== FALSE) {
                                break;
                            }
                        }
                        $endPart = substr($condition, $pos);
                        $startPart = trim(substr($condition, 0, $pos));
                        $fields[] = "{$func}({$startPart}) {$endPart}";
                    } else {
//                                    pr($field);
                        if (is_string($func)) {
                            $func = strtoupper($func);
                            if (array_key_exists($func, $this->_func)) {
                                $fields[] = "{$func}({$tableName}.{$field})";
                            } else {
                                $fields[] = "{$tableName}.{$field}";
                            }
                        } else if (array_key_exists($field, $this->schema[$tableName])) {
                            $fields[] = "{$tableName}.{$field}";
                        } else {
                            $fields[] = $field;
                        }
                    }
                }
            }
        } else if ((strpos($fieldsArr, '*') !== FALSE && strlen($fieldsArr) == 1) || $fieldsArr == NULL) {
            if (count($this->schema) > 0) {
                foreach ($this->schema[$tableName] as $field => $attr) {
                    $fields[] = $tableName . "." . $field;
                }
            }
        } else {
            if (array_key_exists($fieldsArr, $this->schema[$tableName])) {
                $fields[] = "{$tableName}.{$fieldsArr}";
            } else {
                $fields[] = $fieldsArr;
            }
        }
        return $fields;
    }

    /**
     * To addSlashes to all the values in an multi dimensional array
     * @param Array $value
     * @return String
     */
    private function addSlashesDeep($value) {
            $value = is_array($value) ? array_map(array($this,'addSlashesDeep'), $value) : addslashes($value);
            return $value;
    }
   
    /**
     * Generate where clause for the select statement
     *
     * @access Private
     * @param String $tableName
     * @param array $conditions
     * @param array $options
     * @return String
     */
    private  function where($tableName,array $conditions,array $options = array()){
                if(empty ($options))$options = array('prepend' => true ,'join' => ' AND ');
        if(!isset($options['join'])) $options['join'] = ' AND ';
        $ops = $this->_operators;

                if(!array_key_exists($tableName, $this->schema)) $this->loadSchema ($tableName);
        $schema = $this->schema[$tableName];
                $conditions = $this->addSlashesDeep($conditions);

        switch (true) {
            case empty($conditions):
                return '';
            case is_string($conditions):
                return ($options['prepend']) ? "  WHERE {$conditions}" : $conditions;
            case !is_array($conditions):
                return '';
        }
        $result = array();

                if(count($conditions) > 0 && count($schema) > 0){
                    foreach ($conditions as $key => $value) {
                            $schema[$key] = isset($schema[$key]) ? $schema[$key] : array();

                            if(array_key_exists ($key,$this->schema[$tableName]) || $key == 'OR' || $key == 'AND'){
                                $strTmpTableName = $tableName;
                                $key1 = $key;
                                $strTblName = $strTmpTableName;
                            }
                            else{
                                $strTmpTableName = "";
                                if(strpos($key, ".") !== false) $arrTables = explode(".", $key);
                                $strTmpTableName = $arrTables[0];
                                $key = $arrTables[1];

                                $key1 = trim($key,")");

                                if(strpos($strTmpTableName, "(") !== false )
                                        $strTblName = substr($strTmpTableName,  strpos($strTmpTableName, "(") + 1);
                                else
                                    $strTblName = $strTmpTableName;

                                $schema[$key1] = isset($schema[$key1]) ? $schema[$key1] : array();
                            }

                            if(!array_key_exists((isset($strTblName)? $strTblName : $strTmpTableName), $this->schema)) $this->loadSchema ((isset($strTblName) ? $strTblName : $strTmpTableName));
                                $schema = $this->schema[(isset($strTblName)? $strTblName : $strTmpTableName)];

                            switch (true) {
                                    case strtolower($key) == 'or':
                                    case strtolower($key) == 'and':
                                            $result[] = $this->where($strTmpTableName,$value,array('prepend' => FALSE,'join' => " {$key} "));
                                    break;
                                    case (is_numeric($key) && is_array($value)):
                                            $result[] = $this->where($strTmpTableName,$value,array('prepend' => FALSE));
                                    break;
                                    case (is_numeric($key) && is_string($value)):
                                            $result[] = $value;
                                    break;
                                    case (is_string($key) && is_array($value) && isset($ops[key($value)])):
                                            foreach ($value as $op => $val) {
                                                    $result[] = $this->_operator($strTmpTableName,$key, array($op => $val), $schema[$key1]);
                                            }
                                    break;
                                    case (is_string($key) && is_array($value)):
                                            $value = join(', ', $this->value($value, $schema[$key1]));
                                            $result[] = "{$strTmpTableName}.{$key} IN ({$value})";
                                    break;
                                    case (is_string($key) && is_string($value) && strpos($value,'%') !== FALSE):
                                            $result[] =  "{$strTmpTableName}.{$key}  LIKE '{$value}'";
                                    break;
                                    default:
                                            if($strTmpTableName != "") $value = $this->value($value, $schema[$key1]);

                                            if(($value == "IS NULL" || $value == "IS NOT NULL") && is_string($value)){
                                                $result[] = $strTmpTableName.".".$key." {$value} ";
                                            }
                                            else{
                                                if(is_string($value) && $strTmpTableName == "") $value = "'".addslashes ($value)."'";
                                                $result[] = $strTmpTableName.".".$key." = ".$value;
                                            }
                                    break;
                            }
                    }
                }

        if(count($result)>1){
            $result = "(".join($options['join'], $result).")";
        }else{
            $result = join($options['join'], $result);
        }
        return ($options['prepend'] && !empty($result)) ? "WHERE {$result}" : $result;
    }


    /**
     * Converts a given value into the proper type based on a given schema definition.
     *
     * @param mixed $value The value to be converted. Arrays will be recursively converted.
     * @param array $schema Formatted array
     * @return mixed value with converted type
     */
    private function value($value, array $schema = array()) {
        if (is_array($value) && count($value) > 0) {
            foreach ($value as $key => $val) {
                $value[$key] = $this->value($val, $schema);
            }
            return $value;
        }
        if ($value === null) {
            return 'NULL';
        }
        if(count($schema) > 0){
            switch ($type = $schema['type']) {
                            case 'boolean':
                                    return $this->_toNativeBoolean($value);
                            case 'float':
                                    return floatval($value);
                            case 'string':
                            case 'nchar':
                            case 'varchar':
                            case 'nvarchar':
                                    return "'".$value."'";
                            case 'ntext':
                                return "\"".$value."\"";
                            case 'integer':
                            case 'bigint':
                            case 'int':
                                    return intval($value);
                            case 'date':
                                return "'".date("Y-m-d", strtotime($value))."'";
                            case 'datetime':
                                return "'".date("Y-m-d H:i:s", strtotime($value))."'";

                    }
        }
    }

    private function _toNativeBoolean($value) {
        return $value ? 1 : 0;
    }

    /**
     *
     * @param String $tableName
     * @param String $key
     * @param Mixed $value
     * @param array $schema
     * @param array $options
     * @return <type>
     */
    private function _operator($tableName,$key, $value, array $schema = array(), array $options = array()) {
        $defaults = array('boolean' => 'AND');
        $options += $defaults;

        list($op, $value) = each($value);

        $config = $this->_operators[$op];
        $key = "{$tableName}.{$key}";
        $values = array();

                if(count($value) > 0){
                    foreach ((array) $value as $val) {
                            $values[] = $this->value($val, $schema);
                    }
                }
        switch (true) {
            case (isset($config['format'])):
                return $key . ' ' . $this->format($config['format'],$values);
            case (count($values) > 1 && isset($config['multiple'])):
                $op = $config['multiple'];
                $values = join(', ', $values);
                return "{$key} {$op} ({$values})";
            case (count($values) > 1):
                return join(" {$options['boolean']} ", array_map(
                    function($v) use ($key, $op) { return "{$key} {$op} {$v}"; }, $values
                ));
        }
        return "{$key} {$op} {$values[0]}";
    }

    /**
     * To create the between statement
     *
     * Eg: Format = 'BETWEEN ? AND ?'
     *     Values = array(10,20)
     *       Return BETWEEN 10 AND 20
     *
     * @param String $format
     * @param Array $values
     * @return String
     */
    private function format($format,$values) {
        if(strpos($format, '?')){
            $offset = 0;
            while (($pos = strpos($format, '?', $offset)) !== false) {
                $val = array_shift($values);
                $offset = $pos + strlen($val);
                $format = substr_replace($format, $val, $pos, 1);
            }
        }
        return $format;
    }

    /**
     * Function to generate join query
     * @param <string> $tablename
     * @param <array> $arrrelationtables
     * @return string
     */
    private function getjoinquery($tablename,$arrrelationtables){
        $strJoinQuery = "";
        $arrRelations = $this->arrSchemaRelations;
        if(!array_key_exists($tablename, $arrRelations))return FALSE;
        foreach ($arrRelations[$tablename] as $arrSubRelation => $value){
            $childTableNameWithAlias = $alias = "";
            $parentTableWithAlias = $tablename;
            if(isset($arrRelations[$tablename]['alias'])){
                $parentTableWithAlias = $arrRelations[$tablename]['alias'];
            }
           
            $alias = $childTableNameWithAlias = $arrSubRelation;
            if(array_key_exists('tableName', $value)){
                $childTableNameWithAlias = "{$value['tableName']} AS {$value['alias']}";
                $alias = $value['alias'];
            }

            if(in_array($arrSubRelation, $arrrelationtables)|| array_key_exists($arrSubRelation, $arrrelationtables)){
                $arrSubRelValues = $value;
                $strJoinQuery .= " ".$arrSubRelValues['type']." ".$childTableNameWithAlias." ON (".$alias.".".$arrSubRelValues['childkey']." = ".$parentTableWithAlias.".".$arrSubRelValues['parentkey'].") ";
            }
        }
    if(count($arrrelationtables) > 0){
        foreach ($arrrelationtables as $parent => $child) {
            if(is_string($parent)){
//                if(array_key_exists('tableName', $value))$arrSubRelation = $value['tableName'];
                $strJoinQuery .= $this->getjoinquery($parent, $child);
            }
        }
    }
//        pr($strJoinQuery);
        return $strJoinQuery;
    }

    /**
     * Function to get the query to load the schema of table
     * @param <type> $strTblName
     * @return string
     * @access Private
     */
    private function getSchemaQuery($strTblName) {
        $strSchemaQuery = "";
        if(count($this->dbConfigs) > 0){

            switch ($this->dbConfigs["driver"]) {
                case "mysql":
                    $strSchemaQuery = "SELECT `information_schema`.COLUMNS.COLUMN_NAME AS 'Field',
                        lcase(`information_schema`.COLUMNS.DATA_TYPE) AS 'Type',
                        SUBSTRING(`information_schema`.COLUMNS.COLUMN_TYPE,
                        (INSTR(`information_schema`.COLUMNS.COLUMN_TYPE,'(')+1),
                        (INSTR(`information_schema`.COLUMNS.COLUMN_TYPE,')') - (INSTR(`information_schema`.COLUMNS.COLUMN_TYPE,'(')+1)))
                        AS Length,
                        `information_schema`.COLUMNS.IS_NULLABLE AS 'Null',
                        `information_schema`.COLUMNS.COLUMN_DEFAULT AS 'Default',
                        (CASE `information_schema`.COLUMNS.COLUMN_KEY WHEN 'PRI' THEN 1 ELSE 0 END) AS 'Key',
                        `information_schema`.COLUMNS.NUMERIC_SCALE AS 'size'

                        FROM `information_schema`.COLUMNS
                        WHERE `information_schema`.COLUMNS.TABLE_SCHEMA = '" . $this->dbConfigs['db'] . "'
                        AND `information_schema`.COLUMNS.TABLE_NAME = '" . $strTblName . "'";
                    break;

                case "mssql":
                    $strSchemaQuery = "SELECT COLUMN_NAME as Field,
                                        lower(DATA_TYPE) as Type,
                                        COL_LENGTH('" . $strTblName . "', COLUMN_NAME) as Length,
                                        IS_NULLABLE As [Null],
                                        COLUMN_DEFAULT as [Default],
                                        COLUMNPROPERTY(OBJECT_ID('" . $strTblName . "'),
                                        COLUMN_NAME, 'IsIdentity') as [Key],
                                        NUMERIC_SCALE as Size
                                        FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '" . $strTblName . "';";
                    break;

                case "sqlsrv":
                    $strSchemaQuery = "SELECT COLUMN_NAME as Field,
                                        lower(DATA_TYPE) as Type,
                                        COL_LENGTH('" . $strTblName . "', COLUMN_NAME) as Length,
                                        IS_NULLABLE As [Null],
                                        COLUMN_DEFAULT as [Default],
                                        COLUMNPROPERTY(OBJECT_ID('" . $strTblName . "'),
                                        COLUMN_NAME, 'IsIdentity') as [Key],
                                        NUMERIC_SCALE as Size
                                        FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '" . $strTblName . "';";
                    break;
            }
        }

        return $strSchemaQuery;
    }

    /**
     * Function to make a db connection
     * @param <array> $arrConfigs
     */
    private function dbConnect($arrConfigs) {
        try {
            if (count($this->dbConfigs) > 0) {
                switch ($arrConfigs["driver"]) {
                    case "mysql":
                        $this->objDbConn = new PDO($arrConfigs["driver"] . ":host=" . $arrConfigs["host"] . ";dbname=" . $arrConfigs["db"],
                                        $arrConfigs["user"],
                                        $arrConfigs["pwd"]);
                        break;

                    case "mssql":
                        $this->objDbConn = new PDO($arrConfigs["driver"] . ":host=" . $arrConfigs["host"] . ";dbname=" . $arrConfigs["db"],
                                        $arrConfigs["user"],
                                        $arrConfigs["pwd"]);
                        break;

                    case "sqlsrv":
                        $this->objDbConn = new PDO($arrConfigs["driver"] . ":server=" . $arrConfigs["host"] . ";database=" . $arrConfigs["db"],
                                        $arrConfigs["user"],
                                        $arrConfigs["pwd"]);
                        break;
                }

                if (!$this->objDbConn) {
                    print "No database connection is established...";
                }
            }
        } catch (PDOException $e) {
            print $e->getMessage();
        }
    }

    /**
     * Function to execute queries
     * @param <string> $strQuery
     * @return <array>
     */
    private function query($strQuery) {
        try {
            $result = $this->objDbConn->query($strQuery);
            if(!$result)pr($this->objDbConn->errorInfo ());
            return $result;
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * Function to fetch the result as associative array
     * @param <result set> $result
     * @return <array>
     */
    private function fetchValues(&$result) {
        $result->setFetchMode(PDO::FETCH_ASSOC);
        $records = $result->fetchAll();
        return $records;
    }

    /**
     * function to load the data types according to the driver
     */
    private function loadDataTypes() {
        if(count($this->dbConfigs) > 0){
            switch ($this->dbConfigs["driver"]) {
                case "mysql":
                    $this->arrDataTypes = array('char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext', 'tinyblob', 'mediumblob',
                        'blob', 'longblob', 'enum', 'set', 'date', 'datetime', 'time', 'year');
                    break;

                case "mssql":
                    $this->arrDataTypes = array('smalldatetime', 'datetime', 'char', 'varchar', 'text', 'nchar', 'nvarchar', 'ntext', 'binary', 'varbinary');
                    break;

                case "sqlsrv":
                    $this->arrDataTypes = array('smalldatetime', 'datetime', 'char', 'varchar', 'text', 'nchar', 'nvarchar', 'ntext', 'binary', 'varbinary','date');
                    break;
            }
        }
    }
}
?>