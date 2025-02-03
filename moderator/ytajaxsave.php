<?php  error_reporting(E_ALL);

//Vital file include

require_once("../load.php");
// physical path of admin
if( !defined( 'ADM' ) )
	define( 'ADM', ABSPATH.'/'.ADMINCP);
require_once( ADM.'/ymporter/youtube.class.php');

ob_start();

// physical path of admin

if( !defined( 'ADM' ) )

	define( 'ADM', ABSPATH.'/'.ADMINCP);

define( 'in_admin', 'true' );

require_once( ADM.'/adm-functions.php' );

require_once( ADM.'/adm-hooks.php' );
$tmp = array();

$id = toDb($_GET['id']);
if(!empty($id)) {
$thedata = array('allowduplicates' => 1); /* Dirty but works */
$youtube = new Youtube($config = array('cat' => toDb($_GET['category']), 'owner' => toDb($_GET['owner'])) );
$ytvideo = $youtube->getVideo($id);
$done = $youtube->StoreVideo($ytvideo);

echo (int)$done;
	
}
//That's all folks!

?>
