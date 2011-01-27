<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require ROOT_FOLDER.DS."atom".DS."dbos".DS."clsdbos.php";
require ROOT_FOLDER.DS."prj".DS."capsules".DS."common.php";
require ROOT_FOLDER.DS."prj".DS."capsules".DS."nclsacl.php";
/**
 * To switch from dev mode to Production mode
 */
if (DEV == true) {
	error_reporting(E_ALL);
	ini_set('display_errors','On');
} else {
	error_reporting(E_ALL);
	ini_set('display_errors','Off');
	ini_set('log_errors', 'On');
	ini_set('error_log', ROOT.DS.'tmp'.DS.'logs'.DS.'error.log');
}

//pr(ROOT_FOLDER.DS."atom".DS."dbos".DS."stash" .DS."positron".DS);

//setting session path , Name
if(!is_dir(ROOT_FOLDER.DS."atom".DS."stash"))mkdir (ROOT_FOLDER.DS."atom".DS."stash");
if(!is_dir(ROOT_FOLDER.DS."atom".DS."stash".DS."positron"))mkdir (ROOT_FOLDER.DS."atom".DS."stash".DS."positron");
session_save_path(ROOT_FOLDER.DS."atom".DS."stash" .DS."positron");
ini_set('session.gc_probability', 1);
ini_set('short_open_tag', 'Off');
ini_set('session.gc_maxlifetime', 45*60);

session_name("ATOMSESSID");

/** Autoload any classes that are required **/
function __autoload($className) {
	$strFileName = strtolower(substr($className, strpos( $className,'\\')+1));
	if(file_exists(ROOT_FOLDER.DS.'prj'.DS.'capsules'.DS.$strFileName.'.php')) {
		require_once(ROOT_FOLDER.DS.'prj'.DS.'capsules'.DS.$strFileName.'.php');
	}
}
$url="";
if(isset ($_GET['url']))$url = $_GET['url'];
elseif(strpos($_SERVER['REQUEST_URI'],'index.php') !== FALSE){
	$url = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'],'index.php')+10);
}
if($url == ""){
	$url = 'accounts/index';
}

$urlArray = explode("/",$url);
$tempUrl = $urlArray;
$capsuler = $urlArray[0];
array_shift($urlArray);
if(strpos($urlArray[0], '?'))$urlArray[0]  = substr($urlArray[0],0, strpos($urlArray[0], '?'));
$action = $urlArray[0];
array_shift($urlArray);
$queryString = $urlArray;
if(empty ($queryString) || $queryString[0] == ""){
	$queryString = null;
}
if($action == "")$action = "index";
session_start();
if(isset($_SESSION['crm_user']) && $action == 'login'){
	if(isset ($_SESSION['returnurl'])){
		redir($_SESSION['returnurl']);
	}
	else{
		redir(array('accounts','index'));
	}
}
//pr($_SESSION['returnurl']);

$capsulerName = $capsuler;
$capsule = strtolower($capsuler);

$capsuler = 'CRM\ncls'.$capsule;
//if( !in_array($action, array('login','logout'))){
//	$acl = new CRM\nclsacl();
//	$actionId = $acl->getActionId($capsule, $action);
////	pr($actionId);
//	$permission = $acl->checkPermission($actionId);
////	pr($permission);
//	if($permission != 1) {
////		pr($capsule ." ".$action);
//		$_SESSION['msg'] = "Access Denied";// {$capsule}  {$action} **** {$permission} ***---- {$actionId} ---- ```".$_SESSION['acl'][$actionId]."````";
//		if(empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
//			redir (array("accounts","index"));
//		}
//		else{
//		 echo json_encode(array("success" => FALSE,"msg" => 'Access Denied'));
//		 exit();
//		}
//	}
//}

$objCapsules = new $capsuler();

if(isset ($_POST['data']))$objCapsules->data = $_POST['data'];
/**
 * Checking the session and and Permissions
 * An before Callback
 */
if(isset($_SESSION['crm_user']) || in_array($action, array('login'))){
	$objCapsules->{$action}($queryString);
}
else{
	$auth = 'nclsauth';
	$_SESSION['returnurl'] = $tempUrl;
	redir(array('auth','login'));
	//exit();
}

extract($objCapsules->templateVars);

$strTemplateFileName = $capsulerName."_".  strtolower($action);
//pr($strTemplateFileName);
if(file_exists(ROOT_FOLDER.DS.'prj'.DS.'templates'.DS.strtolower($strTemplateFileName).'.phtml')){
	require_once(ROOT_FOLDER.DS.'prj'.DS.'templates'.DS.strtolower($strTemplateFileName).'.phtml');
}
else{
	print "File Not Found!!!";
}


/**
 * Function to check and display the values in
 * a given Array
 *
 * @param Array $arr
 */
function pr($arr) {
	if(DEV === FALSE)return;
    echo "<pre style=\"color:black\">";
    print_r($arr);
    echo "</pre>";
}

function redir($url) {
	
	if(is_array($url)){
		$url = implode('/', $url);
	}
	else{
		if(strpos($url, "http://")>=0){
			$url = substr($url, strlen("http://".$_SERVER['SERVER_NAME'].BASE_URL));
		}
	}
	header('location:'.BASE_URL. $url );
//	exit();
}
?>