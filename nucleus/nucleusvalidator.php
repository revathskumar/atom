<?php
/*
 * Validation Core Class
 *
 * @author habid
 * @package atom
 * @copyright
 * @license
 * @version 1.0
 * @created date 23-11-2010
 *
 * Main methodes are
 *
 * _isEmpty($strValue)                                         : Used to check a value is present or not
 * _isMinimumLenth($strValue, $minimumLength=6)                : Used to Check a string/value is contain minimum lenth
 * _isMaximumLenth($strValue, $maximumLength=15)               : Used to check a string/value exceeds maximum lenth
 * _isNumeric($strValue)                                       : Used to check a string contains only numeric value
 * _isAlphabetic($strValue,$allowableCharacter)                : Used to check a string contains only alphabetic letter
 * _isAlphanumeric($strValue)                                  : Used to check a string contains only alpha numeric letter
 * _isDate($strDate,$format="ymd")                             : Used to Check a string is a date value
 * _isTime($strTime)                                           : Used to check a string is a time value
 * _isEmail($strEmailAddress)                                  : Used to check a string is a valid email address
 * _isUrl($strUrl)                                             : Used to chkeck a string a valid url
 * _compareValues($strValue1, $strValue2,$caseSensitive=false) : Used to Compare two string
 * _isBetween($strValue, $maxLength, $minLength = 0)           : Used to Check the length of a string between two range
 * _isInRange($iValue,$lowerValue,$upperValue)                 : Used to check a value is in range provided
 * _isFileExtension($fileName, $extensions = array(ext))       : Used to check a file extenstion is valide extention provided
 *
 */

namespace validator;

require ROOT_FOLDER.DS.'prj'.DS.'schemarules'.DS.'clsschemarules.php';
require ROOT_FOLDER.DS.'prj'.DS.'schemarules'.DS.'clsschemaalias.php';

define("E_VAL_REQUIRED","Please enter the value");
define("E_VAL_MAXLEN_EXCEEDED","Maximum length exceeded");
define("E_VAL_MINLEN_CHECK_FAILED","Minimum Lenght required");
define("E_VAL_ALNUM_CHECK_FAILED","Only Alpha-Numeric value is allowed");
define("E_VAL_NUM_CHECK_FAILED","Only Numeric value allowed");
define("E_VAL_ALPHA_CHECK_FAILED","Only alphabetic letter are allowed");
define("E_VAL_CORRECT_DATE","Please enter the correct date");
define("E_VAL_CORRECT_TIME","Please enter the correct time");
define("E_VAL_CORRECT_URL","Please enter the correct URL");
define("E_VAL_EMAIL_CHECK_FAILED","Please provide a valid email address");
define("E_VAL_COMPARE_CHECK_FAILED","The Feilds are not Equal");
define("E_VAL_BETWEEN_CHECK_FAILED","The value must in between two values");
define("E_VAL_INRANGE_CHECK_FAILED","The value must in between two range");
define("E_VAL_FILE_CHECK_FAILED","Not a valid File");

class nucleusvalidator extends \schema\rules\clsschemarules {


    var $arrError=array();
    private $objTblAlias;

    function  __construct() {
        $this->objTblAlias = new \schema\alias\clsschemaalias();
    }

        /**
     * For reguler Expression Checking
     *
     * @param string $pattern the pattern of reguler Expression .
     * @param string $value the value to be check
     * @return boolean TRUE if the present a value, FALSE if not
     * @access private
     */
    private static function regCheck($pattern ,$value) {
        if (preg_match($pattern,$value)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Validate if a string contains a valid value
     *
     * @param string $strValue The value to check .
     * @return boolean TRUE if the present a value, FALSE if not
     * @access private
     */
    private function _isEmpty($strValue) {
        if(\is_array($strValue) && count($strValue) > 0){
            return false;
        }

        if (strlen(trim($strValue)) < 1 || is_null($strValue)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Validate if a string contains a Minimum Length
     *
     * @param string $strValue The value to check the minimum Length
     * @param integer $minimumLength [Optional] is the minimum length of value
     * @return boolean TRUE if the value contains minimum lenth, FALSE if not
     * @access private
     */
    private function _isMinimumLenth($strValue, $minimumLength=6) {
        if (strlen($strValue) < $minimumLength ){
            return true;
        }
        else{
            return false;
        }
    }

    /**
     * Validate if a string exceeds maximum lenth
     *
     * @param string $strValue The value to check the maximum length
     * @param integer $maximumLength [Optional] is the maximum length of value
     * @return boolean TRUE if the value with in maximum lenth, FALSE if not
     * @access private
     */
    private function _isMaximumLenth($strValue, $maximumLength=15) {
        if (strlen($strValue) > $maximumLength ){
            return true;
        }
        else{
            return false;
        }
    }

    /**
     * Validate if a string contains only numeric value
     *
     * @param string $strValue The value to check numeric value
     * @return boolean TRUE if the value is numeric, FALSE if not
     * @access private
     */
    private function _isNumeric($strValue) {
      $pattern="/^\-?\+?[0-9e1-9]+$/";
      return self::regCheck($pattern, $strValue);
    }

    /**
     * Validate if a string contains only alphabetic letter
     *
     * @param string $strValue The value to check the alphebetic value
     * @param string $allowableCharacter additional Allowable character exept a-z to check alphabetic value
     * @return boolean TRUE if the value is alphabetic, FALSE if not
     * @access private
     */
    private function _isAlphabetic($strValue,$allowableCharacter="") {
        $pattern ='/^[a-zA-Z' . $allowableCharacter . ']+$/';
        return self::regCheck($pattern, $strValue);
    }

    /**
     * Validate if a string contains only alpha numeric letter
     *
     * @param string $strValue The value to check the alphebetic value
     * @return boolean TRUE if the value is alphanumeric, FALSE if not
     * @access private
     */
    private function _isAlphanumeric($strValue){
        $pattern="/^[A-Za-z0-9 ]+$/";
        return self::regCheck($pattern, $strValue);
    }

    /**
     * Validate if a string contains a valid date
     *
     * @param string $strDate The value to check the Date
     * @param mixed $format [Default : y-m-d] The format of date to be check or pass it an array Eg: array('dmy','mdy','dMy')
     * 	Keys: dmy 23-11-2010 or 23-11-10 separators can be a space, period, dash, forward slash
     * 	mdy 11-23-2010 or 11-23-10 separators can be a space, period, dash, forward slash
     * 	ymd 2010-11-23 or 10-11-23 separators can be a space, period, dash, forward slash
     * 	dMy 23 November 2010 or 23 Nov 2010
     * 	Mdy November 23, 2010 or Nov 23, 2010 comma is optional
     * 	My November 2010 or Nov 2010
     * 	my 11/2010 separators can be a space, period, dash, forward slash
     * @return boolean TRUE if the value is a valide Date, FALSE if not
     * @access private
     */
    private function _isDate($strDate,$format="ymd") {
        $bFound = false;
        $pattern['dmy'] = '%^(?:(?:31(\\/|-|\\.|\\x20)(?:0?[13578]|1[02]))\\1|(?:(?:29|30)(\\/|-|\\.|\\x20)(?:0?[1,3-9]|1[0-2])\\2))(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$|^(?:29(\\/|-|\\.|\\x20)0?2\\3(?:(?:(?:1[6-9]|[2-9]\\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00))))$|^(?:0?[1-9]|1\\d|2[0-8])(\\/|-|\\.|\\x20)(?:(?:0?[1-9])|(?:1[0-2]))\\4(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$%';
        $pattern['mdy'] = '%^(?:(?:(?:0?[13578]|1[02])(\\/|-|\\.|\\x20)31)\\1|(?:(?:0?[13-9]|1[0-2])(\\/|-|\\.|\\x20)(?:29|30)\\2))(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$|^(?:0?2(\\/|-|\\.|\\x20)29\\3(?:(?:(?:1[6-9]|[2-9]\\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00))))$|^(?:(?:0?[1-9])|(?:1[0-2]))(\\/|-|\\.|\\x20)(?:0?[1-9]|1\\d|2[0-8])\\4(?:(?:1[6-9]|[2-9]\\d)?\\d{2})$%';
        $pattern['ymd'] = '%^(?:(?:(?:(?:(?:1[6-9]|[2-9]\\d)?(?:0[48]|[2468][048]|[13579][26])|(?:(?:16|[2468][048]|[3579][26])00)))(\\/|-|\\.|\\x20)(?:0?2\\1(?:29)))|(?:(?:(?:1[6-9]|[2-9]\\d)?\\d{2})(\\/|-|\\.|\\x20)(?:(?:(?:0?[13578]|1[02])\\2(?:31))|(?:(?:0?[1,3-9]|1[0-2])\\2(29|30))|(?:(?:0?[1-9])|(?:1[0-2]))\\2(?:0?[1-9]|1\\d|2[0-8]))))$%';
        $pattern['dMy'] = '/^((31(?!\\ (Feb(ruary)?|Apr(il)?|June?|(Sep(?=\\b|t)t?|Nov)(ember)?)))|((30|29)(?!\\ Feb(ruary)?))|(29(?=\\ Feb(ruary)?\\ (((1[6-9]|[2-9]\\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00)))))|(0?[1-9])|1\\d|2[0-8])\\ (Jan(uary)?|Feb(ruary)?|Ma(r(ch)?|y)|Apr(il)?|Ju((ly?)|(ne?))|Aug(ust)?|Oct(ober)?|(Sep(?=\\b|t)t?|Nov|Dec)(ember)?)\\ ((1[6-9]|[2-9]\\d)\\d{2})$/';
        $pattern['Mdy'] = '/^(?:(((Jan(uary)?|Ma(r(ch)?|y)|Jul(y)?|Aug(ust)?|Oct(ober)?|Dec(ember)?)\\ 31)|((Jan(uary)?|Ma(r(ch)?|y)|Apr(il)?|Ju((ly?)|(ne?))|Aug(ust)?|Oct(ober)?|(Sept|Nov|Dec)(ember)?)\\ (0?[1-9]|([12]\\d)|30))|(Feb(ruary)?\\ (0?[1-9]|1\\d|2[0-8]|(29(?=,?\\ ((1[6-9]|[2-9]\\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]|[3579][26])00)))))))\\,?\\ ((1[6-9]|[2-9]\\d)\\d{2}))$/';
        $pattern['My'] = '%^(Jan(uary)?|Feb(ruary)?|Ma(r(ch)?|y)|Apr(il)?|Ju((ly?)|(ne?))|Aug(ust)?|Oct(ober)?|(Sep(?=\\b|t)t?|Nov|Dec)(ember)?)[ /]((1[6-9]|[2-9]\\d)\\d{2})$%';
        $pattern['my'] = '%^(((0[123456789]|10|11|12)([- /.])(([1][9][0-9][0-9])|([2][0-9][0-9][0-9]))))$%';

        $format = (is_array($format)) ? array_values($format) : array($format);
        foreach ($format as $key) {
            return self::regCheck($pattern[$key], $strDate);
        }
        return $bFound;

    }

    /**
     * Validate if a string contains a valid Time
     * Validate time as 24hr (HH:MM) or am/pm ([H]H:MM[a|p]m)
     * Eg: 15:20 or 01:12am
     * @param $strTime The value to check the Time
     * @return boolean TRUE if the value is a valide Time, FALSE if not
     * @access private
     */
    private function _isTime($strTime){
        $pattern = "%^((0?[1-9]|1[012])(:[0-5]\d){0,2}([AP]M|[ap]m))$|^([01]\d|2[0-3])(:[0-5]\d){0,2}$%";
        return self::regCheck($pattern, $strTime);
    }
    /**
     * Validate if a string contains a valid Email Address
     *
     * @param string $strEmailAddress The value of Email Address
     * @return boolean TRUE if the value is a valide Email Address, FALSE if not
     * @access private
     */
    private function _isEmail($strEmailAddress) {
        $pattern = "/^([a-zA-Z0-9])+([\.a-zA-Z0-9_-])*@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-]+)+/";
        return self::regCheck($pattern, $strEmailAddress) ;
    }


    /**
     * Validate if a string is a valide url
     *
     * @param string $strUrl The value of URL to be check
     * @return boolean TRUE if the value is a valide URL, FALSE if not
     * @access private
     */

    private function _isUrl($strUrl){
        $pattern ="/^http(s)?:\/\/([\w-]+\.)+[\w-]+(\/[\w- .\/?%&=]*)?$/i";
        return self::regCheck($pattern, $strUrl);
    }

    /**
     * Compare values between two string
     *
     * @param string $strValue1 The value of first String
     * @param string $strValue2 The value of second String
     * @param boolean $caseSensitive for checking the Case Sensitivity
     * @return boolean TRUE if the string are equal, FALSE if not
     * @access private
     */
    private function _compareValues($strValue1, $strValue2,$caseSensitive=false){
        if ($caseSensitive) {
            return ($strValue1 == $strValue2 ? true : false);
        } else {
            if (strtoupper($strValue1) == strtoupper($strValue2)) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Validate the length of a String Between the minimum and maximum values
     *
     * @param string  $value The value to check
     * @param integer $maxLength  The maximum allowable length of the value
     * @param integer $minLength [Optional] The minimum allowable length
     * @return boolean TRUE if the value between the minimum and maximum lenth, FALSE if not
     * @access private
     */
    private function _isBetween($strValue, $maxLength,$minLength = 0) {
        if (!(strlen($strValue) > $maxLength) && !(strlen($strValue) < $minLength)) {
            return true;
        } else {

            return false;
        }
    }


    /**
     * Validate a integer value between the specified Rang
     *
     * @param integer  $iValue The value to check
     * @param integer $lowerValue The Minimum value to be checking Started
     * @param integer $upperValue The Maximum value upto be checking
     * @return boolean TRUE if the value is in between the lower and upper value
     * @access private
     */

    private function _isInRange($iValue,$lowerValue,$upperValue){
        if (!self::_isNumeric($iValue)){
            return false;
        }
        if (isset($lowerValue) && isset($upperValue)) {
            return ($iValue > $lowerValue && $iValue < $upperValue);
        }

    }

    /**
     * Validate that value has a valid file extension.
     *
     * @param mixed $fileName Value to check
     * @param array $extensions file extenstions to allow ,Default 'doc','pdf','xls' , 'gif', 'jpef','png','jpg'
     * @return boolean Success
     * @access private
     */
    private function _isFileExtension($fileName, $extensions = array('application/msword','application/pdf','application/vnd.ms-excel','image/gif', 'image/jpeg', 'image/png')) {
       $found = false;
       if (in_array($filetype, $extensions)){
           $found=true;
       }
        return $found;
    }


    /**
     * Validate function in validation Class
     *
     * @param string $tableName the table name for validation
     * @param array $arrData  The values for the validation
     * @param array $arrRules  The rules specified for the validation
     * @return array if Validtion not specified rules not found in the data in given table
     * @access public
     */

    public function checkValidation($tableName,$arrData){
		$this->arrError = array();
      $arrRules = $this->getSchemaRules();
      $arrData = $this->objTblAlias->replaceAlias($arrData);
      $returnMsg = "";
      if ($this->checkKey($tableName, $arrRules)){
            foreach ($arrData[$tableName] as $field => $value) {
                if ($this->checkKey($field, $arrRules[$tableName])){
                    foreach($arrRules[$tableName][$field] as $field2 => $value2){
                       $returnMsg=$this->addValidation($field2, $value2,$value);
                       if ($returnMsg !="" || $returnMsg!=null){
                           $this->arrError[$tableName][$field]=$returnMsg;
                       }
                    }
                }
            }
        }
        
        if(sizeof($this->arrError) > 0){
            //throw new Exception("Validation Failed");
            $arrErrors = $this->objTblAlias->replaceSchemaNames($this->arrError);
            return $arrErrors;
        }
    }

      /**
     * Validate function in validation Class for file validation
     *
     * @param string $tableName the table name for validation
     * @param array $arrData  The values for the validation
     * @param array $arrRules  The rules specified for the validation
     * @return array if Validtion not specified rules not found in the data in given table
     * @access public
     */
    public function checkFileValidation($tableName, $arrData, $arrRules) {
        $arrRules = $this->getSchemaRules();
        $returnMsg ="";
        $arrData = $this->convertFileArr($tableName, $arrData);
        if ($this->checkKey($tableName, $arrRules)) {
            foreach ($arrData[$tableName] as $field => $value) {
                 if ($this->checkKey($field, $arrRules[$tableName])) {
                     foreach ($arrRules[$tableName][$field] as $field2 => $value2) {
                         $returnMsg = $this->addValidation($field2, $value2, $value['type']);
                         if ($returnMsg != "" || $returnMsg != null) {
                            $this->arrError[$tableName][$field] = $returnMsg;
                        }
                     }
                 }
            }
        }

         if (sizeof($this->arrError) > 0) {
            //throw new Exception("Validation Failed");
            return $this->arrError;
        }
    }

    /**
     * Function convert file array to given format
     *
     * @param string $tableName name of the table
     * @param array $arrData  The array for check
     * @return array the converted new array
     * @access private
     */
    private function convertFileArr($tableName, $arrData) {
        $fieldName = "";
        foreach ($arrData['name'][$tableName] as $key => $value) {
            $fieldName = $key;
        }
        foreach ($arrData as $key => $value) {
            $arr[$tableName][$fieldName][$key] = $value[$tableName][$fieldName];
        }
        return $arr;
    }

    /**
     * Function for check a key is exist or not in an array
     *
     * @param string $value Key Name
     * @param array $array  The array for check
     * @return boolean if sucsess
     * @access private
    */
    private  function checkKey($value,$array){
         if (array_key_exists($value, $array)){
             return true;
        }
        else{
            return false;
        }
    }

    /**
     * Function for check a validation and Rules
     *
     * @param string $validatorName name of validator Feild
     * @param array $arryValues  The array for check
     * @param string $value  The value for check for check
     * @return string if validation not found
     * @access private
    */
    private function addValidation($validatorName,$arryValues,$value){
        switch (strtolower($validatorName)){
            case 'notempty' :
               if ($arryValues['value'][0]){
                    if ($this->_isEmpty($value)){
                        //return $arryValues ['msg'];
                        return $this->errorMessage($arryValues,$validatorName);
                    }
               }
                break;
            case 'minimumlength':
                if ($this->_isMinimumLenth($value, $arryValues['value'][0]) ){
                    return $this->errorMessage($arryValues,$validatorName);
                }
                break;
            case 'maximumlength' :
                if ($this->_isMaximumLenth($value, $arryValues['value'][0])){
                     return $this->errorMessage($arryValues,$validatorName);
                }
                break;
            case 'numeric':
                if ($arryValues['value'][0]){
                   if (!$this->_isNumeric($value) && !$this->_isEmpty($value)) {
                       return $this->errorMessage($arryValues,$validatorName);
                    }
                }

                break;
           case 'alphabetic' :
               if ($arryValues['value'][0]) {
                    if (!$this->_isAlphabetic($value) && !$this->_isEmpty($value)) {
                        return $this->errorMessage($arryValues,$validatorName);
                    }
                }
               break;
           case 'alphanumeric' :
               if ($arryValues['value'][0]) {
                    if (!$this->_isAlphanumeric($value) && !$this->_isEmpty($value)) {
                        return $this->errorMessage($arryValues,$validatorName);
                    }
                }
               break;
           case 'date' :
               if (!$this->_isDate($value,$arryValues['value'][0])){
                   return $this->errorMessage($arryValues,$validatorName);
               }
               break;
           case 'time' :
               if (!$this->_isTime($value) && !$this->_isEmpty($value)){
                    return $this->errorMessage($arryValues,$validatorName);
                }
               break;
           case 'email' :
               if ($arryValues['value'][0]) {

                   if (!$this->_isEmail($value) && !$this->_isEmpty($value)) {
                        return $this->errorMessage($arryValues,$validatorName);
                    }

                }
               break;
           case 'url' :
               if ($arryValues['value'][0]) {
                    if (!$this->_isUrl($value) && !$this->_isEmpty($value)) {
                        return $this->errorMessage($arryValues,$validatorName);
                    }
                }
               break;
           case 'compare' :
                if (!$this->_compareValues($arryValues['value'][0], $arryValues['value'][1], $arryValues['value'][2])){
                    return $this->errorMessage($arryValues,$validatorName);
                }
               break;
           case 'inbetween' :
                if (!$this->_isBetween($value, $arryValues['value'][1], $arryValues['value'][0])){
                    return $this->errorMessage($arryValues,$validatorName);
                }
               break;
           case 'inrange' :
               if (!$this->_isInRange($value, $arryValues['value'][0], $arryValues['value'][1])){
                    return $this->errorMessage($arryValues,$validatorName);
               }
               break;
           case 'isfile' :
                if ($arryValues['value'][0]) {
                    if (!$this->_isFileExtension($value, $arryValues['value'][1])) {
                        return $this->errorMessage($arryValues, $validatorName);
                    }
                }
               break;
        }

    }

    /**
     * Function for Generate Error Message
     *
     * @param array $arryValues array of values and error message
     * @param string $validationKey  validator name
     * @return string Return Error messages
     * @access private
     */
    private function errorMessage($arryValues,$validationKey){

        $defaultErrorMessage="";
        switch (strtolower($validationKey)){
            case 'notempty' :
                $defaultErrorMessage=E_VAL_REQUIRED;
                break;
            case 'minimumlength':
                $defaultErrorMessage=E_VAL_MINLEN_CHECK_FAILED;
                break;
            case 'maximumlength' :
                $defaultErrorMessage=E_VAL_MAXLEN_EXCEEDED;
                break;
            case 'numeric':
                $defaultErrorMessage=E_VAL_NUM_CHECK_FAILED;
                break;
            case 'alphabetic' :
                $defaultErrorMessage=E_VAL_ALPHA_CHECK_FAILED;
                break;
            case 'alphanumeric' :
                $defaultErrorMessage=E_VAL_ALNUM_CHECK_FAILED;
                break;
            case 'date' :
                $dateFormateMessage="";
                $dateFormat=$arryValues['value'][0];
                $arrDateformate=array();
                $lengthOfDateFormate=strlen($dateFormat);
                for($i=0;$i< $lengthOfDateFormate;$i++ ){
                    array_push($arrDateformate, substr($dateFormat, $i, 1));
                    //$dateFormateMessage .="aa";
                    if (substr($dateFormat, $i, 1)=="y"){
                        $dateFormateMessage .= "/".date (strtoupper( substr($dateFormat, $i, 1) ));
                    }
                    else{
                        $dateFormateMessage .="/".date (substr($dateFormat, $i, 1));
                    }
                 }

                $defaultErrorMessage=E_VAL_CORRECT_DATE ." [ ".substr($dateFormateMessage, 1) . " ]" ;
                break;
            case 'time' :
                $defaultErrorMessage=E_VAL_CORRECT_TIME;
                break;
            case 'email' :
                $defaultErrorMessage=E_VAL_EMAIL_CHECK_FAILED;
                break;
            case 'url' :
                $defaultErrorMessage=E_VAL_CORRECT_URL;
                break;
            case 'compare' :
                $defaultErrorMessage=E_VAL_COMPARE_CHECK_FAILED;
                break;
            case 'inbetween' :
                $defaultErrorMessage=E_VAL_BETWEEN_CHECK_FAILED;
                break;
            case 'inrange' :
                $defaultErrorMessage=E_VAL_INRANGE_CHECK_FAILED;
                break;
            case 'isfile' :
                $defaultErrorMessage=E_VAL_FILE_CHECK_FAILED;
                break;
        }

        if (array_key_exists('msg', $arryValues)) {
            if ($arryValues ['msg'] != "") {
                 return $arryValues ['msg'];
            }
            else{
                return $defaultErrorMessage;
            }
        }
        else{
            return $defaultErrorMessage;
        }
    }
}
?>