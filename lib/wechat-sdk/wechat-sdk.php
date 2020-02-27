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
	const USER_GET_URL         = 'https://api.weixin.qq.com/cgi-bin/user/get';
	const USER_INFO_URL        = 'https://api.weixin.qq.com/cgi-bin/user/info';
	const USER_INFO_BATCH_URL  = 'https://api.weixin.qq.com/cgi-bin/user/info/batchget';
	const TAG_ID_USER_URL      = 'https://api.weixin.qq.com/cgi-bin/tags/getidlist';
	const TAG_BATCH_UNTAG_URL  = 'https://api.weixin.qq.com/cgi-bin/tags/members/batchuntagging';
	const TAG_BATCH_TAG_URL    = 'https://api.weixin.qq.com/cgi-bin/tags/members/batchtagging';
	const TAGGED_USERS_GET_URL = 'https://api.weixin.qq.com/cgi-bin/user/tag/get';
	const TAG_DELETE_URL       = 'https://api.weixin.qq.com/cgi-bin/tags/delete';
	const TAG_UPDATE_URL       = 'https://api.weixin.qq.com/cgi-bin/tags/update';
	const TAG_GET_URL          = 'https://api.weixin.qq.com/cgi-bin/tags/get';
	const TAG_CREATE_URL       = 'https://api.weixin.qq.com/cgi-bin/tags/create';
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
	const MEDIA_UPLOAD_URL           = 'https://api.weixin.qq.com/cgi-bin/media/upload';               // add temporary Asset
	const MEDIA_GET_URL              = 'https://api.weixin.qq.com/cgi-bin/media/get';                  // get temporary Asset
	const MEDIA_NEWS_URL             = 'https://api.weixin.qq.com/cgi-bin/media/uploadnews';           // add temporary Rich Media Message Assets
	const MATERIAL_NEWS_URL          = 'https://api.weixin.qq.com/cgi-bin/material/add_news';          // add permanent Rich Media Message Assets
	const MATERIAL_NEWS_IMAGE_URL    = 'https://api.weixin.qq.com/cgi-bin/media/uploadimg';            // add permanent Rich Media Message Image asset - images in news
	const MATERIAL_FILE_URL          = 'https://api.weixin.qq.com/cgi-bin/material/add_material';      // add permanent Asset
	const MATERIAL_GET_URL           = 'https://api.weixin.qq.com/cgi-bin/material/get_material';      // get permanent Asset
	const MATERIAL_DEL_URL           = 'https://api.weixin.qq.com/cgi-bin/material/del_material';      // remove permanent Asset
	const MATERIAL_UPDATE_URL        = 'https://api.weixin.qq.com/cgi-bin/material/update_news';       // update permanent Rich Media Message Asset
	const MATERIAL_COUNT_URL         = 'https://api.weixin.qq.com/cgi-bin/material/get_materialcount'; // Get permanent Assets Count
	const MATERIAL_LIST_URL          = 'https://api.weixin.qq.com/cgi-bin/material/batchget_material'; // Get permanent Assets List
	/* Mass Broadcast URL */
	const MASS_BY_TAG  = 'https://api.weixin.qq.com/cgi-bin/message/mass/sendall';
	const MASS_BY_USER = 'https://api.weixin.qq.com/cgi-bin/message/mass/send';
	const MASS_DELETE  = 'https://api.weixin.qq.com/cgi-bin/message/mass/delete';
	const MASS_PREVIEW = 'https://api.weixin.qq.com/cgi-bin/message/mass/preview';
	const MASS_GET     = 'https://api.weixin.qq.com/cgi-bin/message/mass/get';
	/* Customer Service API */
	const CS_SEND_MESSAGE = 'https://api.weixin.qq.com/cgi-bin/message/custom/send';

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
	private $result = false;
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
			$this->parseJson($jsonStr);

			if (false === $this->getError()) {
				$jsonArr                   = $this->result;
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
		$res     = $this->parseJson($jsonStr);

		return ($res) ? $this->result : false;
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
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

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
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

			return true;
		} else {

			return false;
		}
	}

	/**
	 * Get official account's followers tags
	 * @return array|boolean
	 */
	public function tags() {
		$url     = self::TAG_GET_URL . '?access_token='.$this->getAccessToken();
		$jsonStr = $this->http($url);
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

			return $this->result['tags'];
		} else {

			return false;
		}
	}

	/**
	 * Add a followers tag to the official account
	 * @param string $name Followers' tag name
	 * @return boolean
	 */
	public function tag_add($name = '') {

		if (empty($name)) {
			$this->setError('Followers tag name required');

			return false;
		}

		$params = array(
			'tag' => array(
				'name' => $name,
			)
		);
		$params  = $this->json_encode($params);
		$url     = self::TAG_CREATE_URL . '?access_token=' . $this->getAccessToken();
		$jsonStr = $this->http($url, $params, 'POST');
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

			return $this->result['tag'];
		} else {

			return false;
		}
	}

	/**
	 * Update an official account's followers tag
	 * @param integer $tag_id Followers tag ID
	 * @param string $name New followers tag name
	 * @return boolean
	 */
	public function tag_update($tag_id = '', $name = '') {

		if (empty($name) || empty($tag_id)) {
			$this->setError('Followers tag ID and new Followers tag name required');

			return false;
		}

		$params  = array(
			'tag' => array(
				'id'   => $tag_id,
				'name' => $name,
			)
		);
		$params  = $this->json_encode($params);
		$url     = self::TAG_UPDATE_URL . '?access_token=' . $this->getAccessToken();
		$jsonStr = $this->http($url, $params, 'POST');
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

			return true;
		} else {

			return false;
		}
	}

	/**
	 * Delete an official account's followers tag
	 * @param integer $tag_id Followers tag ID
	 * @return boolean
	 */
	public function tag_delete($tag_id) {
		$params  = array(
			'tag' => array(
				'id'   => $tag_id,
			),
		);
		$params  = $this->json_encode($params);
		$url     = self::TAG_DELETE_URL . '?access_token=' . $this->getAccessToken();
		$jsonStr = $this->http($url, $params, 'POST');
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

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
	 * - 'total' is the number of total users in the account (not present if $tag_id is used)
	 * - 'count' is the number of users loaded
	 * - 'next_openid' the openID from which to load the batch of users
	 * @param  string $next_openid The openID from which to load the batch of users - default empty string
	 * @param  int    $tag_id The tag ID to filter the users list by - default null
	 * @return array|boolean
	 */
	public function users($next_openid = '', $tag_id = null) {
		$params = array();

		if (!empty($next_openid)) {
			$params['next_openid'] = $next_openid;
		}

		if (null !== $tag_id && is_numeric($tag_id)) {
			$url = self::TAGGED_USERS_GET_URL . '?access_token=' . $this->getAccessToken();
		} else {
			$url                    = self::USER_GET_URL;
			$params['access_token'] = $this->getAccessToken();
		}

		$jsonStr = $this->http($url, $params);
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {
			$data = $this->result['data']['openid'];

			unset($this->result['data']);

			$this->result['data'] = $data;

			return $this->result;
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
		$jsonArr = $this->user($openid);

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
			$this->setError('User openID required');

			return false;
		}

		$params = array(
			'access_token' => $this->getAccessToken(),
			'lang'         => 'zh_CN',
			'openid'       => $openid,
		);
		$jsonStr = $this->http(self::USER_INFO_URL, $params);
		$res     = $this->parseJson($jsonStr);

		return ($res) ? $this->result : false;
	}

	/**
	 * Get the information of a list of WeChat users
	 * @param  array $users_info the list of WeChat user info - array count max to 100. Each item is an array with keys 'openid' (required) and 'lang' (optional)
	 * @return array|boolean
	 */
	public function user_batch($users_info = array()) {

		if (empty($users_info)) {
			$this->setError('User openIDs required');

			return false;
		}

		$params  = array(
			'user_list' => $users_info,
		);
		$url     = self::USER_INFO_BATCH_URL . '?access_token=' . $this->getAccessToken();
		$params  = $this->json_encode($params);
		$jsonStr = $this->http($url, $params, 'POST');
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

			return $this->result['user_info_list'];
		} else {

			return false;
		}
	}

	/**
	 * Get tags assigned to a follower
	 * @param  string $openid  Follower's openID
	 * @return array|boolean
	 */
	public function user_tags($openid = '') {

		if (empty($openid)) {
			$this->setError('Follower openID required');

			return false;
		}

		$params  = array(
			'openid' => $openid,
		);
		$params  = $this->json_encode($params);
		$url     = self::TAG_ID_USER_URL . '?access_token=' . $this->getAccessToken();
		$jsonStr = $this->http($url, $params, 'POST');
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

			return $this->result['tagid_list'];
		} else {

			return false;
		}
	}

	/**
	 * Batch assign a follower's tag to followers
	 * @param array   $openids Follower's openIDs
	 * @param integer $tag_id Follower's tag ID
	 * @return boolean
	 */
	public function users_batch_assign_tag($openids = array(), $tag_id = '') {

		if (empty($openids) || !is_numeric($tag_id)) {
			$this->setError('Follower openIDs and numeric tag ID required');

			return false;
		}

		$params  = array(
			'openid_list' => $openids,
			'tagid'       => $tag_id,
		);
		$params  = $this->json_encode($params);
		$url     = self::TAG_BATCH_TAG_URL . '?access_token=' . $this->getAccessToken();
		$jsonStr = $this->http($url, $params, 'POST');
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

			return true;
		} else {

			return false;
		}
	}

	/**
	 * Batch unassign a follower's tag from followers
	 * @param array   $openids Follower's openIDs
	 * @param integer $tag_id Follower's tag ID
	 * @return boolean
	 */
	public function users_batch_unassign_tag($openids = array(), $tag_id = '') {

		if (empty($openids) || !is_numeric($tag_id)) {
			$this->setError('Follower openIDs and numeric tag ID required');

			return false;
		}

		$params  = array(
			'openid_list' => $openids,
			'tagid'       => $tag_id,
		);
		$params  = $this->json_encode($params);
		$url     = self::TAG_BATCH_UNTAG_URL . '?access_token=' . $this->getAccessToken();
		$jsonStr = $this->http($url, $params, 'POST');
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

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
	 * Reply to a WeChat message (auto-reply).
	 * Uses the Customer Service API.
	 * A call to "exit" is necessary after calling this method (of after error handling).
	 * @param  string $to      Receiver's OpenID
	 * @param  string $from    Developer's ID
	 * @param  string $type    Message type - "text", "music", "news", "voice", "video", "mpnews", "msgmenu" - default "text"
	 * @param  array  $content Response information - all values in the array must be of type string
	 * @return string|bool
	 */
	public function response($type = 'text', $content = '') {
		$this->data = array(
			'touser'  => $this->data['fromusername'],
			'msgtype' => $type,
		);

		if (!method_exists($this, $type)) {
			$this->setError('Invalid WeChat response message type "' . $type . '"');

			return false;
		}

		$this->$type($content);

		$response = 'success';

		ob_start();

		$serverProtocol = filter_input(INPUT_SERVER, 'SERVER_PROTOCOL', FILTER_SANITIZE_STRING);

		echo 'success';

		header($serverProtocol . ' 200 OK');
		header('Content-type: plain/text');
		header('Content-Encoding: none');
		header('Connection: close');
		header('Content-Length: ' . ob_get_length());

		ob_end_flush();
		ob_flush();
		flush();

		$params  = $this->json_encode($this->data);
		$url     = self::CS_SEND_MESSAGE . '?access_token=' . $this->getAccessToken();
		$jsonStr = $this->http($url, $params, 'POST');
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

			return true;
		} else {

			return false;
		}
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
	private function text($content) {
		$this->data['text'] = array('content' => $content);
	}

	/**
	 * Set Music response content
	 * @param  array $content Music content
	 */
	private function music($music) {
		$content = array();

		$content['title']          = $music['title'];
		$content['description']    = $music['description'];
		$content['musicurl']       = $music['musicurl'];
		$content['hqmusicurl']     = $music['hqmusicurl'];
		$content['thumb_media_id'] = $music['thumb_media_id'];

		$this->data['music'] = $content;
	}

	/**
	 * Set image response content
	 * @param  array $image image content
	 */
	private function image($image) {
		$content = array();

		$content['media_id'] = $image['media_id'];

		$this->data['image'] = $content;
	}

	/**
	 * Set Rich Media response content
	 * @param  array $news Rich Media content
	 */
	private function news($news) {
		$articles = array();

		foreach ($news as $key => $value) {
			$articles[$key]                = array();
			$articles[$key]['title']       = $value['title'];
			$articles[$key]['description'] = $value['description'];
			$articles[$key]['picurl']      = $value['picurl'];
			$articles[$key]['url']         = $value['url'];

			if ($key >= 1) {
				break; 
			} // Maximum 1 news
		}

		$this->data['news'] = array('articles' => $articles);
	}

	/**
	 * Set voice response content
	 * @param  array $voice voice content
	 */
	private function voice($voice) {
		$content = array();

		$content['media_id'] = $voice['media_id'];

		$this->data['voice'] = $content;
	}

	/**
	 * Set video response content
	 * @param  string $video video content
	 */
	private function video($video) {
		$content = array();

		$content['media_id']       = $video['media_id'];
		$content['thumb_media_id'] = $video['thumb_media_id'];
		$content['title']          = $video['title'];
		$content['description']    = $video['description'];

		$this->data['video'] = $content;
	}

	/**
	 * Set mpnews response content
	 * @param  array $mpnews mpnews content
	 */
	private function mpnews($mpnews) {
		$content = array();

		$content['media_id'] = $mpnews['media_id'];

		$this->data['mpnews'] = $content;
	}

	/**
	 * Set menu response content
	 * @param  array $menu menu content
	 */
	private function msgmenu($menu) {
		$content = array();

		$content['head_content'] = $menu['head_content'];
		$content['tail_content'] = $menu['tail_content'];
		$content['list']         = array();

		foreach ($menu['list'] as $key => $value) {
			$item = array();

			if ( is_array( $value ) ) {

				if ( isset( $value['id'] ) ) {
					$item['id'] = $value['id'];
				} else {
					$item['id'] = (string) $key;
				}

				$item['content'] = $value['content'];
			} else {
				$item['id']      = (string) $key;
				$item['content'] = $value;
			}

			$content['list'][] = $item;
		}

		$this->data['msgmenu'] = $content;
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
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

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
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

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
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

			return $this->result;
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
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

			return $this->result;
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
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

			return $this->result;
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
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

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
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

			return $this->ticket = $this->result['ticket'];
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

		if (isset($jsonArr['errcode']) || !$jsonArr) {

			if (!$jsonArr) {
				$error_message = $this->getErrorMessage();

				$this->setError($error_message);
			} elseif (
				empty($jsonArr['errcode']) ||
				0 === $jsonArr['errcode'] ||
				null === $jsonArr['errcode']
			) {
				$this->result = $jsonArr;

				return true;
			} else {
				$error_message = $this->getErrorMessage($jsonArr['errcode']);

				$this->setError($error_message, $jsonArr['errcode']);

				return false;
			}
		} else {
			$this->setError(null, null);

			$this->result = $jsonArr;

			return true;
		}
	}

	/**
	 * Convert base64-encoded AES encrypted message to XML string
	 * @param  string $encrypted Encrypted message
	 * @return string|boolean
	 */
	public function AESdecode($encrypted) {
		$key            = base64_decode($this->AESKey);
		$ciphertext_dec = base64_decode($encrypted);
		$iv             = substr($key, 0, 16);

		$decrypted = openssl_decrypt(
			$ciphertext_dec,
			'aes-256-cbc',
			$key,
			OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
			$iv
		);

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
		$iv            = substr($key, 0, 16);
		$text_length   = strlen($text);
		$amount_to_pad = 32 - ($text_length % 32);

		if ($amount_to_pad === 0) {
			$amount_to_pad = 32;
		}

		$pad_chr = chr($amount_to_pad);
		$tmp     = '';

		for ($index = 0; $index < $amount_to_pad; $index++) {
			$tmp .= $pad_chr;
		}

		$text = $text . $tmp;
        
        $ciphertext = openssl_encrypt(
            $text,
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        return base64_encode($iv . $ciphertext);
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
			$opts[CURLOPT_PROXY]     = $this->proxyHost;
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
	 * Create a temporary Asset (aka media - excluding Rich Media Asset)
	 * @param string $file               Absolute path to a file
	 * @param string $type               Type of Asset - "image", "voice", "video", "thumb"
	 * @param string $video_title        Title of the video - required for video Asset type - default null
	 * @param string $video_introduction Introduction of the video - required for video Asset type - default null
	 * @return array
	 */
	public function upload_media($file, $type, $video_title = null, $video_introduction = null) {

		if (!in_array($type, array('image', 'voice', 'video', 'thumb'))) {
			$this->setError('Invalid temporary Asset type "' . $type . '"');

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
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

			return $this->result;
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
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

			return $this->result;
		} else {

			return false;
		}
	}

	/**
	 * Add temporary Rich Media Assets
	 * @param 	array $articles  An array of Rich Media Assets
	 * @return 	array|bool
	 */
	public function upload_rich_media($articles) {
		$url = self::MEDIA_NEWS_URL . '?access_token=' . $this->getAccessToken();

		$params  = $this->json_encode($articles);
		$jsonStr = $this->http($url, $params, 'POST');
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

			return $this->result;
		} else {

			return false;
		}
	}

	/**
	 * Add permanent Asset (excluding Rich Media Asset)
	 * @param 	string 	$file 				Absolute path to a file
	 * @param 	string 	$type 				Type of Asset - "image", "voice", "video", "thumb", "news_image"
	 * @param 	string 	$video_title 		Title of the video - required for video Asset type - default null
	 * @param 	string 	$video_introduction Introduction of the video - required for video Asset type - default null
	 * @return 	array|bool
	 */
	public function add_file_asset($file, $type, $video_title = null, $video_introduction = null) {

		if (!in_array($type, array('image', 'voice', 'video', 'thumb', 'news_image'))) {
			$this->setError('Invalid permanent Asset type "' . $type . '"');

			return false;
		}

		if ($type === 'video' && (!$video_title || !$video_introduction)) {
			$this->setError('Invalid video Asset: title and introduction required');

			return false;
		}

		if ('news_image' === $type) {
			$api = self::MATERIAL_NEWS_IMAGE_URL;
		} else {
			$api = self::MATERIAL_FILE_URL;
		}

		$url    = $api . '?access_token=' . $this->getAccessToken();
		$params = array(
			'media' => new CurlFile( $file, mime_content_type($file), basename($file) ),
		);

		if ('news_image' != $type) {
			$url .= '&type=' . $type;
		}

		if ($type === 'video') {
			$video_description = array(
				'title'        => $video_title,
				'introduction' => $video_introduction,
			);
			$params['description'] = json_encode($video_description);
		}

		$jsonStr = $this->http($url, $params, 'POST');
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

			return $this->result;
		} else {

			return false;
		}
	}

	/**
	 * Add permanent Rich Media Assets
	 * @param 	array $articles  An array of Rich Media Assets
	 * @return 	array|bool
	 */
	public function add_rich_media_asset($articles) {
		$url = self::MATERIAL_NEWS_URL . '?access_token=' . $this->getAccessToken();

		$params  = $this->json_encode($articles);
		$jsonStr = $this->http($url, $params, 'POST');
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

			return $this->result;
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
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

			return $this->result;
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
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

			return true;
		} else {

			return false;
		}
	}

	/**
	 * Get permanent Assets quantity information
	 * @return array|bool
	 */
	public function count_assets() {
		$params  = array(
			'access_token' => $this->getAccessToken(),
		);
		$jsonStr = $this->http(self::MATERIAL_COUNT_URL, $params);
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

			return $this->result;
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
	public function get_assets_list($type, $offset = 0, $count = 20) {
		$params  = array(
			'type'   => $type,
			'offset' => $offset,
			'count'  => $count,
		);
		$url     = self::MATERIAL_LIST_URL . '?access_token=' . $this->getAccessToken();
		$params  = $this->json_encode($params);
		$jsonStr = $this->http($url, $params, 'POST');
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

			return $this->result;
		} else {

			return false;
		}
	}

	/**
	 * Send preview before broadcast
	 * @param 	array  $message       An array representation of the message to send
	 * @param   string $message_type  The type of message - one of "image", "video", "voice", "text", "news", "mpvideo", "mpnews", "wxcard" - "news" is an alias of "mpnews", "video" is an alias of "mpvideo"
	 * @param 	string $id            The open ID or wechat ID of the recipient of the preview
	 * @param 	string $id_type       The type of ID given - "openid" for Open ID, "wxname" for Wechat ID
	 * @return 	array|bool
	 * @see https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1444738729 (Chinese)
	 */
	public function mass_preview($message, $message_type, $id, $id_type) {
		$url     = self::MASS_PREVIEW . '?access_token=' . $this->getAccessToken();
		$payload = array();

		if ('openid' === $id_type) {
			$payload['touser'] = $id;
		} elseif ('wxname' === $id_type) {
			$payload['towxname'] = $id;
		} else {
			$this->setError('Invalid recipient type "' . $type . '" - must me "open_id" or "wxname"');

			return false;
		}

		if (!in_array( $message_type, array('image', 'video', 'voice', 'text', 'news', 'mpvideo', 'mpnews', 'wxcard'))) {
			$this->setError('Invalid message type "' . $type . '"');

			return false;
		}

		if ('news' === $message_type || 'video' === $message_type) {
			$message_type = 'mp' . $message_type;
		}

		$payload[$message_type] = $message;
		$payload['msgtype']     = $message_type;

		$params  = $this->json_encode($payload);
		$jsonStr = $this->http($url, $params, 'POST');
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

			return $this->result;
		} else {

			return false;
		}
	}

	/**
	 * Send mass broadcast to a defined list of users by openIDs
	 * @param 	array  $message             An array representation of the message to send
	 * @param   string $message_type        The type of message - one of "image", "video", "voice", "text", "news", "mpvideo", "mpnews", "wxcard" - "news" is an alias of "mpnews", "video" is an alias of "mpvideo"
	 * @param 	array  $openids             An array of openIDs of the recipients of the broadcast
	 * @param 	bool   $send_ignore_reprint Whether or not to continue sending the image message if it has been determined to be a reprint - true to continue sending (reprinting), false to stop the batch send - default false.
	 * @param 	string     $clientmsgid         ID to avoid sending the same message repeatedly. May be up to 64 characters in length, automatically truncated. If this field is not set, then the back end will automatically use the message scope and content preview as the clientmsgid - defaul false.
	 * @return 	array|bool
	 * @see https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1444738729 (Chinese)
	 */
	public function mass_to_users($message, $message_type, $openids, $send_ignore_reprint = false, $clientmsgid = false) {
		$url     = self::MASS_BY_USER . '?access_token=' . $this->getAccessToken();
		$payload = array();

		if (!in_array( $message_type, array('image', 'video', 'voice', 'text', 'news', 'mpvideo', 'mpnews', 'wxcard'))) {
			$this->setError('Invalid message type "' . $message_type . '"');

			return false;
		}

		if (!is_array($openids)) {
			$openids = array($openids);
		}

		$payload['touser']              = $openids;
		$payload['send_ignore_reprint'] = (int) $send_ignore_reprint;

		if (false !== $clientmsgid) {
			$payload['clientmsgid'] = substr($clientmsgid, 0, 64);
		}

		if ('news' === $message_type || 'video' === $message_type) {
			$message_type = 'mp' . $message_type;
		}

		$payload[$message_type] = $message;
		$payload['msgtype']     = $message_type;

		$params  = $this->json_encode($payload);
		$jsonStr = $this->http($url, $params, 'POST');
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

			return $this->result;
		} else {

			return false;
		}
	}

	/**
	 * Send mass broadcast to a all followers, or filter recipients by assigned tag
	 * @param 	array      $message             An array representation of the message to send
	 * @param   string     $message_type        The type of message - one of "image", "video", "voice", "text", "news", "mpvideo", "mpnews", "wxcard" - "news" is an alias of "mpnews", "video" is an alias of "mpvideo"
	 * @param 	string|int $tag_id              A valid tag_id used to filter recipients, or "all" - default "all"
	 * @param 	bool       $send_ignore_reprint Whether or not to continue sending the image message if it has been determined to be a reprint - true to continue sending (reprinting), false to stop the batch send - default false.
	 * @param 	string     $clientmsgid         ID to avoid sending the same message repeatedly. May be up to 64 characters in length, automatically truncated. If this field is not set, then the back end will automatically use the message scope and content preview as the clientmsgid - defaul false.
	 * @return 	array|bool
	 * @see https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1444738729 (Chinese)
	 */
	public function mass_to_all_or_tag($message, $message_type, $tag_id = 'all', $send_ignore_reprint = false, $clientmsgid = false) {
		$url     = self::MASS_BY_TAG . '?access_token=' . $this->getAccessToken();
		$payload = array();

		if (!is_numeric($tag_id) && 'all' !== $tag_id) {
			$this->setError('Invalid tag_id - need a valid tag ID');

			return false;
		}

		if (!in_array( $message_type, array('image', 'video', 'voice', 'text', 'news', 'mpvideo', 'mpnews', 'wxcard'))) {
			$this->setError('Invalid message type "' . $message_type . '"');

			return false;
		}

		$payload[$message_type]         = $message;
		$payload['msgtype']             = $message_type;
		$payload['send_ignore_reprint'] = (int) $send_ignore_reprint;
		$payload['filter']              = array();

		if (false !== $clientmsgid) {
			$payload['clientmsgid'] = substr($clientmsgid, 0, 64);
		}

		if ('news' === $message_type || 'video' === $message_type) {
			$message_type = 'mp' . $message_type;
		}

		if ('all' !== $tag_id) {
			$payload['filter'] = array(
				'is_to_all' => false,
				'tag_id'    => $tag_id,
			);
		} else {
			$payload['filter'] = array( 'is_to_all' => true );
		}

		$params  = $this->json_encode($payload);
		$jsonStr = $this->http($url, $params, 'POST');
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

			return $this->result;
		} else {

			return false;
		}
	}

	/**
	 * Delete an article from a previous mass message
	 * @param 	string $msg_id             The ID of the previously broadcasted message
	 * @param   int    $article_idx        The positive index of the article to delete in the message (starts with 1), or 0 to delete all the articles - optional - default 0
	 * @return 	array|bool
	 * @see https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1444738729 (Chinese)
	 */
	public function mass_delete( $msg_id, $article_idx = 0 ) {

		if (!is_numeric($article_idx) || $article_idx < 0 || $article_idx > 8) {
			$this->setError('Invalid article_idx - need an integer between 0 (included) and 8 (included)');

			return false;
		}

		$url     = self::MASS_DELETE . '?access_token=' . $this->getAccessToken();
		$payload = array(
			'msg_id'      => $msg_id,
			'article_idx' => $article_idx,
		);
		$params  = $this->json_encode($payload);
		$jsonStr = $this->http($url, $params, 'POST');
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

			return $this->result;
		} else {

			return false;
		}
	}

	/**
	 * Check the status of a previous mass message
	 * @param 	string $msg_id             The ID of the previously broadcasted message
	 * @return 	array|bool
	 * @see https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1444738729 (Chinese)
	 */
	public function mass_check_status( $msg_id ) {
		$url     = self::MASS_GET . '?access_token=' . $this->getAccessToken();
		$payload = array(
			'msg_id'      => $msg_id,
		);
		$params  = $this->json_encode($payload);
		$jsonStr = $this->http($url, $params, 'POST');
		$res     = $this->parseJson($jsonStr);

		if (false === $this->getError()) {

			return $this->result;
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
	 * Get URL for a Unified order (use in mobile web browser)
	 * @param  string 		$product_id 	Local product identifier
	 * @param  string 		$body       	Product Description - 126 bytes max
	 * @param  string 		$orderId    	Local order ID
	 * @param  float  		$money 			Amound in RMB
	 * @param  string 		$notify_url 	Callback URL - default empty string
	 * @param  array|string $extend  		Used to extend the parameters sent to the WeChat payment interface - if string, will be attributed to 'attach' - default empty array
	 * @return string|bool
	 */
	public function mobileUnifiedOrder($product_id, $body, $orderId, $money, $notify_url = '', $extend = array()) {

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
			'trade_type'       => 'MWEB',
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

					return $data['mweb_url'];
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

		if (empty($data)) {
			$this->setError('Payment interface returned invalid XML data');

			return false;
		}

		$data = $this->_extractXml($data);

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

		if ( empty($this->errorCode)) {

			return false;
		} elseif ('local' === $this->errorCode) {

			return array('code' => null, 'message' => $this->error);
		}

		return array('code' => $this->errorCode, 'message' => $this->error);
	}

	/**
	 * Set error message and error code
	 * @param 	string $message 	The error message - default empty string
	 * @param 	string $errorCode 	The error code - default 'local'
	 * @return 	string
	 */
	public function setError($message = '', $errorCode = 'local') {
		$this->error     = $message;
		$this->errorCode = $errorCode;
	}

	/**
	 * Get an error message from an error code
	 * @param integer $code Error code
	 * @return string
	 */
	private function getErrorMessage($code = null) {

		switch ($code) {
			case -1:
				return 'WeChat API: System busy. Please try again later.';
			case 0:
				return 'WeChat API: Successful request';
			case 40001:
				return 'WeChat API: AppSecret error or invalid Access Token. Verify the AppSecret is correct and confirm you are using the correct callback parameters for this Official Account.';
			case 40002:
				return 'WeChat API: Invalid certificate type or grant_type';
			case 40003:
				return 'WeChat API: Invalid OpenID. Confirm that the user has followed the account, or check that the OpenID does not belong to another Official Account.';
			case 40004:
				return 'WeChat API: Invalid media file type';
			case 40005:
				return 'WeChat API: Invalid file type';
			case 40006:
				return 'WeChat API: Invalid file size';
			case 40007:
				return 'WeChat API: Invalid media ID';
			case 40008:
				return 'WeChat API: Invalid message type';
			case 40009:
				return 'WeChat API: Invalid image file size';
			case 40010:
				return 'WeChat API: Invalid audio file size';
			case 40011:
				return 'WeChat API: Invalid video file size';
			case 40012:
				return 'WeChat API: Invalid thumbnail image file size';
			case 40013:
				return 'WeChat API: Invalid AppID. Verify the AppID is correct and has no invalid characters (AppIDs are case-sensitive)';
			case 40014:
				return 'WeChat API: Invalid Access Token. Confirm the Access Token has not expired and that you are using the correct callback parameters for this Official Account.';
			case 40015:
				return 'WeChat API: Invalid menu type';
			case 40016:
				return 'WeChat API: Invalid number of menu buttons';
			case 40017:
				return 'WeChat API: Invalid number of menu buttons or menu button type';
			case 40018:
				return 'WeChat API: Invalid menu button name length';
			case 40019:
				return 'WeChat API: Invalid menu button key length';
			case 40020:
				return 'WeChat API: Invalid URL length';
			case 40021:
				return 'WeChat API: Invalid menu version';
			case 40022:
				return 'WeChat API: Invalid sub-menu';
			case 40023:
				return 'WeChat API: Invalid number of sub-menu buttons';
			case 40024:
				return 'WeChat API: Invalid sub-menu type';
			case 40025:
				return 'WeChat API: Invalid sub-menu button name length';
			case 40026:
				return 'WeChat API: Invalid sub-menu button key length';
			case 40027:
				return 'WeChat API: Invalid sub-menu button URL length';
			case 40028:
				return 'WeChat API: Invalid menu user';
			case 40029:
				return 'WeChat API: Invalid or expired oauth_code';
			case 40030:
				return 'WeChat API: Invalid refresh_token';
			case 40031:
				return 'WeChat API: Invalid OpenID list';
			case 40032:
				return 'WeChat API: Invalid OpenID list length';
			case 40033:
				return 'WeChat API: Invalid character in your request. The character “\uxxxx” cannot be included.';
			case 40035:
				return 'WeChat API: Invalid parameter';
			case 40036:
				return 'WeChat API: Invalid template_id length';
			case 40037:
				return 'WeChat API: Invalid template_id';
			case 40038:
				return 'WeChat API: Invalid request format';
			case 40039:
				return 'WeChat API: Invalid URL length';
			case 40048:
				return 'WeChat API: Invalid URL domain';
			case 40050:
				return 'WeChat API: Invalid Group ID';
			case 40051:
				return 'WeChat API: Invalid Group ID name';
			case 40053:
				return 'WeChat API: Invalid “actioninfo” parameter';
			case 40054:
				return 'WeChat API: Invalid sub-menu button URL domain';
			case 40055:
				return 'WeChat API: Invalid menu button URL domain';
			case 40056:
				return 'WeChat API: Invalid code';
			case 40066:
				return 'WeChat API: Invalid URL';
			case 40071:
				return 'WeChat API: Invalid coupon type';
			case 40072:
				return 'WeChat API: Invalid coding method';
			case 40073:
				return 'WeChat API: Invalid cardid';
			case 40078:
				return 'WeChat API: Invalid coupon status';
			case 40079:
				return 'WeChat API: Invalid time';
			case 40080:
				return 'WeChat API: Invalid CardExt';
			case 40099:
				return 'WeChat API: Coupon already redeemed';
			case 40100:
				return 'WeChat API: Invalid time interval';
			case 40116:
				return 'WeChat API: Invalid code';
			case 40117:
				return 'WeChat API: Invalid Group ID name';
			case 40118:
				return 'WeChat API: Invalid media_id size';
			case 40119:
				return 'WeChat API: Button type error';
			case 40120:
				return 'WeChat API: Button type error';
			case 40121:
				return 'WeChat API: Invalid media_id type';
			case 40122:
				return 'WeChat API: Invalid in-stock quantity';
			case 40124:
				return 'WeChat API: Membership card settings reached the limit of custom_field';
			case 40125:
				return 'WeChat API: Invalid appsecret';
			case 40127:
				return 'WeChat API: Coupon has been deleted by user or is in the process of being transferred';
			case 40130:
				return 'WeChat API: Invalid openIDs list size, at least two openIDs required';
			case 40132:
				return 'WeChat API: Invalid WeChat ID';
			case 40137:
				return 'WeChat API: Unsupported image file type';
			case 40155:
				return 'WeChat API: Please do not add links to other Official Account pages';
			case 40164:
				return 'WeChat API: This account is using an IP white list. Only IP addresses listed on the WeChat backend can get this account’s WeChat access token.';
			case 41001:
				return 'WeChat API: Missing access_token';
			case 41002:
				return 'WeChat API: Missing appid';
			case 41003:
				return 'WeChat API: Missing refresh_token parameter';
			case 41004:
				return 'WeChat API: Missing secret parameter';
			case 41005:
				return 'WeChat API: Missing multimedia file data';
			case 41006:
				return 'WeChat API: Missing media_id parameter';
			case 41007:
				return 'WeChat API: Missing menu data';
			case 41008:
				return 'WeChat API: Missing oauth code';
			case 41009:
				return 'WeChat API: Missing openID';
			case 41010:
				return 'WeChat API: Missing URL';
			case 41011:
				return 'WeChat API: Missing required field';
			case 41012:
				return 'WeChat API: Missing cardid parameter';
			case 42001:
				return 'WeChat API: The access_token has expired. Check Access Token expiry time or review detailed documentation on creating an access token';
			case 42002:
				return 'WeChat API: The refresh_token has expired';
			case 42003:
				return 'WeChat API: The oauth_code expired';
			case 42007:
				return 'WeChat API: User has changed the WeChat password, the Access Token and Refresh Token failed, developer needs to get permissions again';
			case 43001:
				return 'WeChat API: GET request is required';
			case 43002:
				return 'WeChat API: POST request is required';
			case 43003:
				return 'WeChat API: HTTPS is required';
			case 43004:
				return 'WeChat API: The user must follow the account';
			case 43005:
				return 'WeChat API: You must be friends with the user';
			case 43009:
				return 'WeChat API: Custom SN permission, please enable on the WeChat backend.';
			case 43010:
				return 'WeChat API: No stored value permission, please enable on the WeChat backend.';
			case 43019:
				return 'WeChat API: You will need to remove user(s) from your blacklist in order to receive messages.';
			case 44001:
				return 'WeChat API: Multimedia file is empty';
			case 44002:
				return 'WeChat API: POST data packet is empty';
			case 44003:
				return 'WeChat API: Article content is empty';
			case 44004:
				return 'WeChat API: Text message is empty';
			case 44005:
				return 'WeChat API: List is empty';
			case 45001:
				return 'WeChat API: Multimedia file exceeds maximum file size';
			case 45002:
				return 'WeChat API: Message contents too long';
			case 45003:
				return 'WeChat API: Article title too long';
			case 45004:
				return 'WeChat API: Article short description too long';
			case 45005:
				return 'WeChat API: URL too long';
			case 45006:
				return 'WeChat API: Image URL too long';
			case 45007:
				return 'WeChat API: Audio playback time too long. Maximum voice message is sixty seconds.';
			case 45008:
				return 'WeChat API: Too many articles in this message.';
			case 45009:
				return 'WeChat API: API usage frequency limit reached.';
			case 45010:
				return 'WeChat API: Create menu limit reached.';
			case 45011:
				return 'WeChat API: API usage frequency limit reached, please try again later';
			case 45012:
				return 'WeChat API: Template size exceeds limit';
			case 45015:
				return 'WeChat API: Response time too long';
			case 45016:
				return 'WeChat API: This is a default system group, you are not allowed to modify it';
			case 45017:
				return 'WeChat API: Group ID name is too long';
			case 45018:
				return 'WeChat API: You have created the maximum number of groups allowed.';
			case 45021:
				return 'WeChat API: Field length exceeded, please refer to the field explanation in the relevant API documentation.';
			case 45030:
				return 'WeChat API: This CardID does not have permission to use this interface.';
			case 45031:
				return 'WeChat API: Current stock is zero.';
			case 45033:
				return 'WeChat API: The number of user receive events has exceeded the get_limit';
			case 45047:
				return 'WeChat API: Customer Service message volume limit reached.';
			case 45065:
				return 'WeChat API: The group has already been sent during the last 24 hours (clientmsgid exists).';
			case 45066:
				return 'Sending group message retry speed is too fast (limit is 1 minute interval)';
			case 45067:
				return 'Value of clientmsgid length exceeds limit';
			case 46001:
				return 'WeChat API: Media data does not exist';
			case 46002:
				return 'WeChat API: Menu version does not exist';
			case 46003:
				return 'WeChat API: Menu data does not exist';
			case 46004:
				return 'WeChat API: User does not exist';
			case 47001:
				return 'WeChat API: Error extracting JSON/XML content';
			case 48001:
				return 'WeChat API: You do not have permission to use this API. Check the Developer page on the WeChat Official Account backend for restrictions.';
			case 48002:
				return 'WeChat API: User cannot receive messages. The user has disabled the “Receive Messages”option on the account profile page.';
			case 48003:
				return 'WeChat API: Invalid suitetoken';
			case 48004:
				return 'WeChat API: API interface closed. Refer to the WeChat backend for more details.';
			case 48005:
				return 'WeChat API: This content cannot be deleted as it is being used in auto-replies or menus.';
			case 48006:
				return 'WeChat API: API reset limit reached';
			case 50001:
				return 'WeChat API: The user does not have permission to use this API.';
			case 50002:
				return 'WeChat API: This user is restricted and after violation may be banned from using this interface';
			case 61300:
				return 'WeChat API: Invalid base_info';
			case 61301:
				return 'WeChat API: Invalid detail_info';
			case 61302:
				return 'WeChat API: Invalid product promotion section information (action_info)';
			case 61303:
				return 'WeChat API: Product information does not exist';
			case 61304:
				return 'WeChat API: Invalid product in the promoted services section (action_info)';
			case 61305:
				return 'WeChat API: Invalid keystand or keystr. Using ean13 standards, the encoded content must match the merchant number.';
			case 61306:
				return 'WeChat API: Invalid appid in the promoted services section (action_info)';
			case 61307:
				return 'WeChat API: Invalid cardid in the promoted services section (action_info)';
			case 61308:
				return 'WeChat API: The base_info parameter does not exist';
			case 61309:
				return 'WeChat API: The detail_info parameter does not exist';
			case 61310:
				return 'WeChat API: The promoted services section (action_info) does not exist';
			case 61311:
				return 'WeChat API: Invalid media in the promoted services section (action_info)';
			case 61312:
				return 'WeChat API: Image size too large';
			case 61313:
				return 'WeChat API: Image content invalid or has not been encoded to Base64';
			case 61314:
				return 'WeChat API: Invalid ExtInfo';
			case 61316:
				return 'WeChat API: Barcode conflict: this barcode is already currently in use';
			case 61317:
				return 'WeChat API: Invalid ticket';
			case 61319:
				return 'WeChat API: Invalid merchant category ID';
			case 61320:
				return 'WeChat API: Merchant global information does not exist';
			case 61322:
				return 'WeChat API: Merchant does not have permission to use this product category';
			case 61323:
				return 'WeChat API: Merchant does not have permission to use this barcode';
			case 61324:
				return 'WeChat API: Exceeded the maximum number of service columns in the promoted services section';
			case 61334:
				return 'WeChat API: Product information does not exist';
			case 61337:
				return 'WeChat API: Product information already exists';
			case 61341:
				return 'WeChat API: Exceeded the maximum number of people on the white list';
			case 61342:
				return 'WeChat API: Keystandard and creation time do not match';
			case 61343:
				return 'WeChat API: Invalid Keystandard';
			case 61345:
				return 'WeChat API: Invalid code in the promoted services section (action_info)';
			case 61346:
				return 'WeChat API: Invalid store in the promoted services section (action_info)';
			case 61347:
				return 'WeChat API: Invalid media in the promoted services section (action_info)';
			case 61348:
				return 'WeChat API: Invalid text in the promoted services section (action_info)';
			case 61450:
				return 'WeChat API: System error';
			case 61451:
				return 'WeChat API: Invalid parameter';
			case 61452:
				return 'WeChat API: Invalid customer service agent account';
			case 61453:
				return 'WeChat API: Customer service agent account already exists.';
			case 61454:
				return 'WeChat API: Customer service agent account name is too long. Maximum length 10 Roman characters, not including the “@[WeChat ID]”)';
			case 61455:
				return 'WeChat API: Customer service agent account name contains an invalid character (only Roman characters and numbers are supported)';
			case 61456:
				return 'WeChat API: The maximum number of customer service agents have been created. (Accounts are limited to 100 agents)';
			case 61457:
				return 'WeChat API: Invalid customer service agent avatar image file type';
			case 61500:
				return 'WeChat API: Invalid date format';
			case 61501:
				return 'WeChat API: Date range error';
			case 63154:
				return 'WeChat API: Invalid product status';
			case 63155:
				return 'WeChat API: Invalid home page color';
			case 63156:
				return 'WeChat API: Invalid brand tag';
			case 63157:
				return 'WeChat API: Invalid recommended product setting, any recommended products must come from the same account, and product status must be available for purchase.';
			case 63158:
				return 'WeChat API: Exceeded the maximum number of products (100,000)';
			case 63159:
				return 'WeChat API: The suggested retail price for this product is empty. When you have not set a purchasing channel (including WeChat Shop or eCommerce link), the suggested retail price is required.';
			case 63160:
				return 'WeChat API: Invalid price. The retail_price and sale_price parameters only accept numerical values.';
			case 63161:
				return 'WeChat API: Exceeded the maxium number of modules in module_info section; the same module can only be set once';
			case 63162:
				return 'WeChat API: Invalid native_show setting in the security module';
			case 63163:
				return 'WeChat API: The anti_fake_url in the security module does not exist';
			case 63164:
				return 'WeChat API: Invalid module type in the module_info section';
			case 63166:
				return 'WeChat API: Products cannot be updated, withdrawn from sale, or deleted while under review';
			case 63167:
				return 'WeChat API: Products which have not been released for sale cannot be withdrawn from sale';
			case 63168:
				return 'WeChat API: Products that have not been approved cannot be withdrawn from sale';
			case 63169:
				return 'WeChat API: Product has already been released for sale, it cannot be re-released again.';
			case 63170:
				return 'WeChat API: Cannot set banner and media type at the same time in the promoted services section (action_info)';
			case 63171:
				return 'WeChat API: Only one card type is allowed in the promoted services section (action_info)';
			case 63172:
				return 'WeChat API: Only one user type is allowed in the promoted services section (action_info)';
			case 63173:
				return 'WeChat API: Only one text type in the promoted services section (action_info)';
			case 63174:
				return 'WeChat API: For the three types – link, card, user – in total you can only set three in the promoted services section (action_info)';
			case 63175:
				return 'WeChat API: The product details page must have at least one image detail and one product detail';
			case 65301:
				return 'WeChat API: The personalized menu corresponding to this Menu ID does not exist';
			case 65302:
				return 'WeChat API: No corresponding users';
			case 65303:
				return 'WeChat API: A default menu configuration is required before creating personalized menus.';
			case 65304:
				return 'WeChat API: MatchRule parameters are empty';
			case 65305:
				return 'WeChat API: Personalized menu count limitation reached.';
			case 65306:
				return 'WeChat API: This account does not support personalized menus';
			case 65307:
				return 'WeChat API: Personalized menu information empty';
			case 65308:
				return 'WeChat API: One or more buttons do not have an action associated with them.';
			case 65309:
				return 'WeChat API: Personalized menus have been turned off for this account.';
			case 65310:
				return 'WeChat API: If segmenting by province or city, the country parameter cannot be empty';
			case 65311:
				return 'WeChat API: If segmenting by city, the province parameter cannot be empty';
			case 65312:
				return 'WeChat API: Invalid country parameter';
			case 65313:
				return 'WeChat API: Invalid province parameter';
			case 65314:
				return 'WeChat API: Invalid city parameter';
			case 65316:
				return 'WeChat API: This account does not support direct links to this domain. The account can setup a maximum of 3 domains.';
			case 65317:
				return 'WeChat API: Invalid URL';
			case 9001001:
				return 'WeChat API: Invalid POST data parameter';
			case 9001002:
				return 'WeChat API: Remote service cannot be used';
			case 9001003:
				return 'WeChat API: Invalid Ticket';
			case 9001004:
				return 'WeChat API: Failed getting nearby user information';
			case 9001005:
				return 'WeChat API: Failed getting merchant information';
			case 9001006:
				return 'WeChat API: Failed getting user OpenID';
			case 9001007:
				return 'WeChat API: Upload file failed';
			case 9001008:
				return 'WeChat API: Invalid upload file type';
			case 9001009:
				return 'WeChat API: Invalid upload file size';
			case 9001010:
				return 'WeChat API: Upload failed';
			case 9001020:
				return 'WeChat API: Invalid account';
			case 9001021:
				return 'WeChat API: Less than 50% of your devices are currently active, you cannot add new devices at this time.';
			case 9001022:
				return 'WeChat API: Invalid number of device requests; the number must be greater than zero.';
			case 9001023:
				return 'WeChat API: This device ID is already under review';
			case 9001024:
				return 'WeChat API: Maximum number of device ID requests is fifty.';
			case 9001025:
				return 'WeChat API: Invalid device ID';
			case 9001026:
				return 'WeChat API: Invalid page ID';
			case 9001027:
				return 'WeChat API: Invalid page parameter';
			case 9001028:
				return 'WeChat API: You cannot delete more than ten Page IDs at a time';
			case 9001029:
				return 'WeChat API: This page is linked to a device, first de-link the device from the page before deleting the page.';
			case 9001030:
				return 'WeChat API: Maximum number of page ID requests is fifty.';
			case 9001031:
				return 'WeChat API: Invalid time period';
			case 9001032:
				return 'WeChat API: Error saving the parameter linking the device and page.';
			case 9001033:
				return 'WeChat API: Invalid Venue ID';
			case 9001034:
				return 'WeChat API: Device remarks information is too long';
			case 9001035:
				return 'WeChat API: Invalid device application parameter';
			case 9001036:
				return 'WeChat API: Invalid “begin” query start value';
			case 9001037:
				return 'WeChat API: A single device can be linked to a maximum thirty pages.';
			case 9001038:
				return 'WeChat API: Device limit exceeded';
			case 9001039:
				return 'WeChat API: Invalid contact name';
			case 9001040:
				return 'WeChat API: Invalid phone number';
			case 9001041:
				return 'WeChat API: Invalid email address';
			case 9001042:
				return 'WeChat API: Invalid Industry ID';
			case 9001043:
				return 'WeChat API: File URL does not have a valid certificate; files must be uploaded through the content management interface';
			case 9001044:
				return 'WeChat API: file is missing a valid certificate; files must be uploaded through the content management interface';
			case 9001045:
				return 'WeChat API: Application cannot exceed 500 characters.';
			case 9001046:
				return 'WeChat API: This Official Account is not verified.';
			case 9001047:
				return 'WeChat API: Invalid device application batch ID';
			case 9001048:
				return 'WeChat API: While the application is under review or has already been approved, you cannot resubmit a review request.';
			case 9001049:
				return 'WeChat API: Failed to get group metadata';
			case 9001050:
				return 'WeChat API: Group limit exceeded. Official Accounts can have maximum 100 groups.';
			case 9001051:
				return 'WeChat API: Device limit reached. The maximum number of devices belonging to a group is 10,000.';
			case 9001052:
				return 'WeChat API: You can only add a maximum of 1,000 devices at a time to a group.';
			case 9001053:
				return 'WeChat API: You can only delete a maximum of 1,000 devices at a time from a group.';
			case 9001054:
				return 'WeChat API: The group to be deleted still exists on a device.';
			case 9001055:
				return 'WeChat API: Group name is too long. Maxiumum 100 characters.';
			case 9001056:
				return 'WeChat API: The device list to be added or removed from this group contains one or more device IDs that do not belong to this group';
			case 9001057:
				return 'WeChat API: Group-related information operation failed.';
			case 9001058:
				return 'WeChat API: Group ID does not exist';
			case 9001059:
				return 'WeChat API: The logo_url on the page cannot be blank';
			case 9001060:
				return 'WeChat API: Failed to create red packet lottery';
			case 9001061:
				return 'WeChat API: Failed to get red packet lottery_id';
			case 9001062:
				return 'WeChat API: Failed to create template page';
			case 9001063:
				return 'WeChat API: The merchant Official Account ID providing the red packet is different than that of the merchant associated with the red packet';
			case 9001064:
				return 'WeChat API: Failed to get authorization for this red packet';
			case 9001065:
				return 'WeChat API: Authorization for this red packet is under review';
			case 9001066:
				return 'WeChat API: Authorization for this red packet was canceled';
			case 9001067:
				return 'WeChat API: No authorization for this red packet';
			case 9001068:
				return 'WeChat API: The red packet lottery time period is not within the red packet’s authorized time period';
			case 9001069:
				return 'WeChat API: Failed to start/stop red packet lottery';
			case 9001070:
				return 'WeChat API: Failed to get red packet lottery information';
			case 9001071:
				return 'WeChat API: Red packet ticket query failed';
			case 9001072:
				return 'WeChat API: Maximum number of red packet tickets reached.';
			case 9001073:
				return 'WeChat API: The sponsor_appid and the pre-order wxappid are inconsistent';
			case 9001074:
				return 'WeChat API: Failed to get red packet send ID';
			case 9001075:
				return 'WeChat API: The total number of red packets entered is greater than the number of red packets set when this promotion was created';
			case 9001076:
				return 'WeChat API: Failed to add red packet send ID';
			case 9001077:
				return 'WeChat API: Failed to decode red packet send ID';
			case 9001078:
				return 'WeChat API: Failed to get Official Account uin';
			case 9001079:
				return 'WeChat API: The appid calling the interface and the appid used to create the red packet are inconsistent';
			case 9001090:
				return 'WeChat API: All of the entered tickets are invalid. It could be that the tickets have already been used, are expired, or the ticket amount is not between 1-1000RMB.';
			case 9001091:
				return 'WeChat API: Promotion period has already expired.';
			default:
				return 'WeChat API: Unknown error';
		}
	}

}

class WechatPayException extends Exception {}
class WechatException extends Exception {}