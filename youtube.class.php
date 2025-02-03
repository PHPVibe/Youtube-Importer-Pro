<?php


class Youtube
{

    /**
     * @var string
     */
    protected $youtube_key; // from the config file
	/**
     * @var string
     */
	private $nextpagetoken; // from Youtube
    protected  $dbcategory = null; // default local category
    protected  $dbowner = null; // default local category

    /**
     * @var array
     */
    public $APIs = [
        'categories.list' => 'https://www.googleapis.com/youtube/v3/videoCategories',
        'videos.list' => 'https://www.googleapis.com/youtube/v3/videos',
        'search.list' => 'https://www.googleapis.com/youtube/v3/search',
        'channels.list' => 'https://www.googleapis.com/youtube/v3/channels',
        'playlists.list' => 'https://www.googleapis.com/youtube/v3/playlists',
        'playlistItems.list' => 'https://www.googleapis.com/youtube/v3/playlistItems',
        'activities' => 'https://www.googleapis.com/youtube/v3/activities',
        'commentThreads.list' => 'https://www.googleapis.com/youtube/v3/commentThreads',
    ];

    /**
     * @var array
     */
    public $youtube_reserved_urls = [
        '\/about\b',
        '\/account\b',
        '\/account_(.*)',
        '\/ads\b',
        '\/creators\b',
        '\/feed\b',
        '\/feed\/(.*)',
        '\/gaming\b',
        '\/gaming\/(.*)',
        '\/howyoutubeworks\b',
        '\/howyoutubeworks\/(.*)',
        '\/new\b',
        '\/playlist\b',
        '\/playlist\/(.*)',
        '\/reporthistory',
        '\/results\b',
        '\/shorts\b',
        '\/shorts\/(.*)',
        '\/t\/(.*)',
        '\/upload\b',
        '\/yt\/(.*)',
    ];

    /**
     * @var array
     */
    public $page_info = [];

    /**
     * @var array
     */
    protected $config = [];

    /**
     * Constructor
     * $youtube = new Youtube(['key' => 'KEY HERE'])
     *
     * @param string $key
     * @throws \Exception
     */
    public function __construct( $config = [])
    {
		$key =  get_option('youtubekey', null);
        if (is_string($key) && !empty($key)) {
            $this->youtube_key = $key;
        } else {
            throw new \Exception('Google API key is Required, please visit https://console.developers.google.com/');
        }
        $this->config['use-http-host'] = isset($config['use-http-host']) ? $config['use-http-host'] : false;
        $cat = isset($config['cat']) ? $config['cat'] : '';
        $this->setCategory($cat);
        $owner = isset($config['owner']) ? $config['owner'] : '';
        $this->setOwner($owner);


    }

    /**
     * @param $setting
     * @return Youtube
     */

    public function useHttpHost($setting)
    {
        $this->config['use-http-host'] = !!$setting;

        return $this;
    }
    /**
     * @param $key
     * @return Youtube
     */
    private function setCategory($cat)
    {
        $this->dbcategory = not_empty($cat) ? $cat : get_option('localcategory',1);

        return $this;
    }
    public function getCategory()
    {
        return $this->dbcategory;
    }
    /**
     * @param $key
     * @return Youtube
     */
   private function setOwner($owner)
    {
        $this->dbowner =  not_empty($owner) ? $owner : user_id();

        return $this;
    }
    public function getOwner()
    {
        return $this->dbowner;
    }
    /**
     * @param $key
     * @return Youtube
     */
    public function setApiKey($key)
    {
        $this->youtube_key = $key;

        return $this;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->youtube_key;
    }

    /**
     * @param $regionCode
     * @return \StdClass
     * @throws \Exception
     */
    public function getCategories($regionCode = 'US', $part = ['snippet'])
    {
        $API_URL = $this->getApi('categories.list');
        $params = [
            'key' => $this->youtube_key,
            'part' => implode(',', $part),
            'regionCode' => $regionCode
        ];

        $apiData = $this->api_get($API_URL, $params);
        return $this->decodeMultiple($apiData);
    }

    /**
     * @param string $videoId       Instructs the API to return comment threads containing comments about the specified channel. (The response will not include comments left on videos that the channel uploaded.)
     * @param integer $maxResults   Specifies the maximum number of items that should be returned in the result set. Acceptable values are 1 to 100, inclusive. The default value is 20.
     * @param string $order         Specifies the order in which the API response should list comment threads. Valid values are: time, relevance.
     * @param array $part           Specifies a list of one or more commentThread resource properties that the API response will include.
     * @param bool $pageInfo        Add page info to returned array.
     * @return array
     * @throws \Exception
     */
    public function getCommentThreadsByVideoId($videoId = null, $maxResults = 20, $order = null, $part = ['id', 'replies', 'snippet'], $pageInfo = false) {

        return $this->getCommentThreads(null, null, $videoId, $maxResults, $order, $part, $pageInfo);
    }

    /**
     * @param string $channelId     Instructs the API to return comment threads containing comments about the specified channel. (The response will not include comments left on videos that the channel uploaded.)
     * @param string $id            Specifies a comma-separated list of comment thread IDs for the resources that should be retrieved.
     * @param string $videoId       Instructs the API to return comment threads containing comments about the specified channel. (The response will not include comments left on videos that the channel uploaded.)
     * @param integer $maxResults   Specifies the maximum number of items that should be returned in the result set. Acceptable values are 1 to 100, inclusive. The default value is 20.
     * @param string $order         Specifies the order in which the API response should list comment threads. Valid values are: time, relevance.
     * @param array $part           Specifies a list of one or more commentThread resource properties that the API response will include.
     * @param bool $pageInfo        Add page info to returned array.
     * @return array
     * @throws \Exception
     */
    public function getCommentThreads($channelId = null, $id = null, $videoId = null, $maxResults = 20, $order = null, $part = ['id', 'replies', 'snippet'], $pageInfo = false)
    {
        $API_URL = $this->getApi('commentThreads.list');

        $params = array_filter([
            'channelId' => $channelId,
            'id' => $id,
            'videoId' => $videoId,
            'maxResults' => $maxResults,
            'part' => implode(',', $part),
            'order' => $order,
        ]);

        $apiData = $this->api_get($API_URL, $params);

        if ($pageInfo) {
            return [
                'results' => $this->decodeList($apiData),
                'info' => $this->page_info,
            ];
        } else {
            return $this->decodeList($apiData);
        }
    }

	/**
     * @param $videoId
     * @param array $part
     * @return \Array video details
     * @throws \Exception
     */
    public function getVideo($videoId, $part = ['id', 'snippet', 'contentDetails', 'status'])
	{
		$mediaid = 'ytsingle_'.$videoId.'_'.implode(',', $part);
		
		if(jc_exists($mediaid)) {
			/* Return from local cache to save quota */
			$video = jc_get($mediaid);		
		} else {
			$video =  $this->getVideoInfo($videoId, $part = ['id', 'snippet', 'contentDetails', 'status']);
			/* Cache it now to save quota */
			if($video && !empty($video)) {
			jc_put($mediaid, $video);
			}
		}
		
		return $this->MakeVideoPretty($video);
	}
	
  
	 /**
     * @param $singleVideoId
     * @param array $part
     * @return \StdClass
     * @throws \Exception
     */
    public function getVideoInfo($singleVideoId, $part = ['id', 'snippet', 'contentDetails', 'status'])
    {
        $API_URL = $this->getApi('videos.list');
        $params = [
            'id' => is_array($singleVideoId) ? implode(',', $singleVideoId) : $singleVideoId,
            'key' => $this->youtube_key,
            'part' => implode(',', $part),
        ];

        $apiData = $this->api_get($API_URL, $params);

        if (is_array($singleVideoId)) {
            return $this->decodeMultiple($apiData);
        }

        return $this->decodeSingle($apiData);
    }

    /**
     * Gets localized video info by language (f.ex. de) by adding this parameter after video id
     * Youtube::getLocalizedVideoInfo($video->url, 'de')
     *
     * @param $vId
     * @param $language
     * @param array $part
     * @return \StdClass
     * @throws \Exception
     */

    public function getLocalizedVideoInfo($vId, $language, $part = ['id', 'snippet', 'contentDetails', 'player', 'statistics', 'status']) {

        $API_URL = $this->getApi('videos.list');
        $params = [
            'id'    => is_array($vId) ? implode(',', $vId) : $vId,
            'key' => $this->youtube_key,
            'hl'    =>  $language,
            'part' => implode(',', $part),
        ];

        $apiData = $this->api_get($API_URL, $params);

        if (is_array($vId)) {
            return $this->decodeMultiple($apiData);
        }

        return $this->decodeSingle($apiData);
    }

    /**
     * Gets popular videos for a specific region (ISO 3166-1 alpha-2)
     *
     * @param $regionCode
     * @param integer $maxResults
     * @param array $part
     * @return array
     */
    public function getPopularVideos($regionCode, $maxResults = 10, $part = ['id', 'snippet', 'contentDetails', 'player', 'statistics', 'status'])
    {
        $API_URL = $this->getApi('videos.list');
        $params = [
            'chart' => 'mostPopular',
            'part' => implode(',', $part),
            'regionCode' => $regionCode,
            'maxResults' => $maxResults,
        ];

        $apiData = $this->api_get($API_URL, $params);

        return $this->decodeList($apiData);
    }

    /**
     * Simple search interface, this search all stuffs
     * and order by relevance
     *
     * @param $q
     * @param integer $maxResults
     * @param array $part
     * @return array
     */
    public function search($q, $pageToken = '', $maxResults = 50, $part = ['id', 'snippet'], $pageInfo = true)
    {
        $params = [
            'q' => $q,
            'part' => implode(',', $part),
            'maxResults' => $maxResults,
        ];

         return $this->searchAdvanced($params, $pageInfo, $pageToken);
    }

    /**
     * Search only videos
     *
     * @param  string $q Query
     * @param  integer $maxResults number of results to return
     * @param  string $order Order by
     * @param  array $part
     * @return \StdClass  API results
     */
    public function searchVideos($q,  $pageToken = '', $maxResults = 50, $order = null, $part = ['id'], $pageInfo = true)
    {
        $params = [
            'q' => $q,
            'type' => 'video',
            'part' => implode(',', $part),
            'maxResults' => $maxResults,
        ];
        if (!empty($order)) {
            $params['order'] = $order;
        }

        return $this->searchAdvanced($params, $pageInfo, $pageToken);
    }

    /**
     * Search only videos in the channel
     *
     * @param  string $q
     * @param  string $channelId
     * @param  integer $maxResults
     * @param  string $order
     * @param  array $part
     * @param  $pageInfo
     * @return array
     */
    public function searchChannelVideos($q, $channelId, $maxResults = 10, $order = null, $part = ['id', 'snippet'], $pageInfo = false)
    {
        $params = [
            'q' => $q,
            'type' => 'video',
            'channelId' => $channelId,
            'part' => implode(',', $part),
            'maxResults' => $maxResults,
        ];
        if (!empty($order)) {
            $params['order'] = $order;
        }

        return $this->searchAdvanced($params, $pageInfo);
    }
    /**
     * Search for videos and list them
     *
     * @param  string $q Query
     * @param  integer $maxResults number of results to return
     * @param  string $order Order by
     * @param  array $part
     * @return \StdClass  API results
     */
    public function searchListALL($q,  $pageToken = '', $maxResults = 25, $order = null, $part = ['id'], $pageInfo = true)
    {
        $videos =  $this->search($q, $pageToken, $maxResults, $part);
        echo '<pre>';
        print_r($videos);
        echo '</pre>';
        if($videos && not_empty($videos)) {
            $videosdone = array();
            foreach ($videos['results'] as $video) {
                $videoItem = $this->getVideo($video->id->videoId);
                if ($videoItem) {
                    $videosdone[$video->id->videoId] = $videoItem;
                    if (!ytExists($video->id->videoId)) {
                        $videosdone[$video->id->videoId]['saved'] = 1;
                    } else {
                        $videosdone[$video->id->videoId]['saved'] = 0;
                    }
                }

            }
            return $videosdone;
        }

        return false;
    }
    /**
     * Search for videos and list them
     *
     * @param  string $q Query
     * @param  integer $maxResults number of results to return
     * @param  string $order Order by
     * @param  array $part
     * @return \StdClass  API results
     */
    public function searchListVideos($q,  $pageToken = '', $maxResults = 50, $order = null, $part = ['id'], $pageInfo = true)
    {
       $videos =  $this->searchVideos($q, $pageToken);
       if($videos && not_empty($videos)) {
           $videosdone = array();
           foreach ($videos['results'] as $video) {
               $videoItem = $this->getVideo($video->id->videoId);
               if ($videoItem) {
                   $videosdone[$video->id->videoId] = $videoItem;
                   if (!ytExists($video->id->videoId)) {
                       $videosdone[$video->id->videoId]['saved'] = 1;
                   } else {
                       $videosdone[$video->id->videoId]['saved'] = 0;
                   }
               }

           }
           return $videosdone;
       }

       return false;
    }
    /**
     * Search for videos and save them directly
     *
     * @param  string $q Query
     * @param  integer $maxResults number of results to return
     * @param  string $order Order by
     * @param  array $part
     * @return \StdClass  API results
     */
    public function saveListVideos($q,  $pageToken = '', $maxResults = 50, $order = null, $part = ['id'], $pageInfo = true)
    {
        global $db, $thedata;

        $videos =  $this->searchVideos($q, $pageToken);
        $videosdone = array();
        $cvslines = "";
        $sqlheader = "INSERT INTO ".DB_PREFIX."videos  (`token`,`featured`,`pub`,`source`, `user_id`, `date`, `thumb`, `title`, `duration`, `views` , `liked` , `category`,`nsfw`) VALUES ";


        foreach ($videos['results'] as $video) {
            $videoItem = $this->getVideo($video->id->videoId);
            if ($videoItem) {
                $videosdone[$video->id->videoId] = $videoItem;
                if((isset($thedata['allowduplicates']) && ($thedata['allowduplicates'] > 0)) || !ytExists($video->id->videoId )) {

                    $singlevideo = $this->InsertPrepare($videoItem);


                    $cvslines .= "('" . $singlevideo["token"] . "','" . $singlevideo["featured"] . "','" . $singlevideo["state"] . "','" . $singlevideo["url"] . "', '" . $singlevideo["owner"] . "', now() , '" . $singlevideo["thumb"] . "', '" . toDb($singlevideo["title"]) . "', '" . intval($singlevideo["duration"]) . "', '0', '0','" . toDb($singlevideo["category"]) . "','0'),";




                    $videosdone[$video->id->videoId]['saved'] = 1;

                } else {
                    $videosdone[$video->id->videoId]['saved'] = 0;
                }
            }

        }
        if(not_empty($cvslines)){
            $Query = $sqlheader;
            $Query .=rtrim($cvslines, ',');
            $Query .= ';';
            $db->query($Query);
        }

        return $videosdone;
    }
	/**
     * Save videos from the channel
     *
     * @param  string $channelId
	 * @param  $pageToken
     * @param  integer $maxResults
     * @param  string $order
     * @param  array $part
     * @param  $pageInfo
     * @return array
     */
    public function saveChannelVideos($channelId, $pageToken = '', $maxResults = 50, $order = null, $part = ['id', 'snippet'], $pageInfo = true )

    { global $db;
        $channelVideos = $this->listChannelVideos($channelId, $pageToken, $maxResults, $order, $part, $pageInfo);

        $videosdone = array();

        if (isset( $channelVideos)){

                $cvslines = "";
                $sqlheader = "INSERT INTO ".DB_PREFIX."videos  (`token`,`featured`,`pub`,`source`, `user_id`, `date`, `thumb`, `title`, `duration`, `views` , `liked` , `category`,`nsfw`) VALUES ";

            foreach ($channelVideos['results'] as $video) {

                    $videoItem = $this->getVideo($video->id->videoId);
                    $videosdone[$video->id->videoId] = $videoItem;
                    if ($videoItem) {
                        if((isset($thedata['allowduplicates']) && ($thedata['allowduplicates'] > 0)) || !ytExists($video->id->videoId )) {

                            $singlevideo = $this->InsertPrepare($videoItem);


                            $cvslines .= "('" . $singlevideo["token"] . "','" . $singlevideo["featured"] . "','" . $singlevideo["state"] . "','" . $singlevideo["url"] . "', '" . $singlevideo["owner"] . "', now() , '" . $singlevideo["thumb"] . "', '" . toDb($singlevideo["title"]) . "', '" . intval($singlevideo["duration"]) . "', '0', '0','" . toDb($singlevideo["category"]) . "','0'),";




                            $videosdone[$video->id->videoId]['saved'] = 1;

                        } else {
                            $videosdone[$video->id->videoId]['saved'] = 0;
                        }
                    }
                }
                if(not_empty($cvslines)){
                    $Query = $sqlheader;
                    $Query .=rtrim($cvslines, ',');
                    $Query .= ';';
                    $db->query($Query);
                }



        }

        return $videosdone;
    }
	/**
     * List videos by channel
     *
     * @param  string $channelId
	 * @param  $pageToken
     * @param  integer $maxResults
     * @param  string $order
     * @param  array $part
     * @param  $pageInfo
     * @return array
     */
    public function listVideosbyChannel($channelId, $pageToken = '', $maxResults = 50, $order = null, $part = ['id', 'snippet'], $pageInfo = true )

    {
        $channelVideos = $this->listChannelVideos($channelId, $pageToken, $maxResults, $order, $part, $pageInfo);

        $videosdone = array();

        if (isset( $channelVideos)){


            foreach ($channelVideos['results'] as $video) {

                $videoItem = $this->getVideo($video->id->videoId);
                if($videoItem) {
                    $videosdone[$video->id->videoId] = $videoItem;
                    if(!ytExists($video->id->videoId )) {
                        $videosdone[$video->id->videoId]['saved'] = 1;
                    } else {
                        $videosdone[$video->id->videoId]['saved'] = 0;
                    }
                }


            }

        }

        return $videosdone;
    }
    /**
     * List videos in the channel
     *
     * @param  string $channelId
     * @param  integer $maxResults
     * @param  string $order
     * @param  array $part
     * @param  $pageInfo
     * @return array
     */
    public function listChannelVideos($channelId, $pageToken = '', $maxResults = 10, $order = null, $part = ['id', 'snippet'], $pageInfo = true)
    {
        $params = [
            'type' => 'video',
            'channelId' => $channelId,
            'part' => implode(',', $part),
            'maxResults' => $maxResults,
            'pageToken'=> $pageToken
        ];
        if (!empty($order)) {
            $params['order'] = $order;
        }

        return $this->searchAdvanced($params, $pageInfo, $pageToken);
    }

    /**
     * Generic Search interface, use any parameters specified in
     * the API reference
     *
     * @param $params
     * @param $pageInfo
     * @return array
     * @throws \Exception
     */
    public function searchAdvanced($params, $pageInfo = true, $pageToken = '')
    {
        $API_URL = $this->getApi('search.list');

        if (empty($params) || (!isset($params['q']) && !isset($params['channelId']) && !isset($params['videoCategoryId']))) {
            throw new \InvalidArgumentException('at least the Search query or Channel ID or videoCategoryId must be supplied');
        }
        // Pass page token if it is given, an empty string won't change the api response
        $params['pageToken'] = $pageToken;
        $apiData = $this->api_get($API_URL, $params);
	
	
        if ($pageInfo) {
            return [
                'results' => $this->decodeList($apiData),
                'info' => $this->page_info,
            ];
        } else {

            return $this->decodeList($apiData);
        }
    }

    /**
     * Generic Search Paginator, use any parameters specified in
     * the API reference and pass through nextPageToken as $token if set.
     *
     * @param $params
     * @param $token
     * @return array
     */
    public function paginateResults($params, $token = null)
    {
        if (!is_null($token)) {
            $params['pageToken'] = $token;
        }

        if (!empty($params)) {
            return $this->searchAdvanced($params, true);
        }
    }

    /**
     * @param $username
     * @param array $optionalParams
     * @param array $part
     * @return \StdClass
     * @throws \Exception
     */
    public function getChannelByName($username, $optionalParams = [], $part = ['id', 'snippet', 'contentDetails', 'statistics'])
    {
        $API_URL = $this->getApi('channels.list');
        $params = [
            'forUsername' => $username,
            'part' => implode(',', $part),
        ];

        $params = array_merge($params, $optionalParams);

        $apiData = $this->api_get($API_URL, $params);

        return $this->decodeSingle($apiData);
    }

	/**
	 * @param $username
	 * @param $maxResults
	 * @param $part
	 * @return false|\StdClass
	 * @throws \Exception
	 */
	public function searchChannelByName($username, $maxResults = 1, $part = ['id', 'snippet'])
	{
		$params = [
			'q' => $username,
			'part' => implode(',', $part),
			'type' => 'channel',
			'maxResults' => $maxResults,
		];

		$search = $this->searchAdvanced($params);

		if (!empty($search[0]->snippet->channelId)) {
			$channelId = $search[0]->snippet->channelId;
			return $this->getChannelById($channelId);
		}
	}

    /**
     * @param $id
     * @param array $optionalParams
     * @param array $part
     * @return \StdClass
     * @throws \Exception
     */
    public function getChannelById($id, $optionalParams = [], $part = ['id', 'snippet', 'contentDetails', 'statistics'])
    {
        $API_URL = $this->getApi('channels.list');
        $params = [
            'id' => is_array($id) ? implode(',', $id) : $id,
            'part' => implode(',', $part),
        ];

        $params = array_merge($params, $optionalParams);

        $apiData = $this->api_get($API_URL, $params);

        if (is_array($id)) {
            return $this->decodeMultiple($apiData);
        }

        return $this->decodeSingle($apiData);
    }

    /**
     * @param string $channelId
     * @param array $optionalParams
     * @param array $part
     * @return array
     * @throws \Exception
     */
    public function getPlaylistsByChannelId($channelId, $optionalParams = [], $part = ['id', 'snippet', 'status'])
    {
        $API_URL = $this->getApi('playlists.list');
        $params = [
            'channelId' => $channelId,
            'part' => implode(',', $part)
        ];

        $params = array_merge($params, $optionalParams);

        $apiData = $this->api_get($API_URL, $params);

        $result = ['results' => $this->decodeList($apiData)];
        $result['info']['totalResults'] =  (isset($this->page_info['totalResults']) ? $this->page_info['totalResults'] : 0);
        $result['info']['nextPageToken'] = (isset($this->page_info['nextPageToken']) ? $this->page_info['nextPageToken'] : false);
        $result['info']['prevPageToken'] = (isset($this->page_info['prevPageToken']) ? $this->page_info['prevPageToken'] : false);

        return $result;
    }

    /**
     * @param $id
     * @param $part
     * @return \StdClass
     * @throws \Exception
     */
    public function getPlaylistById($id, $part = ['id', 'snippet', 'status'])
    {
		/* Local cache ID */
		$cacheplid = 'yt_pl_'.$id.'_'.implode('_', $part);
		/* Check for local copy of Youtube playlist's data data to save quota */
		if(jc_exists($cacheplid)) {
		$data = jc_get($cacheplid);		
		} else { 
        $API_URL = $this->getApi('playlists.list');
        $params = [
            'id' => is_array($id)? implode(',', $id) : $id,
            'part' => implode(',', $part)
        ];
        $apiData = $this->api_get($API_URL, $params);

        if (is_array($id)) {
            return $this->decodeMultiple($apiData);
        }
		$data =  $this->decodeSingle($apiData);
			if($data && !empty($data)) {
				jc_put($cacheplid, $data);
			}
		}

        return $data;
    }

    /**
     * @param string $playlistId
     * @param string $pageToken
     * @param integer $maxResults
     * @param array $part
     * @return array
     * @throws \Exception
     */
    public function getPlaylistItemsByPlaylistId($playlistId, $pageToken = '', $maxResults = 50, $part = ['id', 'snippet', 'contentDetails', 'status'])
    {
        $API_URL = $this->getApi('playlistItems.list');
        $params = [
            'playlistId' => $playlistId,
            'part' => implode(',', $part),
            'maxResults' => $maxResults,
        ];

        // Pass page token if it is given, an empty string won't change the api response
        $params['pageToken'] = $pageToken;

        $apiData = $this->api_get($API_URL, $params);
        $result = ['results' => $this->decodeList($apiData)];
        $result['info']['totalResults'] =  (isset($this->page_info['totalResults']) ? $this->page_info['totalResults'] : 0);
        $result['info']['nextPageToken'] = (isset($this->page_info['nextPageToken']) ? $this->page_info['nextPageToken'] : false);
        $result['info']['prevPageToken'] = (isset($this->page_info['prevPageToken']) ? $this->page_info['prevPageToken'] : false);

        return $result;
    }
	/**
     * @param string $playlistId
     * @param string $pageToken
     * @param integer $maxResults
     * @param array $part
     * @return array
     * @throws \Exception
     */
    public function getPlaylistItemIdsByPlaylistId($playlistId, $pageToken = '', $maxResults = 50, $part = ['id', 'contentDetails'])
    {
        $API_URL = $this->getApi('playlistItems.list');
        $params = [
            'playlistId' => $playlistId,
            'part' => implode(',', $part),
            'maxResults' => $maxResults,
        ];

        // Pass page token if it is given, an empty string won't change the api response
        $params['pageToken'] = $pageToken;

        $apiData = $this->api_get($API_URL, $params);
        $result = ['results' => $this->decodeList($apiData)];
        $result['info']['totalResults'] =  (isset($this->page_info['totalResults']) ? $this->page_info['totalResults'] : 0);
        $result['info']['nextPageToken'] = (isset($this->page_info['nextPageToken']) ? $this->page_info['nextPageToken'] : false);
        $result['info']['prevPageToken'] = (isset($this->page_info['prevPageToken']) ? $this->page_info['prevPageToken'] : false);

        return $result;
    }
	
	/**
     * @param string $playlistId
     * @param string $pageToken
     * @param integer $maxResults
	 * @param array $part
     * @return array
     * @throws \Exception
     */
    public function ListPlaylistItems($playlistId, $pageToken = '', $maxResults = 50,  $part = ['id', 'snippet', 'contentDetails', 'status'])
    {
       $playlistVideos =  $this->getPlaylistItemIdsByPlaylistId($playlistId, $pageToken , $maxResults, $part);
	   
	   $videosdone = array();
		   if (isset( $playlistVideos['results'])){			   
				 
				 foreach ($playlistVideos['results'] as $video) {
					$videoItem = $this->getVideo($video->contentDetails->videoId); 
					if($videoItem) {
					$videosdone[$video->contentDetails->videoId] = $videoItem;					
					if(!ytExists($video->contentDetails->videoId )) {
					$videosdone[$video->contentDetails->videoId]['saved'] = 1;	
					} else {
					$videosdone[$video->contentDetails->videoId]['saved'] = 0;		
					}
					}
					 
					 
				 }

	   }
	   
	   return $videosdone;
    }
	/**
     * @param string $playlistId
     * @param string $pageToken
     * @param integer $maxResults
	 * @param array $part
     * @return array
     * @throws \Exception
     */
    public function savePlaylistItems($playlistId, $pageToken = '', $maxResults = 50, $part = ['id', 'snippet', 'contentDetails', 'status'])
    {
        global $thedata, $db;
       $playlistVideos =  $this->getPlaylistItemIdsByPlaylistId($playlistId, $pageToken , $maxResults);
	   
	   $videosdone = array();
		   if (isset( $playlistVideos['results'])){


               $cvslines = "";
                 $sqlheader = "INSERT INTO ".DB_PREFIX."videos  (`token`,`featured`,`pub`,`source`, `user_id`, `date`, `thumb`, `title`, `duration`, `views` , `liked` , `category`,`nsfw`) VALUES ";

				 foreach ($playlistVideos['results'] as $video) {

                     $videoItem = $this->getVideo($video->contentDetails->videoId);
                         $videosdone[$video->contentDetails->videoId] = $videoItem;
                     if ($videoItem) {
                         if((isset($thedata['allowduplicates']) && ($thedata['allowduplicates'] > 0)) || !ytExists($video->contentDetails->videoId )) {

                         $singlevideo = $this->InsertPrepare($videoItem);


                         $cvslines .= "('" . $singlevideo["token"] . "','" . $singlevideo["featured"] . "','" . $singlevideo["state"] . "','" . $singlevideo["url"] . "', '" . $singlevideo["owner"] . "', now() , '" . $singlevideo["thumb"] . "', '" . toDb($singlevideo["title"]) . "', '" . intval($singlevideo["duration"]) . "', '0', '0','" . toDb($singlevideo["category"]) . "','0'),";




                      $videosdone[$video->contentDetails->videoId]['saved'] = 1;

                 } else {
                         $videosdone[$video->contentDetails->videoId]['saved'] = 0;
                     }
                     }
                     }
            if(not_empty($cvslines)){
            $Query = $sqlheader;
            $Query .=rtrim($cvslines, ',');
            $Query .= ';';
               $db->query($Query);
                       }

            }
	   return $videosdone;
    }

    /**
     * @param array $video
       * @return array
     */
    public function InsertPrepare($video) {
         if(!isset($video["title"])) {return false;}
        /* Handle locals : category & owner */
        $video["owner"] = $this->getCategory();
        $video["category"] = $this->getOwner();

        /* Published?  */
        if(!isset($video["state"])) {
            $video["state"] = intval(get_option('videos-initial', 1));
            /* Always approve moderators & admins */
            if(is_moderator()) {			$video["state"] = 1;			}
        }
        /* Tags */
        if(isset($video["tags"]) && is_array($video["tags"]) && not_empty($video["tags"])) {
            $video["tags"] = implode(',',$video["tags"]);
        } else {
            /* Auto-Generate */
            $tags = array_unique(explode('-',nice_tag(removeCommonWords($video["title"]))));
            $video["tags"] = ','.implode(',',$tags);
        }
        if(!isset($video["featured"])) {
            $video["featured"] = 0;
        }
        /* Unique Token */
        $video["token"] = md5($video["videoid"].time());

return $video;
    }


    /**
     * @param array $video

     * @return bool
     */
    public function SaveVideo($video) {
        global $db, $thedata;

        if((isset($thedata['allowduplicates']) && ($thedata['allowduplicates'] > 0)) || !ytExists($video['videoid'] )) {
            $video = $this->InsertPrepare($video) ;
            $db->query("INSERT INTO ".DB_PREFIX."videos 
		(`token`,`featured`,`pub`,`source`, `user_id`, `date`, `thumb`, `title`, `duration`, `views` , `liked` , `category`,`nsfw`) 
		VALUES 
		('".$video["token"]."','".$video["featured"]."','".$video["state"]."','".$video["url"]."', '".$video["owner"]."', now() , '".$video["thumb"]."', '".toDb($video["title"]) ."', '".intval($video["duration"])."', '0', '0','".toDb($video["category"])."','0')");
            //Recover new id
            $theid = getVideobyToken($video["token"]);
            if($theid) {
                //Add tags
                foreach (explode(',',$video["tags"]) as $tagul){
                    save_tag($tagul,$theid);
                }
                //Add description
                save_description($theid,$video["description"]);
            }

            return true;
        } else {
            return false;
        }
    }
    /**
     * @param array $video

     * @return bool
     */
    public function StoreVideo($video)
    {
        $PreparedVideo = $this->InsertPrepare($video);
        return $this->SaveVideo($PreparedVideo) ;
    }
    /**
     * @param $channelId
     * @param array $part
     * @param integer $maxResults
     * @param $pageInfo
     * @param $pageToken
     * @return array
     * @throws \Exception
     */
    public function getActivitiesByChannelId($channelId, $part = ['id', 'snippet', 'contentDetails'], $maxResults = 5, $pageInfo = false, $pageToken = '')
    {
        if (empty($channelId)) {
            throw new \InvalidArgumentException('ChannelId must be supplied');
        }
        $API_URL = $this->getApi('activities');
        $params = [
            'channelId' => $channelId,
            'part' => implode(',', $part),
            'maxResults' => $maxResults,
            'pageToken' => $pageToken,
        ];
        $apiData = $this->api_get($API_URL, $params);

        if ($pageInfo) {
            return [
                'results' => $this->decodeList($apiData),
                'info' => $this->page_info,
            ];
        } else {
            return $this->decodeList($apiData);
        }
    }

    /**
     * Parse a youtube URL to get the youtube Vid.
     * Support both full URL (www.youtube.com) and short URL (youtu.be)
     *
     * @param  string $youtube_url
     * @throws \Exception
     * @return string Video Id
     */
    public static function parseVidFromURL($youtube_url)
    {
        if (strpos($youtube_url, 'youtube.com')) {
            if (strpos($youtube_url, 'embed')) {
                $path = static::_parse_url_path($youtube_url);
                $vid = substr($path, 7);
                return $vid;
            } else {
                $params = static::_parse_url_query($youtube_url);
                return $params['v'];
            }
        } else if (strpos($youtube_url, 'youtu.be')) {
            $path = static::_parse_url_path($youtube_url);
            $vid = substr($path, 1);
            return $vid;
        } else {
            throw new \Exception('The supplied URL does not look like a Youtube URL');
        }
    }

    /**
     * Get the channel object by supplying the URL of the channel page
     *
     * @param  string $youtube_url
     * @throws \Exception
     * @return object Channel object
     */
    public function getChannelFromURL($youtube_url)
    {
        if (strpos($youtube_url, 'youtube.com') === false) {
            throw new \Exception('The supplied URL does not look like a Youtube URL');
        }

        $path = static::_parse_url_path($youtube_url);
        $segments = explode('/', $path);

        if (strpos($path, '/channel/') === 0) {
            $channelId = $segments[count($segments) - 1];
            $channel = $this->getChannelById($channelId);
        } else if (strpos($path, '/user/') === 0) {
            $username = $segments[count($segments) - 1];
            $channel = $this->getChannelByName($username);
        } else if (strpos($path, '/c/') === 0) {
            $username = $segments[count($segments) - 1];
            $channel = $this->searchChannelByName($username);
        } else if (strpos($path, '/@') === 0) {
            $username = str_replace('@', '', $segments[count($segments) - 1]);
            $channel = $this->searchChannelByName($username);
        } else {
            foreach ($this->youtube_reserved_urls as $r) {
                if (preg_match('/'.$r.'/', $path)) {
                    throw new \Exception('The supplied URL does not look like a Youtube Channel URL');
                }
            }

	        $username = $segments[1];
	        $channel = $this->searchChannelByName($username);
        }

        return $channel;
    }

    /*
     *  Internally used Methods, set visibility to public to enable more flexibility
     */

    /**
     * @param $name
     * @return mixed
     */
    public function getApi($name)
    {
        return $this->APIs[$name];
    }

    /**
     * Decode the response from youtube, extract the single resource object.
     * (Don't use this to decode the response containing list of objects)
     *
     * @param  string $apiData the api response from youtube
     * @throws \Exception
     * @return \StdClass  an Youtube resource object
     */
    public function decodeSingle(&$apiData)
    {
        $resObj = json_decode($apiData);
        if (isset($resObj->error)) {
            $msg = "Error " . $resObj->error->code . " " . $resObj->error->message;
            if (isset($resObj->error->errors[0])) {
                $msg .= " : " . $resObj->error->errors[0]->reason;
            }

            throw new \Exception($msg);
        } else {
            if(isset($resObj->items)){
                $itemsArray = $resObj->items;
                if (!is_array($itemsArray) || count($itemsArray) == 0) {
                    return false;
                } else {
                    return $itemsArray[0];
                }
            }
           return false;
        }
    }

    /**
     * Decode the response from youtube, extract the multiple resource object.
     *
     * @param  string $apiData the api response from youtube
     * @throws \Exception
     * @return \StdClass  an Youtube resource object
     */
    public function decodeMultiple(&$apiData)
    {
        $resObj = json_decode($apiData);
        if (isset($resObj->error)) {
            $msg = "Error " . $resObj->error->code . " " . $resObj->error->message;
            if (isset($resObj->error->errors[0])) {
                $msg .= " : " . $resObj->error->errors[0]->reason;
            }

            throw new \Exception($msg);
        } else {

            if(isset($resObj->items)) {
                $itemsArray = $resObj->items;
                if (!is_array($itemsArray) || count($itemsArray) == 0) {
                    return false;
                } else {
                    if (isset($resObj->pageInfo)) {
                        $this->page_info['resultsPerPage'] = $resObj->pageInfo->resultsPerPage;
                        $this->page_info['totalResults'] = $resObj->pageInfo->totalResults;
                    }

                    if (isset($resObj->prevPageToken)) {
                        $this->page_info['prevPageToken'] = $resObj->prevPageToken;
                    }

                    if (isset($resObj->nextPageToken)) {
                        $this->page_info['nextPageToken'] = $resObj->nextPageToken;

                    }
                    return $itemsArray;
                }
            }
            return false;
        }
    }
	/**
     * Return the next page token 
     * @return string
     */
	 public function NextToken() {
		 if(isset($this->page_info->nextPageToken)) {
			return $this->page_info->nextPageToken;
		 } 		 
		 return false;
	 }
	 /**
     * Return the previous page token 
     * @return string
     */
	 public function PrevToken() {
		 if(isset($this->page_info->prevPageToken)) {
			return $this->page_info->prevPageToken;
		 } 		 
		 return false;
	 }
    /**
     * Decode the response from youtube, extract the list of resource objects
     *
     * @param  string $apiData response string from youtube
     * @throws \Exception
     * @return array Array of StdClass objects
     */
    public function decodeList(&$apiData)
    {
        $resObj = json_decode($apiData);
        if (isset($resObj->error)) {
            $msg = "Error " . $resObj->error->code . " " . $resObj->error->message;
            if (isset($resObj->error->errors[0])) {
                $msg .= " : " . $resObj->error->errors[0]->reason;
            }

            throw new \Exception($msg);
        } else {
			
            $this->page_info = [
                'kind' => $resObj->kind,
                'etag' => $resObj->etag,
                'prevPageToken' => null,
                'nextPageToken' => null,
            ];
  
            if (isset($resObj->pageInfo)) {
                $this->page_info['resultsPerPage'] = $resObj->pageInfo->resultsPerPage;
                $this->page_info['totalResults'] = $resObj->pageInfo->totalResults;
            }

            if (isset($resObj->prevPageToken)) {
                $this->page_info['prevPageToken'] = $resObj->prevPageToken;
            }

            if (isset($resObj->nextPageToken)) {
                $this->page_info['nextPageToken'] = $resObj->nextPageToken;
				
            }

            if(isset($resObj->items)) {
                $itemsArray = $resObj->items;
                if (!is_array($itemsArray) || count($itemsArray) == 0) {
                    return false;
                } else {
                    return $itemsArray;
                }
            }
            return false;
        }
    }

    /**
     * Using CURL to issue a GET request
     *
     * @param $url
     * @param $params
     * @return mixed
     * @throws \Exception
     */
    public function api_get($url, $params)
    {
        //set the youtube key
        $params['key'] = $this->youtube_key;

        //boilerplates for CURL
        $tuCurl = curl_init();

        if (isset($_SERVER['HTTP_HOST']) && $this->config['use-http-host']) {
            curl_setopt($tuCurl, CURLOPT_HEADER, array('Referer' => $_SERVER['HTTP_HOST']));
        }

        curl_setopt($tuCurl, CURLOPT_URL, $url . (strpos($url, '?') === false ? '?' : '') . http_build_query($params));
        if (strpos($url, 'https') === false) {
            curl_setopt($tuCurl, CURLOPT_PORT, 80);
        } else {
            curl_setopt($tuCurl, CURLOPT_PORT, 443);
        }

        curl_setopt($tuCurl, CURLOPT_RETURNTRANSFER, 1);
        $tuData = curl_exec($tuCurl);
        if (curl_errno($tuCurl)) {
            throw new \Exception('Curl Error : ' . curl_error($tuCurl));
        }

        return $tuData;
    }

    /**
     * Parse the input url string and return just the path part
     *
     * @param  string $url the URL
     * @return string      the path string
     */
    public static function _parse_url_path($url)
    {
        $array = parse_url($url);

        return $array['path'];
    }

    /**
     * Parse the input url string and return an array of query params
     *
     * @param  string $url the URL
     * @return array      array of query params
     */
    public static function _parse_url_query($url)
    {
        $array = parse_url($url);
        $query = $array['query'];

        $queryParts = explode('&', $query);

        $params = [];
        foreach ($queryParts as $param) {
            $item = explode('=', $param);
            $params[$item[0]] = empty($item[1]) ? '' : $item[1];
        }

        return $params;
    }
	/**
	 * Decodes PT*M*S to seconds
	 * @param $duration
     * @return \String
	**/  
    public static function getDurationSeconds($duration){
    preg_match_all('/[0-9]+[HMS]/',$duration,$matches);
    $duration=0;
    foreach($matches as $match){    
        foreach($match as $portion){        
            $unite=substr($portion,strlen($portion)-1);
            switch($unite){
                case 'H':{  
                    $duration +=    substr($portion,0,strlen($portion)-1)*60*60;            
                }break;             
                case 'M':{                  
                    $duration +=substr($portion,0,strlen($portion)-1)*60;           
                }break;             
                case 'S':{                  
                    $duration +=    substr($portion,0,strlen($portion)-1);          
                }break;
            }
        }
    }
     return $duration -1;
    /* seems to add +1 to actual duration */
    }
	/**
     * Parse the video object and return selected video data as array
     * @param  object $video the video data returned by Youtube
     * @return array      array of video sections
     */
	
	public static function MakeVideoPretty($video) {		
		$v = array();
		if(isset($video->id) && isset($video->snippet->title)) {
        $v['videoid'] = $v['id'] = 	$video->id;
        $v['url'] = 'https://www.youtube.com/watch?v='.$video->id;
        $v['thumb'] = $v['thumbnail'] = $video->snippet->thumbnails->medium->url;
        $v['title'] = htmlentities($video->snippet->title, ENT_QUOTES, "UTF-8");
        $v['description'] = htmlentities($video->snippet->description, ENT_QUOTES, "UTF-8");
        $v['duration'] = Youtube::getDurationSeconds($video->contentDetails->duration);
        $v['ptime'] = $video->contentDetails->duration;  
		$v['privacy'] = $video->status->privacyStatus;
		$v['embeddable'] = (bool)$video->status->embeddable;
        $v['ytChannelID'] = $video->snippet->channelId;
		$v['author'] = $v['ytChannelTitle'] = $video->snippet->channelTitle;
		$v['ytPublished'] = isset($video->snippet->publishedAt)? $video->snippet->publishedAt: '';
		}
			
	  return $v;
	}
}

/** End class */
