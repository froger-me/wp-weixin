<?php

/**
 * WeChat PHP SDK
 *
 * Helper class to handle WeChat authentication, official account manipulation and ecommerce
 * Requires Curl
 *
 * Inspired from the work of 小陈叔叔 <cjango@163.com> - https://coding.net/u/cjango/p/wechat_sdk/git
 *
 * @category   SDK
 * @package    WeChat
 * @author     Alexandre Froger
 * @copyright  2017 froger.me
 * @license    MIT License
 * @version    2.0
 * @see        http://froger.me
 */

class Wechat_SDK {
	/* Get access_token URL */
	const AUTH_URL = 'https://api.weixin.qq.com/cgi-bin/token';
	/* Menu URLs */
	const MENU_CREATE_URL             = 'https://api.weixin.qq.com/cgi-bin/menu/create';
	const MENU_GET_URL                = 'https://api.weixin.qq.com/cgi-bin/menu/get';
	const MENU_DELETE_URL             = 'https://api.weixin.qq.com/cgi-bin/menu/delete';
	const MENU_CREATE_CONDITIONAL_URL = 'https://api.weixin.qq.com/cgi-bin/menu/addconditional';
	const MENU_DELETE_CONDITIONAL_URL = 'https://api.weixin.qq.com/cgi-bin/menu/delconditional';
	/* User and user group URLs */
	const USER_GET_URL            = 'https://api.weixin.qq.com/cgi-bin/user/get';
	const USER_INFO_URL           = 'https://api.weixin.qq.com/cgi-bin/user/info';
	const USER_IN_GROUP_URL       = 'https://api.weixin.qq.com/cgi-bin/groups/getid';
	const GROUP_GET_URL           = 'https://api.weixin.qq.com/cgi-bin/groups/get';
	const GROUP_CREATE_URL        = 'https://api.weixin.qq.com/cgi-bin/groups/create';
	const GROUP_UPDATE_URL        = 'https://api.weixin.qq.com/cgi-bin/groups/update';
	const GROUP_MEMBER_UPDATE_URL = 'https://api.weixin.qq.com/cgi-bin/groups/members/update';
	/* Send customer service message URL */
	const CUSTOM_SEND_URL = 'https://api.weixin.qq.com/cgi-bin/message/custom/send';
	/* Parametric QR code URLs */
	const QRCODE_URL      = 'https://api.weixin.qq.com/cgi-bin/qrcode/create';
	const QRCODE_SHOW_URL = 'https://mp.weixin.qq.com/cgi-bin/showqrcode';
	/* Web browser authentication QR code URL */
	const QR_AUTHORIZATION_URL = 'https://open.weixin.qq.com/connect/qrconnect';
	/* OAuth2.0 URLs */
	const OAUTH_AUTHORIZE_URL  = 'https://open.weixin.qq.com/connect/oauth2/authorize';
	const OAUTH_USER_TOKEN_URL = 'https://api.weixin.qq.com/sns/oauth2/access_token';
	const OAUTH_REFRESH_URL    = 'https://api.weixin.qq.com/sns/oauth2/refresh_token';
	/* Get user info URL */
	const GET_USER_INFO_URL = 'https://api.weixin.qq.com/sns/userinfo';
	/* Message template URL */
	const TEMPLATE_SEND_URL = 'https://api.weixin.qq.com/cgi-bin/message/template/send';
	/* JS-SDK jsapi_ticket URL */
	const JSAPI_TICKET_URL = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket';
	/* Unified order */
	const UNIFIED_ORDER_URL               = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
	const UNIFIED_ORDER_INTERFACE_VERSION = '1.0';
	/* Order status inquiry URL */
	const ORDER_QUERY_URL = 'https://api.mch.weixin.qq.com/pay/orderquery';
	/* Close order URL */
	const CLOSE_ORDER_URL = 'https://api.mch.weixin.qq.com/pay/closeorder';
	/* Cancel payment URL */
	const REVERSE_TRANSACTION_URL = 'https://api.mch.weixin.qq.com/secapi/pay/reverse';
	/* Refund URL */
	const PAY_REFUND_ORDER_URL = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
	/* Refund inquiry URL */
	const REFUND_QUERY_URL = 'https://api.mch.weixin.qq.com/pay/refundquery';
	/* Download bill URL */
	const DOWNLOAD_BILL_URL = 'https://api.mch.weixin.qq.com/pay/downloadbill';
	/* URL shortener tool URL */
	const GET_SHORT_URL = 'https://api.mch.weixin.qq.com/tools/shorturl';
	/* Send red envelope URL */
	const SEND_RED_PACK_URL = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';
	/* Send shared red envelope URL */
	const SEND_GROUP_RED_PACK_URL = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendgroupredpack';
	/* Red envelope inquiry URL */
	const GET_RED_PACK_INFO_URL = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/gethbinfo';
	/* Assets management URLs */
	const MEDIA_UPLOAD_URL    = 'https://api.weixin.qq.com/cgi-bin/media/upload';               // add temporary Asset
	const MEDIA_GET_URL       = 'https://api.weixin.qq.com/cgi-bin/media/get';                  // get temporary Asset
	const MATERIAL_NEWS_URL   = 'https://api.weixin.qq.com/cgi-bin/material/add_news';          // add permanent Rich Media Message Asset
	const MATERIAL_FILE_URL   = 'https://api.weixin.qq.com/cgi-bin/material/add_material';      // add permanent Asset
	const MATERIAL_GET_URL    = 'https://api.weixin.qq.com/cgi-bin/material/get_material';      // get permanent Asset
	const MATERIAL_DEL_URL    = 'https://api.weixin.qq.com/cgi-bin/material/del_material';      // remove permanent Asset
	const MATERIAL_UPDATE_URL = 'https://api.weixin.qq.com/cgi-bin/material/update_news';       // update permanent Rich Media Message Asset
	const MATERIAL_COUNT_URL  = 'https://api.weixin.qq.com/cgi-bin/material/get_materialcount'; // Get permanent Assets Count
	const MATERIAL_LIST_URL   = 'https://api.weixin.qq.com/cgi-bin/material/batchget_material'; // Get permanent Assets List

	private $token;
	private $appid;
	private $secret;
	private $access_token;
	private $access_token_expire;
	private $user_token;
	private $debug = false;
	private $data  = array();
	private $send  = array();
	private $error;
	private $errorCode;
	private $ticket;
	private $result;
	private $encode;
	private $AESKey;
	private $mch_appid;
	private $mch_id;
	private $payKey;
	private $pemCert;
	private $pemKey;
	private $pemPath;
	private $proxy;
	private $proxyPort;
	private $proxyHost;

	public function __construct($options = array()) {
		$this->token               = isset($options['token']) ? $options['token'] : '';
		$this->appid               = isset($options['appid']) ? $options['appid'] : '';
		$this->secret              = isset($options['secret']) ? $options['secret'] : '';
		$this->access_token        = isset($options['access_token']) ? $options['access_token'] : '';
		$this->access_token_expire = isset($options['access_token_expire']) ? $options['access_token_expire'] : '';
		$this->debug               = isset($options['debug']) ? $options['debug'] : false;
		$this->encode              = isset($options['encode']) && !empty($options['encode']) ? true : false;
		$this->AESKey              = isset($options['aeskey']) ? $options['aeskey'] : '';
		$this->mch_appid           = isset($options['mch_appid']) && !empty($options['mch_appid']) ? $options['mch_appid'] : $this->appid;
		$this->mch_id              = isset($options['mch_id']) ? $options['mch_id'] : '';
		$this->payKey              = isset($options['payKey']) ? $options['payKey'] : '';
		$this->pem                 = isset($options['pem']) ? $options['pem'] : '';
		$this->pemPath             = isset($options['pemPath']) ? $options['pemPath'] : '';
		$this->proxy               = isset($options['proxy']) ? $options['proxy'] : false;
		$this->proxyHost           = isset($options['proxyHost']) ? $options['proxyHost'] : '';
		$this->proxyPort           = isset($options['proxyPort']) ? $options['proxyPort'] : '';

		if ($this->encode && strlen($this->AESKey) != 43) {
			$this->setError('AESKey Length Error');

			return false;
		}
	}

	public function __get($key) {
		return $this->$key;
	}

	public function __set($key, $value) {
		$this->$key = $value;
	}

	/**
	 * Check if accessing the app using the WeChat browser
	 * @param 	string $version Minimum required version - format: 3 numbers separated by "." - default empty string 
	 * @return 	bool
	 */
	public static function isMobileBrowser($version = '') {
		$is_wechat_mobile = (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false);

		if ($is_wechat_mobile && !empty($version)) {

			$version_parts = explode('.', $version_browser);

			if (count($version_parts) !== 3) {
				$this->setError('Invalid WeChat version format');

				$is_wechat_mobile = false;
			} else {

				foreach (explode(' ', $_SERVER['HTTP_USER_AGENT']) as $key => $value) {

					if (strpos($value, 'MicroMessenger') !== false) {

						$version_browser       = end(explode('/', $value));
						$version_browser_parts = explode('.', $version_browser);

						$condition = (((int) $version_browser_parts[0]) >= ((int) $version_parts[0]));

						if ($condition) {
							$condition = ((int) $version_browser_parts[1]) >= ((int) $version_parts[1]);
						}

						if ($condition) {
							$condition = ((int) $version_browser_parts[2]) >= ((int) $version_parts[2]);
						}

						if (!$condition) {
							$this->setError('Current WeChat version ('. $version_browser .') < required version (' . $version . ')');

							$is_wechat_mobile = false;
						} else {
							$is_wechat_mobile = true;
						}
					}
				}
			}
		}

		return $is_wechat_mobile;
	}

	/**
	 * Check if the website is bound to the WeChat official account
	 * @author, chen shushu <cjango@163.com>
	 */
	public function checkBind() {
		$echoStr = filter_input(INPUT_GET, 'echostr', FILTER_SANITIZE_STRING);

		if ($echoStr) {

			if ($this->checkSignature()) {

				exit($echoStr);
			} else {

				exit('Access Denied!');
			}	
		}

		return true;
	}

	/**
	 * Check official account's signature
	 * @author, chen shushu <cjango@163.com>
	 */
	public function checkSignature() {

		if ($this->debug) {

			return true;
		}

		$signature = filter_input(INPUT_GET, 'signature', FILTER_SANITIZE_STRING);
		$timestamp = filter_input(INPUT_GET, 'timestamp', FILTER_SANITIZE_STRING);
		$nonce     = filter_input(INPUT_GET, 'nonce', FILTER_SANITIZE_STRING);

		if (empty($signature) || empty($timestamp) || empty($nonce)) {

			return false;
		}

		$token = $this->token;

		if (!$token) {
			return false;
		}

		$tmpArr = array($token, $timestamp, $nonce);

		sort($tmpArr, SORT_STRING);

		$tmpStr = implode($tmpArr);

		return (sha1($tmpStr) === $signature);
	}

	/**
	 * Get official account's access_token
	 * @param boolean $force Force retrieving the access token from the API if true, get the property otherwise.
	 * @return string|boolean
	 * @author, chen shushu <cjango@163.com>
	 */
	public function getAccessToken($force = false) {
		$access_token = $this->access_token;

		if (!empty($access_token) && !$force) {

			return $this->access_token;
		} else {

			if ($this->requestAccessToken()) {

				return $this->access_token;
			} else {

				return false;
			}
		}
	}

	/**
	 * Set official account's access_token
	 * @param string $access_token A valid official account's access_token
	 * @author, chen shushu <cjango@163.com>
	 */
	public function setAccessToken($access_token) {
		$this->access_token = $access_token;
	}

	/**
	 * Get official account's access_token expiry time (timestamp)
	 * @return integer|boolean
	 * @author, chen shushu <cjango@163.com>
	 */
	public function getAccessTokenExpiry() {

		return ($this->access_token_expire) ? $this->access_token_expire : false;
	}

	/**
	 * Set official account's access_token expiry time
	 * @param integer The official account's access_token expiry time (timestamp)
	 * @author, chen shushu <cjango@163.com>
	 */
	public function setAccessTokenExpiry($access_token_expire) {
		$this->access_token_expire = $access_token_expire;
	}

	/**
	 * Retrieve the official account's access_token from the WeChat remote interface
	 * @author, chen shushu <cjango@163.com>
	 */
	private function requestAccessToken() {
		$params = array(
			'grant_type' => 'client_credential',
			'appid'      => $this->appid,
			'secret'     => $this->secret,
		);
		$jsonStr = $this->http(self::AUTH_URL, $params);

		if ($jsonStr) {
			$jsonArr = $this->parseJson($jsonStr);

			if ($jsonArr) {
				$this->access_token        = $jsonArr['access_token'];
				$this->access_token_expire = time() + $jsonArr['expires_in'];

				return $this->access_token;
			} else {

				return false;
			}
		} else {

			return false;
		}
	}

	/**
	 * Get the official account's custom menu
	 * @return array | boolean
	 * @author, chen shushu <cjango@163.com>
	 */
	public function menus() {
		$params  = array(
			'access_token' => $this->getAccessToken(),
		);
		$jsonStr = $this->http(self::MENU_GET_URL, $params);
		$jsonArr = $this->parseJson($jsonStr);

		return ($jsonArr) ? $jsonArr : false;
	}

	/**
	 * Create the official account's custom menu
	 * @param array $menus An array representing the custom menu
	 * @param bool 	$conditional Whether the menu structure is conditional or general
	 * @see http://open.wechat.com/cgi-bin/newreadtemplate?t=overseas_open/docs/oa/custom-menus/create	
	 * @see http://open.wechat.com/cgi-bin/newreadtemplate?t=overseas_open/docs/oa/custom-menus/personalized#custom-menus_personalized
	 * @return boolean
	 * @author, chen shushu <cjango@163.com>
	 */
	public function menu_create($menus = array(), $conditional = false) {

		if (empty($menus)) {
			$this->setError('Menu array representation required');

			return false;
		}

		$params = $this->json_encode($menus);

		if ($conditional) {
			$url = self::MENU_CREATE_CONDITIONAL_URL . '?access_token=' . $this->getAccessToken();
		} else {
			$url = self::MENU_CREATE_URL . '?access_token=' . $this->getAccessToken();
		}

		$jsonStr = $this->http($url, $params, 'POST');
		$jsonArr = $this->parseJson($jsonStr);

		if ($jsonArr) {

			return true;
		} else {

			return false;
		}
	}

	/**
	 * Delete the official account's custom menus
	 * @param int 	$menu_id the id of the menu to delete - delete all menus by default ; default null
	 * @see http://open.wechat.com/cgi-bin/newreadtemplate?t=overseas_open/docs/oa/custom-menus/delete
	 * @see http://open.wechat.com/cgi-bin/newreadtemplate?t=overseas_open/docs/oa/custom-menus/personalized#custom-menus_personalized
	 * @return boolean
	 * @author, chen shushu <cjango@163.com>
	 */
	public function menu_delete($menu_id = NULL) {
		$params = array();

		if ($menu_id !== NULL) {
			$params['menuid'] = ((string)$menu_id);
			$url              = self::MENU_DELETE_CONDITIONAL_URL . '?access_token=' . $this->getAccessToken();
		} else {
			$url = self::MENU_DELETE_URL . '?access_token=' . $this->getAccessToken();
		}

		$params  = json_encode($params);
		$jsonStr = $this->http($url, $params, 'POST');
		$jsonArr = $this->parseJson($jsonStr);

		if ($jsonArr) {

			return true;
		} else {

			return false;
		}
	}

	/**
	 * Get official account's followers groups
	 * @return array|boolean
	 */
	public function groups() {
		$url     = self::GROUP_GET_URL . '?access_token='.$this->getAccessToken();
		$jsonStr = $this->http($url);
		$jsonArr = $this->parseJson($jsonStr);

		if ($jsonArr) {

			return $jsonArr['groups'];
		} else {

			return false;
		}
	}
	
	/**
	 * Add a followers group to the official account
	 * @param string $name Followers' group name
	 * @return boolean
	 */
	public function group_add($name = '') {

		if (empty($name)) {
			$this->setError('Followers group name required');

			return false;
		}

		$params = array(
			'group' => array(
				'name' => $name,
			)
		);
		$params  = $this->json_encode($params);
		$url     = self::GROUP_CREATE_URL . '?access_token=' . $this->getAccessToken();
		$jsonStr = $this->http($url, $params, 'POST');
		$jsonArr = $this->parseJson($jsonStr);

		if ($jsonArr) {

			return $jsonArr['group'];
		} else {

			return false;
		}
	}
	
	/**
	 * Edit an official account's followers group
	 * @param integer $gid Followers group ID
	 * @param string $name New followers group name
	 * @return boolean
	 */
	public function group_edit($gid = '', $name = '') {

		if (empty($name) || empty($gid)) {
			$this->setError('Followers group ID and new Followers group name required');

			return false;
		}

		$params  = array(
			'group' => array(
				'id'   => $gid,
				'name' => $name,
			)
		);
		$params  = $this->json_encode($params);
		$url     = self::GROUP_UPDATE_URL . '?access_token=' . $this->getAccessToken();
		$jsonStr = $this->http($url, $params, 'POST');
		$jsonArr = $this->parseJson($jsonStr);
		if ($jsonArr) {

			return true;
		} else {

			return false;
		}
	}

	/**
	 * Get a list of openIDs of the official account's followers
	 * Max 10,000 openIDs can be loaded ; use the index 'next_openid' and call this method again to get more users
	 * If success, the returned array has the following indexes:
	 * - 'data' contains the users
	 * - 'total' is the number of total usrs in the account
	 * - 'count' is the number of users loaded
	 * - 'next_openid' the openID from which to load the batch of users
	 * @param  string $next_openid The openID from which to load the batch of users - default empty string
	 * @return array|boolean
	 */
	public function users($next_openid = '') {
		$params = array();

		if (!empty($next_openid)) {
			$params['next_openid'] = $next_openid;
		}

		$params['access_token'] = $this->getAccessToken();

		$jsonStr = $this->http(self::USER_GET_URL, $params);
		$jsonArr = $this->parseJson($jsonStr);

		if ($jsonArr) {
			$data = $jsonArr['data']['openid'];
			unset($jsonArr['data']);

			$jsonArr['data'] = $openId;

			return $jsonArr;
		} else {

			return false;
		}
	}

	/**
	 * Get the information of a follower of the official account
	 * @param  string $openid the follower's openID
	 * @return array|boolean
	 */
	public function follower($openid = '') {

		if (empty($openid)) {
			$this->setError('Follower openID required');

			return false;
		}

		$params = array(
			'access_token' => $this->getAccessToken(),
			'lang'         => 'zh_CN',
			'openid'       => $openid,
		);
		$jsonStr = $this->http(self::USER_INFO_URL, $params);
		$jsonArr = $this->parseJson($jsonStr);

		if ($jsonArr['subscribe'] == 1) {

			unset($jsonArr['subscribe']);

			return $jsonArr;
		} else {

			if (empty($this->errorCode)) {
				$this->setError('No Follower found');
			}

			return false;
		}
	}

	/**
	 * Get the information of a WeChat user
	 * @param  string $openid the WeChat user openID
	 * @return array|boolean
	 */
	public function user($openid = '') {

		if (empty($openid)) {
			$this->setError('User openId required');

			return false;
		}

		$params = array(
			'access_token' => $this->getAccessToken(),
			'lang'         => 'zh_CN',
			'openid'       => $openid,
		);
		$jsonStr = $this->http(self::USER_INFO_URL, $params);
		$jsonArr = $this->parseJson($jsonStr);

		return ($jsonArr);
	}

	/**
	 * Check if a follower belongs to a follower's group
	 * If yes, get the group ID
	 * @param  string $openid  Follower's openID
	 * @return integer|boolean
	 */
	public function user_in_group($openid = '') {

		if (empty($openid)) {
			$this->setError('Follower openID required');

			return false;
		}

		$params  = array(
			'openid' => $openid,
		);
		$params  = $this->json_encode($params);
		$url     = self::USER_IN_GROUP_URL . '?access_token=' . $this->getAccessToken();
		$jsonStr = $this->http($url, $params, 'POST');
		$jsonArr = $this->parseJson($jsonStr);

		if ($jsonArr) {

			return $jsonArr['groupid'];
		} else {

			return false;
		}
	}

	/**
	 * Assign a follower to a follower's group
	 * @param string  $openid Follower's openID
	 * @param integer $gid Follower's group ID
	 * @return boolean
	 */
	public function user_to_group($openid = '', $gid = '') {

		if (empty($openid) || !is_numeric($gid)) {
			$this->setError('Follower openID and numeric group ID required');

			return false;
		}

		$params  = array(
			'openid' => $openid,
			'to_groupid' => $gid,
		);
		$params  = $this->json_encode($params);
		$url     = self::GROUP_MEMBER_UPDATE_URL . '?access_token=' . $this->getAccessToken();
		$jsonStr = $this->http($url, $params, 'POST');
		$jsonArr = $this->parseJson($jsonStr);

		if ($jsonArr) {

			return true;
		} else {

			return false;
		}
	}

	/**
	 * Get data pushed by WeChat to the server
	 * @return array An array of data with keys all converted to lowercase
	 */
	public function request() {
		$postStr = file_get_contents("php://input");

		if (!empty($postStr)) {
			$data = $this->_extractXml($postStr);

			if ($this->encode && isset($data['encrypt'])) {
				$data = $this->AESdecode($data['encrypt']);
			}

			return $this->data = $data;
		} else {

			return false;
		}
	}

	/**
	 * Parse an XML string and converts it to an array with keys in lowercase
	 * @param  string $xml
	 * @return array
	 */
	private function _extractXml($xml) {
		$data = (array)simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

		return array_change_key_case($data, CASE_LOWER);
	}

	/**
	 * Reply to a WeChat message (auto-reply)
	 * @param  string $to      Receiver's OpenID
	 * @param  string $from    Developer's ID
	 * @param  string $type    Message type - "text", "music", "news", "event" - default "text"
	 * @param  array  $content Response information - all values in the array must be of type string
	 * @return string|bool
	 */
	public function response($type = 'text', $content = '') {
		$this->data = array(
			'ToUserName'   => $this->data['fromusername'],
			'FromUserName' => $this->data['tousername'],
			'CreateTime'   => time(),
			'MsgType'      => $type,
		);

		if (!method_exists($this, $type)) {
			$this->setError('Invalid WeChat response message type "' . $type . '"');

			return false;
		}

		$this->$type($content);
		// Deprecated - set to 1 to flag the message with a star in the official account's backend
		$this->data['FuncFlag'] = 0;

		$response = $this->_array2Xml($this->data);

		if ($this->encode) {
			$nonce                  = filter_input(INPUT_GET, 'nonce', FILTER_SANITIZE_STRING);
			$xmlStr['Encrypt']      = $this->AESencode($response);
			$xmlStr['MsgSignature'] = self::getSHA1($xmlStr['Encrypt'], $nonce);
			$xmlStr['TimeStamp']    = time();
			$xmlStr['Nonce']        = $nonce;
			$response               = $this->_array2Xml($xmlStr);
		}

		exit($response);
	}

	/**
	 * Sign a mesage with SHA1
	 * @param 	string 	$encrypt_msg Message to sign
	 * @param 	string 	$nonce Random characters string
	 * @return 	string
	 */
	public function getSHA1($encrypt_msg, $nonce) {
		$array = array($encrypt_msg, $this->token, time(), $nonce);
		sort($array, SORT_STRING);
		$str = implode($array);

		return sha1($str);
	}

	/**
	 * Set Text response content
	 * @param  string $content Text content
	 */
	private function event($content) {
		$this->data['Event']    = 'VIEW';
		$this->data['EventKey'] = $content;
	}

	/**
	 * Set Text response content
	 * @param  string $content Text content
	 */
	private function text($content) {
		$this->data['Content'] = $content;
	}

	/**
	 * Set Music response content
	 * @param  string $content Music content
	 */
	private function music($music) {
		list(
			$music['Title'],
			$music['Description'],
			$music['MusicUrl'],
			$music['HQMusicUrl']
		) = $music;
		$this->data['Music'] = $music;
	}

	/**
	 * Set image response content
	 * @param  string $image image content
	 */
	private function image($image) {
		list(
			$image['MediaId']
		) = $image;
		$this->data['Image'] = $image;
	}

	/**
	 * Set Rich Media response content
	 * @param  string $news Rich Media content
	 */
	private function news($news) {
		$articles = array();

		foreach ($news as $key => $value) {
			$articles[$key]                = array();
			$articles[$key]['Title']       = $value['Title'];
			$articles[$key]['Description'] = $value['Description'];
			$articles[$key]['PicUrl']      = $value['PicUrl'];
			$articles[$key]['Url']         = $value['Url'];

			if ($key >= 9) {
				break; 
			} // Maximum 10 news
		}

		$this->data['ArticleCount'] = count($articles);
		$this->data['Articles']     = $articles;
	}

	/**
	 * Convert an aray to XML string
	 * @param 	array $array Array to convert
	 * @return 	string
	 */
	private function _array2Xml($array) {
		$xml  = new \SimpleXMLElement('<xml></xml>');
		$this->_data2xml($xml, $array);

		return $xml->asXML();
	}

	/**
	 * Convert data to XML string
	 * @param  object $xml  Receiving XML object
	 * @param  mixed  $data Data
	 * @param  string $item Default node name replacing numeric index in $data - default "item"
	 * @return string
	 */
	private function _data2xml($xml, $data, $item = 'item') {

		foreach ($data as $key => $value) {
			is_numeric($key) && $key = $item;

			if (is_array($value) || is_object($value)) {
				$child = $xml->addChild($key);
				$this->_data2xml($child, $value, $item);
			} else {

				if (is_numeric($value)) {
					$child = $xml->addChild($key, $value);
				} else {
					$child = $xml->addChild($key);
					$node  = dom_import_simplexml($child);
					$node->appendChild($node->ownerDocument->createCDATASection($value));
				}
			}
		}
	}

	/**
	 * Send templated message
	 * @param object $content The content of the templated message
	 * @see http://admin.wechat.com/wiki/index.php?title=Templated_Messages
	 * @return boolean
	 */
	public function sendTemplate($content) {
		$params = $this->json_encode($content);
		$url    = self::TEMPLATE_SEND_URL . '?access_token=' . $this->getAccessToken();
		$jsonStr = $this->http($url, $params, 'POST');
		$jsonArr = $this->parseJson($jsonStr);

		if ($jsonArr) {

			return true;
		} else {

			return false;
		}
	}

	/**
	 * Send customer service message
	 * @param 	string 	$openid 	Receiver's openID
	 * @param 	array 	$content 	Message content - all values in the array must be of type string
	 * @param 	string 	$type 		Message type - default "text"
	 * @return 	boolean
	 */
	public function sendMsg($openid, $content, $type = 'text') {
		$this->send ['touser']  = $openid;
		$this->send ['msgtype'] = $type;
		$sendtype               = 'send' . $type;

		if (!method_exists($this, $type)) {
			$this->setError('Invalid WeChat customer service message type "' . $type . '"');

			exit(false);
		}

		$this->$sendtype($content);

		$params  = $this->json_encode($this->send);
		$url     = self::CUSTOM_SEND_URL . '?access_token=' . $this->getAccessToken();
		$jsonStr = $this->http($url, $params, 'POST');
		$jsonArr = $this->parseJson($jsonStr);

		if ($jsonArr) {

			return true;
		} else {

			return false;
		}
	}

	/**
	 * Send a Text message
	 * @param string $content Text content
	 */
	private function sendtext($content) {
		$this->send['text'] = array(
			'content' => $content,
		);
	}

	/**
	 * Send an Image message
	 * @param string $content 要发送的信息
	 */
	private function sendimage($content) {
		$this->send['image'] = array(
			'media_id' => $content,
		);
	}

	/**
	 * Send a Video message
	 * @param  string $content Video content
	 */
	private function sendvideo($video) {
		list (
			$video ['media_id'],
			$video ['title'],
			$video ['description']
		) = $video;
		$this->send ['video'] = $video;
	}

	/**
	 * Send a Voice message
	 * @param string $content Voice content
	 */
	private function sendvoice($content) {
		$this->send['voice'] = array(
			'media_id' => $content,
		);
	}

	/**
	 * Send a Music message
	 * @param string $content Music content
	 */
	private function sendmusic($music) {
		list ( 
			$music['title'], 
			$music['description'], 
			$music['musicurl'], 
			$music['hqmusicurl'], 
			$music['thumb_media_id']
		) = $music;
		$this->send['music'] = $music;
	}

	/**
	 * Send a Rich Media message
	 * @param  string $news Rich Media content
	 */
	private function sendnews($news) {
		$articles = array();

		foreach ($news as $key => $value) {
			$articles[$key]                = array();
			$articles[$key]['Title']       = $value['Title'];
			$articles[$key]['Description'] = $value['Description'];
			$articles[$key]['PicUrl']      = $value['PicUrl'];
			$articles[$key]['Url']         = $value['Url'];

			if ($key >= 9) {
				break;
			} // Maximum 10 news
		}

		$this->send['articles'] = $articles;
	}

	/**
	 * Get authentication redirect URL for WeChat browser authentication
	 * @param 	string 	$callback 	Callback URL (including http(s)://)
	 * @param 	sting 	$state 		Any state information (a-zA-Z0-9) to preserve across the OAuth process, for example a token to prevent CSRF attacks - default empty string
	 * @param 	string 	$scope 		'snsapi_userinfo' will require user approval and get the user's full public profile ; 'snsapi_base' will get the user's openid - default "snsapi_base"
	 * @return 	string 	Authentication redirect URL
	 */
	public function getOAuthRedirect($callback, $state = '', $scope = 'snsapi_base') {

		return self::OAUTH_AUTHORIZE_URL . '?appid=' . $this->appid . '&redirect_uri=' . rawurlencode($callback) . '&response_type=code&scope=' . $scope . '&state=' . $state . '#wechat_redirect';
	}

	/**
	 * Get QR Code authentication redirect URL for web browser authentication
	 * @param 	string 	$callback 	Callback URL (including http(s)://)
	 * @param 	sting 	$state 		Any state information (a-zA-Z0-9) to preserve across the OAuth process, for example a token to prevent CSRF attacks - default empty string
	 * @param 	string 	$scope 		'snsapi_userinfo' will require user approval and get the user's full public profile ; 'snsapi_base' will get the user's openid - default "snsapi_base"
	 * @return 	string 	QR Code authentication redirect URL
	 */
	public function getOAuthQR($callback, $state = '', $scope = 'snsapi_base') {

		return self::QR_AUTHORIZATION_URL . '?appid='.$this->appid . '&redirect_uri=' . rawurlencode($callback) . '&response_type=code&scope=' . $scope . '&state=' . $state . '#wechat_redirect';
	}

	/**
	 * Get user access_token information from OAuth code
	 * @return array|boolean
	 */
	public function getOauthAccessToken() {
		$code = filter_input(INPUT_GET, 'code', FILTER_SANITIZE_STRING);

		if (!$code) {

			return false;
		}

		$params  = array(
			'appid'      => $this->appid,
			'secret'     => $this->secret,
			'code'       => $code,
			'grant_type' => 'authorization_code',
		);
		$jsonStr = $this->http(self::OAUTH_USER_TOKEN_URL, $params);
		$jsonArr = $this->parseJson($jsonStr);

		if ($jsonArr) {

			return $jsonArr;
		} else {

			return false;
		}
	}

	/**
	 * Get user access_token information from refresh_token
	 * @param string $refresh_token
	 * @return array|boolean
	 */
	public function refreshOauthAccessToken($refresh_token) {
		$params   = array(
			'appid'         => $this->appid,
			'refresh_token' => $refresh_token,
			'grant_type'    => 'refresh_token',
		);
		$jsonStr = $this->http(self::OAUTH_REFRESH_URL, $params);
		$jsonArr = $this->parseJson($jsonStr);

		if ($jsonArr) {

			return $jsonArr;
		} else {

			return false;
		}
	}

	/**
	 * Get authenticated user's public information
	 * @param  string $access_token  The get token obtained by the getOauthAccessToken method
	 * @param  string $openid        User's OpenID
	 * @return array
	 */
	public function getOauthUserInfo($access_token, $openid) {
		$params  = array(
			'access_token' => $access_token,
			'openid'       => $openid,
			'lang'         => 'zh_CN',
		);
		$jsonStr = $this->http(self::GET_USER_INFO_URL, $params);
		$jsonArr = $this->parseJson($jsonStr);

		if ($jsonArr) {

			return $jsonArr;
		} else {

			return false;
		}
	}

	/**
	 * Get jsapi_ticket
	 * @return array|boolean
	 */
	public function getJsapiTicket() {
		$params  = array(
			'access_token' => $this->getAccessToken(),
			'type'         => 'jsapi',
		);
		$jsonStr = $this->http(self::JSAPI_TICKET_URL, $params);
		$jsonArr = $this->parseJson($jsonStr);

		if ($jsonArr) {

			return $this->result['ticket'];
		} else {

			return false;
		}
	}

	/**
	 * Get parametric QR code image URL
	 * @param  integer $scene_id 	Scene value - temporary code: 32 bits (integer); permanent code: no more than 1,000 - default null
	 * @param  boolean $limit    	true for temporary QR code, false for permanent - default true
	 * @param  integer $expire   	QR code validity time - up to 1,800 seconds - default 1,800
	 * @param  string  $scene_str   Scene value - up to 64 characters - default empty string
	 * @return string|boolean
	 */
	public function getQRUrl($scene_id = null, $limit = true, $expire = 1800, $scene_str = '') {

		if (!isset($this->ticket)) {

			if (!$this->qrcode($scene_id, $limit, $expire, $scene_str)) {
			
				return false;
			}
		}

		return self::QRCODE_SHOW_URL . '?ticket=' . $this->ticket;
	}

	/**
	 * Generate parametric QR code
	 * @param  integer $scene_id 	Scene value - temporary code: 32 bits (integer); permanent code: no more than 1,000 - default null
	 * @param  boolean $limit    	true for temporary QR code, false for permanent - default true
	 * @param  integer $expire   	QR code validity time - up to 1,800 seconds - default 1,800
	 * @param  string  $scene_str   Scene value - up to 64 characters - default empty string
	 * @return string|boolean
	 */
	private function qrcode($scene_id = null, $limit = true, $expire = 1800, $scene_str = '') {

		if (!$scene_id && (empty($scene_str) || strlen($scene_str) > 64)) {
			$this->setError('Invalid scene_str');

			return false;
		} else if (!$scene_id || !is_numeric($scene_id) || $scene_id > 100000 || $scene_id < 1) {
			$this->setError('Invalid scene_id');

			return false;
		}

		$params['action_name'] = $limit ? 'QR_SCENE' : 'QR_LIMIT_SCENE';

		if ($limit) {
			$params['expire_seconds'] = $expire;
		}

		$sceneKey              = ($scene_id) ? 'scene_id' : 'scene_str';
		$sceneValue            = ($scene_str) ? $scene_str : $scene_id;
		$params['action_info'] = array(
			'scene' => array(
				$sceneKey => $sceneValue,
			)
		);
		$params  = $this->json_encode($params);
		$url     = self::QRCODE_URL . '?access_token=' . $this->getAccessToken();
		$jsonStr = $this->http($url, $params, 'POST');
		$jsonArr = $this->parseJson($jsonStr);

		if ($jsonArr) {

			return $this->ticket = $jsonArr['ticket'];
		} else {

			return false;
		}
	}

	/**
	 * JSON encode without escaping Chinese characters
	 * @param  array $array Array to encode - default empty array
	 * @return json
	 */
	public function json_encode($array = array()) {
		$res = preg_replace_callback(
			"#\\\u([0-9a-f]+)#i",
			function($matches) {

				foreach($matches as $match){
					$current_encoding = mb_detect_encoding($match, 'auto');

					if ($current_encoding !== 'UTF-8') {

						return iconv($current_encoding, 'UTF-8', $match);
					} else {
						return $match;
					}
				}
			},
			str_replace("\\/", "/", json_encode($array, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))
		);

		return $res;
	}

	/**
	 * Parse JSON string received from wechat
	 * If failure, set an error message and return false.
	 * @param 	string $json JSON string to parse
	 * @return 	array
	 */
	private function parseJson($json) {
		$jsonArr = json_decode($json, true);

		if (isset($jsonArr['errcode'])) {

			if ($jsonArr['errcode'] == 0) {

				$this->result = $jsonArr;

				return true;
			} else {
				$error_message = $this->getErrorMessage($jsonArr['errcode']);

				$this->setError($error_message, $jsonArr['errcode']);

				return false;
			}
		} else {

			return $jsonArr;
		}
	}

	/**
	 * Convert base64-encoded AES encrypted message to XML string
	 * @param  string $encrypted Encrypted message
	 * @return string|boolean
	 */
	public function AESdecode($encrypted) {
		$key            = base64_decode($this->AESKey . "=");
		$ciphertext_dec = base64_decode($encrypted);
		$module         = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
		$iv             = substr($key, 0, 16);

		mcrypt_generic_init($module, $key, $iv);

		$decrypted      = mdecrypt_generic($module, $ciphertext_dec);

		mcrypt_generic_deinit($module);
		mcrypt_module_close($module);

		$pad = ord(substr($decrypted, -1));

		if ($pad < 1 || $pad > 32) {
			$pad = 0;
		}

		$result = substr($decrypted, 0, (strlen($decrypted) - $pad));

		if (strlen($result) < 16) {
			$this->setError('AESdecode Result Length Error');

			return false;
		}

		$content     = substr($result, 16);
		$len_list    = unpack("N", substr($content, 0, 4));
		$xml_len     = $len_list[1];
		$xml_content = substr($content, 4, $xml_len);
		$from_appid  = substr($content, $xml_len + 4);

		if ($from_appid != $this->appid) {
			$this->setError('AESdecode AppId Error');

			return false;
		} else {

			return $this->_extractXml($xml_content);
		}
	}

	/**
	 * Convert string to base64-encoded AES encrypted message
	 * @param  string $text Text to encrypt
	 * @return boolean
	 */
	public function AESencode($text) {
		$key           = base64_decode($this->AESKey . "=");
		$random        = self::getNonceStr();
		$text          = $random . pack("N", strlen($text)) . $text . $this->appid;
		$size          = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
		$module        = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
		$iv            = substr($key, 0, 16);
		$text_length   = strlen($text);
		$amount_to_pad = 32 - ($text_length % 32);

		if ($amount_to_pad == 0) {
			$amount_to_pad = 32;
		}

		$pad_chr = chr($amount_to_pad);
		$tmp     = '';

		for ($index = 0; $index < $amount_to_pad; $index++) {
			$tmp .= $pad_chr;
		}

		$text = $text . $tmp;

		mcrypt_generic_init($module, $key, $iv);

		$encrypted = mcrypt_generic($module, $text);

		mcrypt_generic_deinit($module);
		mcrypt_module_close($module);

		return base64_encode($encrypted);
	}

	/**
	 * Generate a 20-digit order ID, optionally using a 1-bit prefix
	 * @param  string $prefix Order ID prefix used to differenciate business types - default empty string
	 * @return string
	 */
	public static function createOrderId($prefix = '') {
		$code = date('ymdHis') . sprintf("%08d", mt_rand(1, 99999999));

		if (!empty($prefix)) {
			$code = $prefix . substr($code, strlen($prefix));
		}

		return $code;
	}

	/**
	 * Gets a random string composed of [A-Za-z0-9] characters
	 * @param  integer $length Length of the returned string - default 16
	 * @return string
	 */
	public static function getNonceStr($length = 16)	{
		$str_pol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";

		return substr(str_shuffle($str_pol), 0, $length);
	}

	/**
	 * Send HTTP request using CURL
	 * @param  string  $url    Request's URL
	 * @param  array   $params Request's parameters - default empty array
	 * @param  string  $method Request's method ("GET" or "POST") - default "GET"
	 * @param  boolean $ssl    Whether to use SSL authentication - default false
	 * @return array   $data   响应数据
	 */
	private function http($url, $params = array(), $method = 'GET', $ssl = false) {
		$opts = array(
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false
		);

		switch(strtoupper($method)) {
			case 'GET':
				$getQuerys         = !empty($params) ? '?' .  http_build_query($params) : '';
				$opts[CURLOPT_URL] = $url . $getQuerys;
				break;
			case 'POST':
				$opts[CURLOPT_URL]        = $url;
				$opts[CURLOPT_POST]       = 1;
				$opts[CURLOPT_POSTFIELDS] = $params;
				break;
		}

		if ($ssl) {
			$pemCert = $this->pemPath . $this->pem . '_cert.pem';
			$pemKey  = $this->pemPath . $this->pem . '_key.pem';

			if (!file_exists($pemCert)) {
				$this->setError('Invalid pem certificate path');

				return false;
			}

			if (!file_exists($pemKey)) {
				$this->setError('Invalid pem key path');

				return false;
			}

			$opts[CURLOPT_SSLCERTTYPE] = 'PEM';
			$opts[CURLOPT_SSLCERT]     = $pemCert;
			$opts[CURLOPT_SSLKEYTYPE]  = 'PEM';
			$opts[CURLOPT_SSLKEY]      = $pemKey;
		}

		if ($this->proxy && !empty($this->proxyHost) && !empty($this->proxyPort)) {
			$opts[CURLOPT_PROXY]     = $this->proxy;
			$opts[CURLOPT_PROXYPORT] = $this->proxyPort;
		}
		$ch = curl_init();

		curl_setopt_array($ch, $opts);

		$data   = curl_exec($ch);
		$err    = curl_errno($ch);
		$errmsg = curl_error($ch);

		curl_close($ch);

		if ($err > 0) {
			$this->setError($errmsg, $err);

			return false;
		} else {

			return $data;
		}
	}

	/**
	 * Check if the provided paths to certificate files are valid.
	 * @return bool
	 */
	public function cert_files_exist() {

		return file_exists( $this->pemPath . $this->pem . '_cert.pem' ) && file_exists( $this->pemPath . $this->pem . '_key.pem' );
	}

	/**
	 * Create a temporary Asset (aka media)
	 * @param  string $file  Absolute path to a file
	 * @param  string $type  Type of Asset - "image", "voice", "video", "thumb"
	 * @param 	string 	$video_title 		Title of the video - required for video Asset type - default null
	 * @param 	string 	$video_introduction Introduction of the video - required for video Asset type - default null
	 * @return array
	 */
	public function upload_media($file, $type, $video_title = null, $video_introduction = null) {

		if (!in_array($type, array('image', 'voice', 'video', 'thumb'))) {
			$this->setError('Invalid permanent Asset type "' . $type . '"');

			return false;
		}

		if ($type === 'video' && (!$video_title || !$video_introduction)) {
			$this->setError('Invalid video Asset: title and introduction required');

			return false;
		}

		$url    = self::MEDIA_UPLOAD_URL . '?access_token=' . $this->getAccessToken() . '&type=' . $type;
		$params = array(
			'media' => new CurlFile( $file, mime_content_type($file), basename($file) ),
		);

		if ($type === 'video') {
			$video_description = array(
				'title'        => $video_title,
				'introduction' => $video_introduction,
			);
			$params['description'] = json_encode($video_description);
		}

		$jsonStr = $this->http($url, $params, 'POST');
		$jsonArr = $this->parseJson($jsonStr);

		if ($jsonArr) {

			return $jsonArr;
		} else {

			return false;
		}
	}

	/**
	 * Get temporary Asset (aka media)
	 * @param  string $media_id ID of the media
	 * @return array
	 */
	public function get_media($media_id) {
		$url     = self::MEDIA_GET_URL;
		$params  = array(
			'access_token' => $this->getAccessToken(),
			'media_id'     => $media_id,
		);
		$jsonStr = $this->http($url, $params);
		$jsonArr = $this->parseJson($jsonStr);

		if ($jsonArr) {

			return $jsonArr;
		} else {

			return false;
		}
	}

	/**
	 * Add permanent Rich Media Asset
	 * @param 	array $articles An array of Rich Media Assets
	 * @return 	array|bool
	 * @see https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1444738729 (Chinese)
	 */
	public function add_rich_media_asset($articles) {
		self::MATERIAL_NEWS_URL . '?access_token=' . $this->getAccessToken();

		$params  = $this->json_encode($articles);
		$jsonStr = $this->http($url, $params, 'POST');
		$jsonArr = $this->parseJson($jsonStr);

		if ($jsonArr) {

			return $jsonArr;
		} else {

			return false;
		}
	}

	/**
	 * Add permanent Asset (excluding Rich Media Asset)
	 * @param 	string 	$file 				Absolute path to a file
	 * @param 	string 	$type 				Type of Asset - "image", "voice", "video", "thumb"
	 * @param 	string 	$video_title 		Title of the video - required for video Asset type - default null
	 * @param 	string 	$video_introduction Introduction of the video - required for video Asset type - default null
	 * @return 	array|bool
	 */
	public function add_file_asset($file, $type, $video_title = null, $video_introduction = null) {

		if (!in_array($type, array('image', 'voice', 'video', 'thumb'))) {
			$this->setError('Invalid permanent Asset type "' . $type . '"');

			return false;
		}

		if ($type === 'video' && (!$video_title || !$video_introduction)) {
			$this->setError('Invalid video Asset: title and introduction required');

			return false;
		}

		$url    = self::MATERIAL_FILE_URL . '?access_token=' . $this->getAccessToken() . '&type=' . $type;
		$params = array(
			'media' => '@' . $file . ';type=' . $type . ';filename=' . basename($file),
		);

		if ($type === 'video') {
			$video_description = array(
				'title'        => $video_title,
				'introduction' => $video_introduction,
			);
			$params['description'] = json_encode($video_description);
		}

		$jsonStr = $this->http($url, $params, 'POST');
		$jsonArr = $this->parseJson($jsonStr);

		if ($jsonArr) {

			return $jsonArr;
		} else {

			return false;
		}
	}

	/**
	 * Get permanent Asset
	 * @param  string $asset_id
	 * @return array|bool
	 */
	public function get_asset($asset_id) {
		$url     = self::MATERIAL_GET_URL . '?access_token=' . $this->getAccessToken();
		$params  = array(
			'media_id' => $asset_id,
		);
		$params  = $this->json_encode($params);
		$jsonStr = $this->http($url, $params, 'POST');
		$jsonArr = $this->parseJson($jsonStr);

		if ($jsonArr) {

			return $jsonArr;
		} else {

			return false;
		}
	}

	/**
	 * Delete permanent Asset
	 * @param  string $asset_id Asset ID
	 * @return boolean
	 */
	public function delete_asset($asset_id) {
		$url     = self::MATERIAL_DEL_URL . '?access_token=' . $this->getAccessToken();
		$params  = array(
			'media_id' => $asset_id,
		);
		$params  = $this->json_encode($params);
		$jsonStr = $this->http($url, $params, 'POST');
		$jsonArr = $this->parseJson($jsonStr);

		if ($jsonArr) {

			return true;
		} else {

			return false;
		}
	}

	/**
	 * Get permanent Assets quantity information
	 * @return array|bool
	 */
	public function count_asset() {
		$params  = array(
			'access_token' => $this->getAccessToken(),
		);
		$jsonStr = $this->http(self::MATERIAL_COUNT_URL, $params);
		$jsonArr = $this->parseJson($jsonStr);

		if ($jsonArr) {

			return $jsonArr;
		} else {

			return false;
		}
	}

	/**
	 * Get permanent Assets list
	 * @param  string  $type    Asset type - "image", "video", "voice", "news"
	 * @param  integer $offset  List starting position offset - default 0
	 * @param  integer $count   Number of Assets - default 20
	 * @return array|bool
	 */
	public function get_asset_list($type, $offset = 0, $count = 20) {
		$params  = array(
			'type'   => $type,
			'offset' => $offset,
			'count'  => $count,
		);
		$url     = self::MATERIAL_LIST_URL . '?access_token=' . $this->getAccessToken();
		$params  = $this->json_encode($params);
		$jsonStr = $this->http($url, $params, 'POST');
		$jsonArr = $this->parseJson($jsonStr);

		if ($jsonArr) {

			return $jsonArr;
		} else {

			return false;
		}
	}

	/**
	 * Get URL for a Unified order (use in web browser)
	 * @param  string 		$product_id 	Local product identifier
	 * @param  string 		$body       	Product Description - 126 bytes max
	 * @param  string 		$orderId    	Local order ID
	 * @param  float  		$money 			Amound in RMB
	 * @param  string 		$notify_url 	Callback URL - default empty string
	 * @param  array|string $extend  		Used to extend the parameters sent to the WeChat payment interface - if string, will be attributed to 'attach' - default empty array
	 * @return string|bool
	 */
	public function webUnifiedOrder($product_id, $body, $orderId, $money, $notify_url = '', $extend = array()) {

		if (strlen($body) > 127) {
			$body = substr($body, 0, 127);
		}

		$params = array(
			'version'          => self::UNIFIED_ORDER_INTERFACE_VERSION,
			'appid'            => $this->mch_appid,
			'mch_id'           => $this->mch_id,
			'nonce_str'        => self::getNonceStr(),
			'body'             => $body,
			'out_trade_no'     => $orderId,
			'total_fee'        => $money * 100,
			'spbill_create_ip' => $this->_get_client_ip(),
			'notify_url'       => $notify_url,
			'product_id'       => $product_id,
			'trade_type'       => 'NATIVE',
		);

		if (is_string($extend)) {
			$params['attach']  = $extend;
		} elseif (is_array($extend) && !empty($extend)) {
			$params = array_merge($params, $extend);
		}

		$params['sign'] = self::_getOrderMd5($params);
		$data           = $this->_array2Xml($params);
		$data           = $this->http(self::UNIFIED_ORDER_URL, $data, 'POST');
		$data           = $this->_extractXml($data);

		if ($data) {

			if ($data['return_code'] === 'SUCCESS') {

				if ($data['result_code'] === 'SUCCESS') {

					return $data['code_url'];
				} else {
					$this->setError($data['err_code_des'], $data['err_code']);

					return false;
				}
			} else {
				$this->setError($data['return_msg'], $data['return_code']);

				return false;
			}
		} else {
			$this->setError('Invalid XML data - failed to create web Unified Order');

			return false;
		}

	}

	/**
	 * Get JSON Unified order (use with JSAPI in WeChat browser)
	 * @param  string 		$openid     	User OpenID
	 * @param  string 		$body       	Product Description - 126 bytes max.
	 * @param  string 		$orderId    	Local order ID
	 * @param  float  		$money 			Amound in RMB
	 * @param  string 		$notify_url 	Callback URL - default empty string
	 * @param  array|string $extend  		Used to extend the parameters sent to the WeChat payment interface - if string, will be attributed to 'attach' - default empty array
	 * @return array|boolean
	 */
	public function unifiedOrder($openid, $body, $orderId, $money, $notify_url = '', $extend = array()) {

		if (strlen($body) > 127) {
			$body = substr($body, 0, 127);
		}

		$params = array(
			'version'          => self::UNIFIED_ORDER_INTERFACE_VERSION,
			'openid'           => $openid,
			'appid'            => $this->mch_appid,
			'mch_id'           => $this->mch_id,
			'nonce_str'        => self::getNonceStr(),
			'body'             => $body,
			'out_trade_no'     => $orderId,
			'total_fee'        => $money * 100,
			'spbill_create_ip' => $this->_get_client_ip(),
			'notify_url'       => $notify_url,
			'trade_type'       => 'JSAPI',
		);

		if (is_string($extend)) {
			$params['attach']  = $extend;
		} elseif (is_array($extend) && !empty($extend)) {
			$params = array_merge($params, $extend);
		}

		$params['sign'] = self::_getOrderMd5($params);
		$data           = $this->_array2Xml($params);
		$data           = $this->http(self::UNIFIED_ORDER_URL, $data, 'POST');
		$data           = $this->_extractXml($data);

		if ($data) {

			if ($data['return_code'] === 'SUCCESS') {

				if ($data['result_code'] === 'SUCCESS') {

					return array(
						'payment_params' => $this->createPayParams($data['prepay_id']),
						'prepay_id'      => $data['prepay_id'],
					);
				} else {
					$this->setError($data['err_code_des'], $data['err_code']);

					return false;
				}
			} else {
				$this->setError($data['return_msg'], $data['return_code']);

				return false;
			}
		} else {
			$this->setError('Invalid XML data - failed to create Unified Order');

			return false;
		}
	}

	/**
	 * Generate payment parameters
	 * @param  string $prepay_id The prepay_id parameter generated by the WeChat payment interface
	 * @return string
	 */
	private function createPayParams($prepay_id) {

		if (empty($prepay_id)) {
			$this->setError('prepay_id is required');

			return false;
		}

		$params['appId']     = $this->mch_appid;
		$params['timeStamp'] = (string) time();
		$params['nonceStr']  = self::getNonceStr();
		$params['package']   = 'prepay_id=' . $prepay_id;
		$params['signType']  = 'MD5';
		$params['paySign']   = self::_getOrderMd5($params);

		return $this->json_encode($params);
	}

	/**
	 * Get order info from the WeChat pay interface
	 * @param  string 	$orderId 	Order ID
	 * @param  bool 	$remote 	If set to true, order ID is a WeChat payment interface transaction_id ; Local order ID otherwise - default false
	 * @return boolean|array
	 */
	public function getOrderInfo($orderId, $remote = false) {
		$params['appid']  = $this->mch_appid;
		$params['mch_id'] = $this->mch_id;

		if ($remote) {
			$params['transaction_id'] = $orderId;
		} else {
			$params['out_trade_no'] = $orderId;
		}

		$params['nonce_str'] = self::getNonceStr();
		$params['sign']      = self::_getOrderMd5($params);
		$data                = $this->_array2Xml($params);
		$data                = $this->http(self::ORDER_QUERY_URL, $data, 'POST');

		return self::parsePayRequest($data);
	}

	/**
	 * Close order
	 * @param  string $orderId Local order ID
	 * @return boolean|array
	 */
	public function closeOrder($orderId) {
		$params['appid']        = $this->mch_appid;
		$params['mch_id']       = $this->mch_id;
		$params['out_trade_no'] = $orderId;
		$params['nonce_str']    = self::getNonceStr();
		$params['sign']         = self::_getOrderMd5($params);
		$data                   = $this->_array2Xml($params);
		$data                   = $this->http(self::CLOSE_ORDER_URL, $data, 'POST');

		return self::parsePayRequest($data);
	}

	/**
	 * Request a refund (Requires an SSL certificate)
	 * @param  string 		$orderId 		Local order ID
	 * @param  string 		$refundId 		Merchant refund ID ([A-Za-z_- | * @])
	 * @param  float 		$total_fee 		Total order fee in RMB
	 * @param  float 		$refund_fee 	Refund fee in RMB - default 0
	 * @param  array|string $extend  		Used to extend the parameters sent to the WeChat payment interface - if string, will be attributed to 'refund_desc' - default empty array
	 * @return boolean|array
	 */
	public function refundOrder($orderId, $refundId, $total_fee, $refund_fee = 0, $extend = array()) {
		$params = array();

		if (is_string($extend)) {
			$params['refund_desc']  = $extend;
		} elseif (is_array($extend) && !empty($extend)) {
			$params = array_merge($params, $extend);
		}

		$params['appid']         = $this->mch_appid;
		$params['mch_id']        = $this->mch_id;
		$params['nonce_str']     = self::getNonceStr();
		$params['out_trade_no']  = $orderId;
		$params['out_refund_no'] = $refundId;
		$params['total_fee']     = (int)($total_fee * 100);
		$params['refund_fee']    = (int)($refund_fee * 100);
		$params['op_user_id']    = $this->mch_id;
		$params['sign']          = self::_getOrderMd5($params);
		$data                    = $this->_array2Xml($params);
		$data                    = $this->http(self::PAY_REFUND_ORDER_URL, $data, 'POST', true);

		return self::parsePayRequest($data);
	}

	/**
	 * Get Local order refund status from the WeChat payment interface 
	 * @param  string $orderId Local order ID
	 * @return boolean|array
	 */
	public function getRefundStatus($orderId) {
		$params['appid']        = $this->mch_appid;
		$params['mch_id']       = $this->mch_id;
		$params['nonce_str']    = self::getNonceStr();
		$params['out_trade_no'] = $orderId;
		$params['sign']         = self::_getOrderMd5($params);
		$data                   = $this->_array2Xml($params);
		$data                   = $this->http(self::REFUND_QUERY_URL, $data, 'POST');
		
		return self::parsePayRequest($data);
	}

	/**
	 * Download billing statement for date
	 * @param  date  	$date day for which to get the billing statements - format Ymd - default today's date
	 * @param  string 	$type TYpe of statement to return - ALL: return all ; SUCCESS: successful payments REFUND: refunded orders REVOKED: revoked orders - default "ALL"
	 * @return boolean|array
	 */
	public function downloadBill($date = '', $type = 'ALL') {
		$date                = $date ?: date('Ymd');
		$params['bill_date'] = $date;
		$params['bill_type'] = $type;
		$params['appid']     = $this->mch_appid;
		$params['mch_id']    = $this->mch_id;
		$params['nonce_str'] = self::getNonceStr();
		$params['sign']      = self::_getOrderMd5($params);		
		$data                = $this->_array2Xml($params);
		$data                = $this->http(self::DOWNLOAD_BILL_URL, $data, 'POST');
		
		return self::parsePayRequest($data, false);
	}

	/**
	 * Create 28-digits Merchant order ID
	 * @return integer
	 */
	private function createMchBillNo() {
		$micro = microtime(true) * 100;
		$micro = ceil($micro);
		$rand  = substr($micro, -8) . \Tools\String::randNumber(0,99);

		return $this->mch_id . date('Ymd') . $rand;
	}

	/**
	 * Send shared red envelope
	 * @param 	string 	$openid User OpenID
	 * @param 	string 	$money 	Amount in RMB
	 * @param 	integer $num 	Red envelop divisor - default 1
	 * @param 	array 	$data 	Red envelope data
	 * @return 	boolean|array
	 */
	public function sendGroupRedPack($openid, $money, $num = 1, $data) {
		$params['mch_billno']   = self::createMchBillNo();
		$params['send_name']    = $data['send_name'];
		$params['re_openid']    = $openid;
		$params['total_amount'] = $money * 100;
		$params['total_num']    = $num;
		$params['amt_type']     = 'ALL_RAND';
		$params['wishing']      = $data['wishing'];
		$params['act_name']     = $data['act_name'];
		$params['remark']       = $data['remark'];
		$params['mch_id']       = $this->mch_id;
		$params['wxappid']      = $this->mch_appid;
		$params['nonce_str']    = self::getNonceStr();
		$params['sign']         = self::_getOrderMd5($params);
		$data                   = $this->_array2Xml($params);
		$data                   = $this->http(self::SEND_GROUP_RED_PACK_URL, $data, 'POST', true);

		return self::parsePayRequest($data, false);
	}

	/**
	 * Send red envelope
	 * @param  string $openid User OpenID
	 * @param  string $money  Amount in RMB
	 * @param  array  $data   Red envelope data
	 * @return boolean|array
	 */
	public function sendRedPack($openid, $money, $data) {
		$params['mch_billno']   = self::createMchBillNo();
		$params['nick_name']    = $data['send_name'];
		$params['send_name']    = $data['send_name'];
		$params['re_openid']    = $openid;
		$params['total_amount'] = $money * 100;
		$params['min_value']    = $money * 100;
		$params['max_value']    = $money * 100;
		$params['total_num']    = 1;
		$params['wishing']      = $data['wishing'];
		$params['act_name']     = $data['act_name'];
		$params['remark']       = $data['remark'];
		$params['client_ip']    = $this->_get_client_ip();
		$params['mch_id']       = $this->mch_id;
		$params['wxappid']      = $this->mch_appid;
		$params['nonce_str']    = self::getNonceStr();
		$params['sign']         = self::_getOrderMd5($params);
		$data                   = $this->_array2Xml($params);
		$data                   = $this->http(self::SEND_RED_PACK_URL, $data, 'POST', true);

		return self::parsePayRequest($data, false);
	}

	/**
	 * Get red envelope information
	 * @param  string $billNo Red envelope's Merchant order ID
	 * @return array
	 */
	public function getRedPack($billNo) {
		$params['mch_billno'] = $billNo;
		$params['mch_id']     = $this->mch_id;
		$params['appid']      = $this->mch_appid;
		$params['bill_type']  = 'MCHT';
		$params['nonce_str']  = self::getNonceStr();
		$params['sign']       = self::_getOrderMd5($params);
		$data                 = $this->_array2Xml($params);
		$data                 = $this->http(self::GET_RED_PACK_INFO_URL, $data, 'POST', true);

		return self::parsePayRequest($data, false);
	}

	/**
	 * Parse result of the WeChat payment interface
	 * @param  xmlstring $data      The data returned by the interface
	 * @param  boolean   $checkSign Whether signature verification is required - default true
	 * @return boolean|array
	 */
	private function parsePayRequest($data, $checkSign = true) {
		$data = $this->_extractXml($data);

		if (empty($data)) {
			$this->setError('Payment interface returned invalid XML data');

			return false;
		}


		if ($data['return_code'] === 'SUCCESS') {

			if ($checkSign) {

				if (!self::_checkSign($data)) {

					return false;
				}
			}

			if ($data['result_code'] === 'SUCCESS') {

				return $data;
			} else {
				$this->setError($data['err_code_des'], $data['err_code']);

				return false;
			}
		} else {
			$this->setError($data['return_msg'], $data['return_code']);

			return false;
		}
	}

	/**
	 * Get WeChat payment interface notification
	 * @return array
	 */
	public function getNotify() {
		$data = file_get_contents("php://input");

		return self::parsePayRequest($data);
	}

	/**
	 * Return a notification to the WeChat payment interface
	 * @param  string $return_msg Error message to return to the WeChat payment interface - default empty string
	 * @return string
	 */
	public function returnNotify($return_msg = '') {

		if (empty($return_msg)) {
			$data = array(
				'return_code' => 'SUCCESS',
				'return_msg'  => 'OK',
			);
		} else {
			$data = array(
				'return_code' => 'FAIL',
				'return_msg'  => $return_msg,
			);
		}

		exit($this->_array2Xml($data));
	}

	/**
	 * Check payment data signature
	 * @param  $data The data from WeChat interface
	 * @return boolean
	 */
	private function _checkSign($data) {
		$sign = (string) $data['sign'];

		unset($data['sign']);

		if (self::_getOrderMd5($data) !== $sign) {
			$this->setError('Signature verification failed');

			return false;
		} else {

			return true;
		}
	}

	/**
	 * Sign order data with MD5
	 * @param  array $params data to sign
	 * @return string
	 */
	private function _getOrderMd5($params) {
		ksort($params);
		$params['key'] = $this->payKey;

		return strtoupper(md5(urldecode(http_build_query($params))));
	}

	/**
	 * Get client ip.
	 *
	 * @return string
	 */
	private function _get_client_ip() {
		if (!empty($_SERVER['REMOTE_ADDR'])) {
			$ip = $_SERVER['REMOTE_ADDR'];
		} else {
			$ip = gethostbyname(gethostname());
		}

		return filter_var($ip, FILTER_VALIDATE_IP) ?: '127.0.0.1';
	}

	/**
	 * Get last error message and error code
	 * @return array
	 */
	public function getError() {

		return (empty($this->error)) ? false : array('code' => $this->errorCode, 'message' => $this->error);
	}

	/**
	 * Set error message and error code
	 * @param 	string $message 	The error message - default empty string
	 * @param 	string $errorCode 	The error code - default null
	 * @return 	string
	 */
	public function setError($message = '', $errorCode = null) {
		$this->error     = $message;
		$this->errorCode = $errorCode;
	}

	/**
	 * Get an error message from an error code
	 * @param integer $code Error code
	 * @return string
	 */
	private function getErrorMessage($code) {

		switch ($code) {
			case -1: 	return 'WeChat API:	System busy';
			case 0: 	return 'WeChat API:	Request succeeded';
			case 40001: return 'WeChat API:	Verification failed';
			case 40002: return 'WeChat API:	Invalid certificate type';
			case 40003: return 'WeChat API:	Invalid Open ID';
			case 40004: return 'WeChat API:	Invalid media file type';
			case 40005: return 'WeChat API:	Invalid file type';
			case 40006: return 'WeChat API:	Invalid file size';
			case 40007: return 'WeChat API:	Invalid media file ID';
			case 40008: return 'WeChat API:	Invalid message type';
			case 40009: return 'WeChat API:	Invalid image file size';
			case 40010: return 'WeChat API:	Invalid audio file size';
			case 40011: return 'WeChat API:	Invalid video file size';
			case 40012: return 'WeChat API:	Invalid thumbnail file size';
			case 40013: return 'WeChat API:	Invalid App ID';
			case 40014: return 'WeChat API:	Invalid access token';
			case 40015: return 'WeChat API:	Invalid menu type';
			case 40016: return 'WeChat API:	Invalid button quantity';
			case 40017: return 'WeChat API:	Invalid button quantity';
			case 40018: return 'WeChat API:	Invalid button name length';
			case 40019: return 'WeChat API:	Invalid button KEY length';
			case 40020: return 'WeChat API:	Invalid button URL length';
			case 40021: return 'WeChat API:	Invalid menu version';
			case 40022: return 'WeChat API:	Invalid sub-menu levels';
			case 40023: return 'WeChat API:	Invalid sub-menu button quantity';
			case 40024: return 'WeChat API:	Invalid sub-menu button type';
			case 40025: return 'WeChat API:	Invalid sub-menu button name length';
			case 40026: return 'WeChat API:	Invalid sub-menu button KEY length';
			case 40027: return 'WeChat API:	Invalid sub-menu button URL length';
			case 40028: return 'WeChat API:	Invalid custom menu user';
			case 40029: return 'WeChat API:	Invalid oauth code';
			case 40030: return 'WeChat API:	Invalid refresh token';
			case 40031: return 'WeChat API:	Invalid openid list';
			case 40032: return 'WeChat API:	Invalid openid list length';
			case 40033: return 'WeChat API:	Invalid request characters: The character "\uxxxx" cannot be included.';
			case 40035: return 'WeChat API:	Invalid parameters';
			case 40038: return 'WeChat API:	Invalid request format';
			case 40039: return 'WeChat API:	Invalid URL length';
			case 40050: return 'WeChat API:	Invalid group ID';
			case 40051: return 'WeChat API:	Invalid group name';
			case 41001: return 'WeChat API:	Parameter missing: access token';
			case 41002: return 'WeChat API:	Parameter missing: appid';
			case 41003: return 'WeChat API:	Parameter missing: refresh token';
			case 41004: return 'WeChat API:	Parameter missing: secret';
			case 41005: return 'WeChat API:	Multimedia file data missing';
			case 41006: return 'WeChat API:	Parameter missing: media id';
			case 41007: return 'WeChat API:	Sub-menu data missing';
			case 41008: return 'WeChat API:	Parameter missing: oauth code';
			case 41009: return 'WeChat API:	Parameter missing: openid';
			case 42001: return 'WeChat API:	access token timed out';
			case 42002: return 'WeChat API:	refresh token timed out';
			case 42003: return 'WeChat API:	oauth code timed out';
			case 43001: return 'WeChat API:	GET request required';
			case 43002: return 'WeChat API:	POST request required';
			case 43003: return 'WeChat API:	HTTPS request required';
			case 43004: return 'WeChat API:	The other user is not yet a follower';
			case 43005: return 'WeChat API:	The other user is not yet a follower';
			case 44001: return 'WeChat API:	Multimedia file is empty';
			case 44002: return 'WeChat API:	POST package is empty';
			case 44003: return 'WeChat API:	Rich media message is empty';
			case 44004: return 'WeChat API:	Text message is empty';
			case 45001: return 'WeChat API:	Error source: multimedia file size';
			case 45002: return 'WeChat API:	Message contents too long';
			case 45003: return 'WeChat API:	Title too long';
			case 45004: return 'WeChat API:	Description too long';
			case 45005: return 'WeChat API:	URL too long';
			case 45006: return 'WeChat API:	Image URL too long';
			case 45007: return 'WeChat API:	Audio play time over limit';
			case 45008: return 'WeChat API:	Rich media messages over limit';
			case 45009: return 'WeChat API:	Error source: interface call';
			case 45010: return 'WeChat API:	Message quantity over limit';
			case 45015: return 'WeChat API:	Response too late';
			case 45016: return 'WeChat API:	System group cannot be changed.';
			case 45017: return 'WeChat API:	System name too long';
			case 45018: return 'WeChat API:	Too many groups';
			case 46001: return 'WeChat API:	Media data missing';
			case 46002: return 'WeChat API:	This menu version does not exist.';
			case 46003: return 'WeChat API:	This menu data does not exist.';
			case 46004: return 'WeChat API:	This user does not exist.';
			case 47001: return 'WeChat API:	Error while extracting JSON/XML contents';
			case 48001: return 'WeChat API:	Unauthorized API function';
			case 50001: return 'WeChat API:	The user is not authorized for this API';
			case 61450: return 'WeChat API:	System error (system error)';
			case 61451: return 'WeChat API:	Invalid parameter (invalid parameter)';
			case 61452: return 'WeChat API:	Invalid customer service account (invalid kf_account)';
			case 61453: return 'WeChat API:	Existing customer service account (kf_account existed)';
			case 61454: return 'WeChat API:	Length of customer service account name over limit (ten English characters at a maximum, excluding @ and the part after it) (invalid kf_acount length)';
			case 61455: return 'WeChat API:	Invalid characters in a customer service account name (English letters and numbers supported only) (illegal character in kf_account)';
			case 61456: return 'WeChat API:	Maximum number of customer service accounts reached(ten customer service accounts at a maximum) (kf_account count exceeded)';
			case 61457: return 'WeChat API:	Invalid image file type (invalid file type)';
			case 61500: return 'WeChat API:	Date format error';
			case 61501: return 'WeChat API:	Date range error';
			case 50001: return 'WeChat API: The user has not authorized the api';
			case 65303: return 'WeChat API: There is no menu. To create a conditonal menu or delete menus, create a default menu first';
			default: 	return 'Unknown error';
		}
	}

}

class WechatPayException extends Exception {}
class WechatException extends Exception {}