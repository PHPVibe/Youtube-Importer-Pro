<?php $thedata = array();
require_once( ADM.'/ymporter/youtube.class.php');
foreach ($_GET as $urlitem => $urlvalue) {
    $thedata[$urlitem] = is_array($urlvalue) ? implode(',', $urlvalue) : urldecode($urlvalue);
}
$thedata['bpp'] = 50;

$autoSave = (isset($thedata['autosave']) && ($thedata['autosave'] <> 1 )) ? false : true;
$thedata['pageToken'] = isset($thedata['nextToken']) ? $thedata['nextToken'] : '';
$thedata['localcategory'] = isset($thedata['localcategory']) ? $thedata['localcategory'] : 1;
$thedata['localowner'] = isset($thedata['localowner']) ? $thedata['localowner'] : user_id();
$_SESSION['setlocaluser'] = $thedata['localowner'];
$_SESSION['setlocalcategory']  = $thedata['localcategory'];
$youtube = new Youtube($config = array('cat' => $thedata['localcategory'], 'owner' => $thedata['localowner']) );


if(isset($thedata['channelid'])) {
$channel = $youtube->getChannelById($thedata['channelid']);	
}elseif(isset($thedata['channelname'])) {
	$channel = $youtube->getChannelByName($thedata['channelname']);
} else{  
die ('Somethign went wrong');
}

if (!$autoSave) {
        echo '
        <div class="youtubeimporter-options">
        <a id="ytcheckall" class="btn btn-danger" href="javascript:void(0)">Check all </a>
        <a id="ytuncheckall" class="btn btn-warning hide" href="javascript:void(0)" class="hide"> Uncheck all </a>
        <a id="ytsaveallchecked" class="btn btn-success" href="javascript:void(0)" class="hide"> Save selected </a>
        </div>';
}

echo '<div class="youtubeimporter">

<div class="importerstage">
<div class="cards">
<div class="card youtubepresentation"  style="background-image: url(' . addslashes($channel->snippet->thumbnails->medium->url) . '); background-size: cover;">';
echo '<h5>Channel import</h5>
<h1> ' . _html($channel->snippet->title) . '</h1>';
echo '<h4> by ' . _html($channel->snippet->title) . '</h4> ';
echo '</div>';
$videos = array();
/*
$temppageToken = null;
for ($i=1; $i<100; $i++) {

    $videos = $youtube->listVideosbyChannel($channel->id,  $temppageToken, 50);
    $temppageToken = isset($youtube->page_info['nextPageToken']) ? $youtube->page_info['nextPageToken'] : '';
    if(is_empty($temppageToken)) { die();} else {echo $temppageToken;}

}

die();
*/
    if (!$autoSave) {
        /* List Videos for choosing */
		$videos = $youtube->listVideosbyChannel($channel->id,  $thedata['pageToken'], 50);
    } else {
        /* List Videos and save them automaticaly to database */
		$videos = $youtube->saveChannelVideos($channel->id, $thedata['pageToken'], 50);
    }


if (!empty($videos)) {
	  /* echo ('<pre><code>');
                print_r($videos);
                echo ('</code></pre>');
				exit(); */
    foreach ($videos as $v) {
        if(isset($v['id'])) {
       
      


        echo '<div class="card" id="card-' . $v['id'] . '">';
        $v['localcategory'] = $thedata['localcategory'];
        $v['localowner'] = $thedata['localowner'];
        echo '<div class="cardImage"> ';
        if (!$autoSave) {
            echo '<div class="checkingyt" ><input type = "checkbox" data-owner = ' . $v['localowner'] . ' data-category = ' . $v['localcategory'] . ' name = "checkRow[]" value = "' . $v['id'] . '" class="styled ytchecksingle" /></div>';
        }
        echo '<img src="' . $v['thumb'] . '"/>';
        echo '<div class="cardImageSide">';
        if (!$autoSave) {
            echo '<a id="' . $v['id'] . '" href="#" data-owner =' . $v['localowner'] . ' data-category =' . $v['localcategory'] . ' target="_blank" class="SaveYoutubeVideo roundBtn tipN" title="Save">
        <i class="material-icons">add</i></a>';
        }

        echo '<a  href="' . str_replace("watch?v=", "embed/", $v["url"]) . '"  target="_blank" data-dimbox="youtubeExample' . $v['id'] . '" data-dimbox-ratio="16x9" class="roundBtn roundGrey tipN" title="Preview"><i class="material-icons">link</i></a>';
        if (!$autoSave) {
            echo '<a href="#"  target="_blank" class="removeytItem roundBtn roundRed tipN" title="Remove from this list"><i class="material-icons">close</i></a>';
        }
        echo '</div>';

        if ($autoSave) {
            if ($v['saved'] == 1) {
                echo '<div class="ytduplicate ytgrabbed">Saved : Unique</div>';
            } else {
                echo '<div class="ytduplicate">Skipped : Duplicate</div>';
            }

        }
        echo '</div>';
        echo '<div class="cardTitle">' . $v['title'] . '</div>';


        echo '</div>';
    }
}
    $theurl = array();
    //print_r($youtube->page_info);
    echo '<div class="card cardPagination">';
    unset($thedata['pt']);
    $thedata['nextToken'] = isset($youtube->page_info['nextPageToken']) ? $youtube->page_info['nextPageToken'] : '';
    $thedata['prevToken'] = isset($youtube->page_info['prevPageToken']) ? $youtube->page_info['prevPageToken'] : '';

    foreach ( $thedata as $urlitem => $urlvalue) {
        $theurl[$urlitem] = urlencode($urlvalue);
    }
    $paginext = admin_url() . '?' . http_build_query(  $thedata);
    /* Next page */
    if (not_empty($thedata['nextToken'])) {
        echo '<a class="btn btn-primary btn-block" href="' . $paginext . '">Next Page</a>';
    }
    /* Previous page */
    if (not_empty($thedata['prevToken'])) {
        $thedata['nextToken'] = $thedata['prevToken'];
        foreach ($thedata as $urlitem => $urlvalue) {
            $theurl[$urlitem] = urlencode($urlvalue);
        }
        $pagiback = admin_url() . '?' . http_build_query($thedata);
        echo '<a class="btn btn-default btn-block" href="' . $pagiback . '">Previous</a>';
    }
    /* First page */
    $thedata['nextToken'] = $thedata['prevToken'] = '';
    foreach ($thedata as $urlitem => $urlvalue) {
        $theurl[$urlitem] = urlencode($urlvalue);
    }
    $pagifirst = admin_url() . '?' . http_build_query($thedata);
    echo '<a class="btn btn-default btn-block" href="' . $pagifirst . '">First page</a>';

    echo '<p class="cardfooter">Expected results: <br>' . round($youtube->page_info['totalResults'] / $youtube->page_info['resultsPerPage']) . ' pages of  ' . $youtube->page_info['resultsPerPage'] . ' videos each</p></div>';

} else {
    echo 'Result came back empty! Please check the data';
}

echo '</div></div></div>
';

