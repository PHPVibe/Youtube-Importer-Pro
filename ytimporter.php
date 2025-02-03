<?php if (isset($_POST['videoq']) || isset($_POST['playlistIDsearch']) || isset($_POST['channelName']) || isset($_POST['channelID'])) {

    $looking = array();
    if (isset($_POST['type'])) {
        $looking['resourcetype'] = $_POST['type'];
    }
    if (isset($_POST['videoq']) && not_empty($_POST['videoq'])) {
        if($looking['resourcetype'] == 'video') {
            $looking['sk'] = 'ytkeywordvideos';
            $looking['query'] = $_POST['videoq'];
        } else {
            $looking['sk'] = 'ytfindall';
            $looking['query'] = $_POST['videoq'];

        }

    } elseif (isset($_POST['playlistID']) && not_empty($_POST['playlistID'])) {
        $looking['sk'] = 'ytplaylistvideos';
        $looking['playlistid'] = $_POST['playlistID'];
    } else {

        if (isset($_POST['channelID']) && not_empty($_POST['channelID'])) {
            $looking['sk'] = 'ytchannelvideos';
            $looking['channelid'] = $_POST['channelID'];
        } elseif (isset($_POST['channelName']) && not_empty($_POST['channelName']) ) {
            $looking['sk'] = 'ytchannelvideos';
            $looking['channelname'] = $_POST['channelName'];
        }

    }


if (!isset( $looking['sk'])) {
die("Something went wrong");
}
    if (isset($_POST['categ'])) {
        $looking['localcategory'] = is_array($_POST['categ']) ? implode(',', $_POST['categ']) : urldecode($_POST['categ']);
    } else {
        $looking['localcategory'] = get_option('defaultcategory', 1);
    }
    $looking['localowner'] = isset($_POST['owner']) ? $_POST['owner'] : user_id();
    if (is_array($looking['localowner'])) {
        $looking['localowner'] = $looking['localowner'][0];
    }

    $looking['autosave'] = isset($_POST['autosave']) ? $_POST['autosave'] : 0;
    $looking['allowduplicates'] = isset($_POST['allowduplicates']) ? $_POST['allowduplicates'] : 0;

    $pageVars = array_filter($looking);
    unset($pageVars['sk']);
    $pc = admin_url($looking['sk']) . '&' . urldecode(http_build_query($pageVars));


//print_r($_POST);
//print_r($looking);
redirect($pc);
    exit();
}


?>
<h2 class=""> Youtube Importer</h2>
<?php
if (nullval(get_option('youtubekey', null))) { ?>
    <div class="msg-warning">Your Youtube API key is empty.</div>
    <div class="msg-info">Set your key <a href="<?php echo admin_url('ytsetts'); ?>"><strong>here</strong> first</a>!
    </div>
<?php } ?>

<div class="row" style="margin:20px 0 30px">
    <input type="text" name="checkytLink" id="checkytLink" class="input-lg col-md-8" value=""
           placeholder="You have a link?">

    <div id="ytsuggestalternatives"></div>
</div>


<ul class="nav nav-tabs nav-tabs-line" id="myTab">
    <li class="active"><a href="#search">Keywords</a></li>
    <li><a href="#playlist">Playlist</a></li>
    <li><a href="#channel">Channel</a></li>
</ul>
<form id="ytSave" class="form-horizontal styled" action="<?php echo admin_url('ytimporter'); ?>"
      enctype="multipart/form-data" method="post">
    <div class="tab-content" style="padding-top:35px">
        <div class="tab-pane active" id="search">
            <div class="row">

                <div class="form-group">

                    <div class="controls col-md-4">
                        <input type="text" name="videoq" class="input-lg " value=""
                               placeholder="Search here">
                        <span class="help-block">Type your keywords for this search </span>
                    </div>
                </div>




            </div>
        </div>

        <div class="tab-pane" id="playlist">
            <div class="row">

                <div class="form-group ">

                    <div class="controls col-md-4">
                        <label class="control-label"></i>Playlist ID</label>
                        <input type="text" id="playlistIDsearch" name="playlistID" class="input-lg " value=""
                               placeholder="RDl_yHhemwu2M">
                        <span class="help-block"><strong>List id.</strong> Check the Youtube url for the id, it will be contained by <code>&list=</code> <br>Ex: youtube.com/watch?v=l_yHhemwu2M&list=<code>RDl_yHhemwu2M</code> </span>
                    </div>
                </div>
            </div>

        </div>

        <div class="tab-pane" id="channel">
            <div class="row">

                <div class="form-group">
                    <div class="controls">
                        <div class="row">
                            <div class="col-md-4">
                                <input type="text" name="channelID" class="col-md-12 input-lg" value=""
                                       placeholder="UC6rKRuz5CkWC4l_Al8gZUpQ">
                                <span class="help-block">Channel ID. Ex: youtube.com/channel/<code>UC6rKRuz5CkWC4l_Al8gZUpQ</code> </span>
                            </div>
                        </div>
                        <div class="col-md-12"><strong class="badge badge-primary">OR</strong></div>
                        <div class="col-md-4">
                            <input type="text" name="channelName" value=""
                                   placeholder="AMI Official">
                            <span class="help-block">Channel's name (Less reliable!)</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <label class="control-label">Add to owner</label>
            <div class="controls">
                <div class="form-group col-md-4 col-xs-12">
                    <input id="ytsuggestuser" class="input-lg " name="owner"/>

                    <script>
<?php
if(!isset($_SESSION['setlocaluser'])) {
    echo 'var setlocaluser ='.user_id().';';
} else {
    echo 'var setlocaluser ='.$_SESSION['setlocaluser'].';';
}
if(!isset($_SESSION['setlocalcategory'])) {
    echo 'var setlocalcategory = 1;';
} else {
    echo 'var setlocalcategory ='.$_SESSION['setlocalcategory'].';';
}
?>

                    </script>
                </div>
            </div>
        </div>

        <div class="col-md-12">
            <label class="control-label">Add to category</label>
            <div class="controls">
                <div class="form-group col-md-4 col-xs-12">
                    <input id="ytselectcategory" class="input-lg " name="categ"/>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group form-material">
                <label class="control-label">Import mode</label>
                <div class="controls">
                    <label class="radio"><input type="radio" name="autosave" class="styled" value="1"> Automated (Save
                        all results directly) </label>
                    <label class="radio"><input type="radio" name="autosave" class="styled" value="2" checked> List
                        (Display & save by choice)</label>
                    <span class="help-block">Save videos before listing, or just pick what to import by hand? </span>
                </div>
            </div>
        </div>
    

        <div class="col-md-12">
            <div class="form-group form-material">
                <label class="control-label">Allow duplicate videos</label>
                <div class="controls">
                    <label class="radio "><input type="radio" name="allowduplicates" class="styled" value="1"> YES
                    </label>
                    <label class="radio "><input type="radio" name="allowduplicates" class="styled" value="0" checked>
                        NO </label>
                    <span class="help-block">If set to NO it will search if video is already in the database and skip it. </span>

                </div>
            </div>
        </div>
    </div>
    <div class="control-group" style="padding:40px 0 30px 10px;">
        <button type="submit" class="btn btn-large btn-primary">Start importing</button>

    </div>
</form>
</div>



