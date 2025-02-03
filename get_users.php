<?php  error_reporting(E_ALL);

//Vital file include

require_once("../load.php");

ob_start();
// physical path of admin

if( !defined( 'ADM' ) )

	define( 'ADM', ABSPATH.'/'.ADMINCP);

define( 'in_admin', 'true' );

require_once( ADM.'/adm-functions.php' );

require_once( ADM.'/adm-hooks.php' );
$term = isset($_REQUEST['q'])? $_REQUEST['q'] : '';
$term = toDB($term);
if(!empty($term)){
$users = $db->get_results("Select id, name from ".DB_PREFIX."users where name like '".$term."' or name like '%".$term."' or name like '%".$term."&' or email like '".$term."' ", ARRAY_A);
} else {
$users = $db->get_results("Select id, name from ".DB_PREFIX."users limit 0,10000 ", ARRAY_A);	
}
$myusers = array();
foreach ($users as $usr) {
$usr['name'] = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $usr['name']);
$usr['name'] = preg_replace('/[\x00-\x1F\x7F]/u', '', $usr['name']);
$myusers[] = $usr;
}

//print_r($myusers);
 echo json_encode($myusers)
//That's all folks!

?>
