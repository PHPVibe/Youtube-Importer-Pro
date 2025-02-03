<?php function youtubelinks($txt = '') {
return $txt.'
<li class="LiHead"><a href="'.admin_url('ytimporter').'"><i class="material-icons">youtube_activity</i>Youtube Importer</a></li>
';
}
add_filter('before_plugins_menu', 'youtubelinks');

function youtubeimporterjsasset($txt = ''){
	return $txt.'<script type="text/javascript" src="'.admin_url().'ymporter/youtubeimporter.js"></script>';
}
add_filter('admin_custom_footerjs_links', 'youtubeimporterjsasset');
function youtubeimportercss($txt = ''){
    return $txt.'<link rel="stylesheet" href="'.admin_url().'ymporter/youtubeimporter.css"/>';
}
add_filter('admin_custom_css_links', 'youtubeimportercss');
?>