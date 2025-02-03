function equalHeight(group) {
    var tallest = 0;
    group.each(function() {
        var thisHeight = $(this).height();
        if (thisHeight > tallest) {
            tallest = thisHeight;
        }
    });
    group.height(tallest);
}

function StoreVideo(id, category, owner, title) {
    if (id) {

        $.get("ytajaxsave.php", {
                category: category,
                owner: owner,
                id: id
            },
            function(data) {
                console.log(data);
                if (data == 1) {
                    Snackbar.show({
                        text: '[Saved] ' + title,
                        duration: 3000,
                        pos: 'bottom-right'
                    });
                } else {
                    if (data == 0) {
                        Snackbar.show({
                            text: '[Duplicate] ' + title,
                            duration: 3000,
                            pos: 'bottom-right'
                        });
                    } else {
                        Snackbar.show({
                            text: '[Failed] ' + title,
                            duration: 3000,
                            pos: 'bottom-right'
                        });
                    }
                };
                return data;
            });
    }
};

var getUrlParams = function(url) {
    var params = {};
    (url + '?').split('?')[1].split('&').forEach(
        function(pair) {
            pair = (pair + '=').split('=').map(decodeURIComponent);
            if (pair[0].length) {
                params[pair[0]] = pair[1];
            }
        });
    return params;
};

function activaTab(tab) {
    $('.nav-tabs a[href="#' + tab + '"]').tab('show');
    $("#checkytLink").removeClass("input-special");
};

function PopulateChannel(name) {
    $("input[name=channelID]").val(name);
    $("input[name=channelID]").addClass("input-special");
    activaTab('channel');
};

function saveYtVideo(ytID) {

    StoreVideo(ytID, null, null, 'Video is now available');

};

jQuery(function($) {
    equalHeight($(".card"));
    $('#ytsaveallchecked').click(function() {

        $('.cards').find(':checkbox').each(function() {
            if ($(this).is(':checked')) {
                var theid = $(this).val();
                if (typeof theid !== "undefined") {
                    var ytcat = $(this).data('category');
                    var yto = $(this).data('owner');
                    var title = $(this).closest('.card').find('.cardTitle').text();
                 StoreVideo(theid, ytcat, yto, title);
                }
            }
        });
    });


    $('#ytcheckall').click(function() {
        $('.cards').find(':checkbox').each(function() {
            $(this).attr('checked', true);
            $(this).parent().addClass('checked');
        });
        $('#ytcheckall').toggleClass('hide');
        $('#ytuncheckall').toggleClass('hide');

    });
    $('#ytuncheckall').click(function() {
        $('.cards').find(':checkbox').each(function() {
            $(this).attr('checked', false);
            $(this).parent().removeClass('checked');

        });
        $('#ytcheckall').toggleClass('hide');
        $('#ytuncheckall').toggleClass('hide');

    });
    $("a.SaveYoutubeVideo").click(function() {
        var ytid = $(this).attr('id');
        var ytcat = $(this).data('category');
        var yto = $(this).data('owner');
        var title = $(this).closest('.card').find('.cardTitle').text();
        console.log(ytid);
        StoreVideo(ytid, ytcat, yto, title);
        $(this).closest('.card').prepend('<div class="ytsaved"></div>');
        $(this).closest(".card").find("a.removeytItem").remove();
        $(this).remove();
    });
    $(".removeytItem").click(function() {
        $(this).closest(".card").remove();
    });


    $("#checkytLink").bind("paste keyup", function(e) {
        // alert('fired');
        var inputData = $("#checkytLink").val();
        $(".input-special").removeClass("input-special");
        if ((inputData.indexOf("/channel") >= 0) || (inputData.indexOf("/@") >= 0)) {
            var canal = inputData.split('/channel/');
            if (typeof canal[1] == 'undefined') {
                canal = inputData.split('/@');
            }
            var rezultat = {
                type: 'channel',
                channel: canal[1]
            };

            $("input[name=channelID]").val(canal[1]);
            $("input[name=channelID]").addClass("input-special");
            activaTab('channel');


        } else {

            var rezultat = getUrlParams(inputData);
            console.log(rezultat);

            if (typeof rezultat.list !== 'undefined') {
                rezultat.type = 'playlist';
                $("#playlistIDsearch").val(rezultat.list);
                $("#playlistIDsearch").addClass("input-special");
                activaTab('playlist');
            }
            if (typeof rezultat.search_query !== 'undefined') {
                rezultat.type = 'search';
                rezultat.q = rezultat.search_query.replaceAll(/\+/g, " ");
                // $( "#rezultatword").val(rezultat.q);
                $("input[name=videoq]").val(rezultat.q);
                $("input[name=videoq]").addClass("input-special");
                activaTab('search');
            }
            if (typeof rezultat.v != 'undefined') {
                rezultat.type = 'video';
                rezultat.videoid = rezultat.v;
                if (typeof rezultat.ab_channel !== 'undefined') {
                    rezultat.channel = rezultat.ab_channel;
                }
                var suggestalt = '<div class ="ytclarify"> Are you by any chance looking to import the video # <a href="javascript:saveYtVideo(\'' + rezultat.videoid + '\');"> ' + rezultat.videoid + '</a> ';
                $('#ytsuggestalternatives').html(suggestalt);
                if (typeof rezultat.ab_channel !== 'undefined') {
                    $('#ytsuggestalternatives').html(suggestalt + ' or the channel <a href="javascript:PopulateChannel(\' ' + rezultat.channel + '\');"> ' + rezultat.channel + '</a>?</div>');
                }
            }

        }
        console.log(rezultat);
    });
    $('#ytsuggestuser').magicSuggest({
        allowFreeEntries: false,
        value: [ setlocaluser],
        maxSelection: 1,
        //name: 'owner',
        placeholder: 'Type and select a worthy user',
        data: admin_url + 'get_users.php',
        valueField: 'id',
        displayField: 'name'
    });
    $('#ytselectcategory').magicSuggest({
        allowFreeEntries: false,
        value: [ setlocalcategory ],
        maxSelection: 100,
        //name: 'categ',
        placeholder: 'A fit category (or more)',
        data: admin_url + 'get_categories.php',
        valueField: 'id',
        displayField: 'name'
    });
});