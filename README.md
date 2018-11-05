# WP Weixin - WordPress WeChat integration

* [General description](#user-content-general-description)
	* [Important notes](#user-content-important-notes)
	* [Overview](#user-content-overview)
	* [Screenshots](#user-content-screenshots)
	* [Object Cache considerations](#user-content-object-cache-considerations)
* [Settings](#user-content-settings)
	* [Main Settings](#user-content-main-settings)
	* [WeChat Responder Settings](#user-content-wechat-responder-settings)
	* [WeChat Pay Settings](#user-content-wechat-pay-settings)
	* [Proxy Settings (beta)](#user-content-proxy-settings-beta)
	* [Miscellaneous Settings](#user-content-miscellaneous-settings)
* [Functions](#user-content-functions)
* [Hooks - actions & filters](#user-content-hooks---actions--filters)
	* [Actions](#user-content-actions)
	* [Filters](#user-content-filters)
* [Templates](#user-content-templates)
* [Javascript](#user-content-javascript)

## General Description

WP Weixin enables integration between WordPress and WeChat. It is fully functional as a standalone plugin, and acts as a core for [Woo WeChatPay](https://anyape.com/woo-wechatpay.html) payment gateway for WooCommerce and [WP Weixin Pay](https://anyape.com/wp-weixin-pay.html) extension.

### Important notes

* Although the plugin does provide really useful functionalities out of the box, such as WeChat authentication and Official Account menu integration, it really shines when used by developers to extend its functionalities (mainly through the pre-initialised JS SDK, the WeChat Responder, and various actions and filters).
* The plugin does not support multisite at this stage, and is to be used with a China Mainland WeChat Official Account (Subscription or Service - Service is required if used with companion plugins dealing with payments).

### Overview

This plugin adds the following major features to WordPress:

* **WP Weixin settings page:** to configure the plugin with an Official Account.
* **WeChat Share:** Share posts and pages on Moments or Send to chat, in a pretty way. Triggers javascript events for developers on success and failure.
* **WeChat JS_SDK:** the `wx` global variable is pre-configured with a signed package to leverage the javascript SDK of WeChat in WordPress themes more easily. 
* **WP Weixin QR code generator:** to create custom codes.
* **WeChat Authentication:** to automatically create and authenticate users in WordPress on WeChat browser, or allow users to scan a QR code with WeChat when using other browsers (social login).
* **Force WeChat mobile:** to prevent users from browsing the website outside of WeChat. If accessed with an other browser, the page displays a QR code.
* **WeChat Responder:** acts as an API for developers to receive and respond to calls made by WeChat.
* **Force following the Official Account:** to harvest WeChat followers, forcing users to follow the Official Account before accessing the content.
* **Welcome message:** sends a welcome message in WeChat when a user follows the Official Account ; allows to do so with WordPress when the WeChat Responder is enabled.
* **Menu integration:** allows to set the Official Account menus in WordPress when the WeChat Responder is enabled.
* **WordPress Users screen override:** to display WeChat names and WeChat avatars if they exist, instead of the default values in the user screen.

Developers are encouraged to build plugins and themes integrated with WeChat with WP Weixin as a core, leveraging its publicly available functions, actions and filters.  
### Screenshots

<img src="https://ps.w.org/wp-weixin/assets/screenshot-2.png" alt="Welcome default message" width="30%"> <img src="https://ps.w.org/wp-weixin/assets/screenshot-3.png" alt="Force WeChat mobile" width="65%"> <img src="https://ps.w.org/wp-weixin/assets/screenshot-1.png" alt="WP Weixin Settings" width="100%"> <img src="https://ps.w.org/wp-weixin/assets/screenshot-4.png" alt="WP Weixin Responder settings" width="100%"> <img src="https://ps.w.org/wp-weixin/assets/screenshot-5.png" alt="WP Weixin Miscellaneous settings" width="100%"> <img src="https://ps.w.org/wp-weixin/assets/screenshot-7.png" alt="WP Weixin QR code generator" width="42%"> <img src="https://ps.w.org/wp-weixin/assets/screenshot-6.png" alt="WP Weixin Users screen" width="55%"> 

### Object Cache considerations

This plugin uses WordPress `WP_Object_Cache` to optimise the number of database queries, ensuring only the proper amount is fired on each pageload. Because the `WP_Object_Cache` object can be affected by third-party plugins, it is required that such plugins implement the `wp_cache_add_non_persistent_groups` function to avoid side effects.  

See below examples of popular cache plugins compatible with WP Weixin:

* [W3 Total Cache](http://wordpress.org/extend/plugins/w3-total-cache/)
* [APC Object Cache](https://github.com/l3rady/WordPress-APC-Object-Cache) / [APCu Object Cache](https://github.com/l3rady/WordPress-APCu-Object-Cache)
* [Redis Object Cache](https://github.com/ericmann/Redis-Object-Cache) 

## Settings

The following settings can be accessed on the WP Weixin settings page.

### Main Settings

Required settings below are the **minimal configuration** necessary for the plugin to have any effect.  

Name                                | Required | Type      | Description                                                                                                                  
----------------------------------- |:--------:|:---------:| ------------------------------------------------------------------------------------------------------------------------------
Enable                              | Yes      | checkbox  | Enable WP Weixin - requires a valid configuration.                                                                            
WeChat App ID                       | Yes      | text      | The AppId in the backend at `https://mp.weixin.qq.com/` under Development > Basic configuration.                              
WeChat App Secret                   | Yes      | text      | The AppSecret in the backend at `https://mp.weixin.qq.com/` under Development > Basic configuration.                          
WeChat OA Name                      | No       | text      | The name of the Official Account (recommended to enter the actual name).                                                      
WeChat OA Logo URL                  | No       | text      | A URL to the logo of the Official Account - (recommended enter the URL of a picture of the actual logo).                      
Enable WeChat mobile authentication | No       | checkbox  | If enabled, users will be authenticated with their WeChat account in WordPress (if not, a session cookie `wx_openId` is set). 
Force WeChat mobile                 | No       | checkbox  | Make the website accessible only through the WeChat browser.<br>If accessed with another browser, the page displays a QR code.
Force follow (any page)             | No       | checkbox  | Require the user to follow the Official Account before accessing the site with the WeChat browser.                            

### WeChat Responder Settings

Name                      | Type      | Description                                                                                                                                                                                                                                                                            
------------------------- |:---------:| ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
Use WeChat Responder      | checkbox  | Allow the website to receive messages from WeChat and respond to them. Server configuration must be enabled and configured in `https://mp.weixin.qq.com/` under Development > Basic configuration. Required if using "Force follow" option in the Main Settings or WeChat Pay settings.                                                                                                                                                                                                                                                                                                                 
WeChat Token              | text      | The Token in the backend at `https://mp.weixin.qq.com/` under Development > Basic configuration.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         
Encode messages           | checkbox  | Encode the communication between the website and the WeChat API (recommended).                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           
WeChat AES Key            | text      | The EncodingAESKey in the backend at `https://mp.weixin.qq.com/` under<br/> Development > Basic configuration.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           
Send welcome message      | checkbox  | Send a welcome message when a user follows the Official Account.<br/>The following filters can be used to change the default values of the message:<ul><li>`apply_filters( 'wp_weixin_follower_welcome_title', string $title, mixed $before_subscription );`</li><li>`apply_filters( 'wp_weixin_follower_welcome_description', string $description, mixed $before_subscription );`</li><li>`apply_filters( 'wp_weixin_follower_welcome_url', string $url, mixed $before_subscription );`</li><li>`apply_filters( 'wp_weixin_follower_welcome_pic_url', string $pic_url, mixed $before_subscription );`</li></ul>
Welcome message image URL | text      | A URL to the image used for the welcome message sent after a user follows the Official Account (external or from the Media Library).<br>Default image is in `/wp-weixin/images/default-welcome.png`.                                                                                                                                                                                                                                                                                                                                                                                                    

### WeChat Pay Settings

These settings are only available if WP Weixin Pay and/or Woo WeChatPay are installed and activated.

Name                                | Type      | Description                                                                                                                                                                                                                     | Requirement                             
----------------------------------- |:---------:|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | :--------------------------------------:
Use merchant platform               | checkbox  | Allow users to send money to the Service Account with WeChat - an account at `https://pay.weixin.qq.com/` is necessary. This setting is not configurable (forced to checked and hidden) if Woo WeChatPay plugin is activated.   | WP Weixin Pay                           
Custom amount transfer 			    | checkbox  | Allow users to do custom amount transfers and admins to create payment QR Codes.                                          																									      | WP Weixin Pay                           
Force follow (account and checkout) | checkbox  | Require the user to follow the Official Account before accessing the checkout and account pages with the WeChat browser. This setting is only available if WooCommerce is activated.                                            | Woo WeChatPay                           
WeChat Merchant App ID              | text      | The AppID in the backend at `https://pay.weixin.qq.com/` - can be different from the WeChat App ID as the WeChat Pay account may be linked to a different AppID. Leave empty to use the WeChat App ID.                          | WP Weixin Pay<br>**or**<br>Woo WeChatPay  
WeChat Merchant ID                  | text      | The Merchant ID in the backend at `https://pay.weixin.qq.com/index.php/extend/pay_setting`.                                                                                                                                     | WP Weixin Pay<br>**or**<br>Woo WeChatPay  
WeChat Merchant Key                 | text      | The Merchant Key in the backend at `https://pay.weixin.qq.com/`.                                                                                                                                                                | WP Weixin Pay<br>**or**<br>Woo WeChatPay  

In addition to these settings, the plugin provides onscreen help for what values to input for the different URLs in the merchant account's API configuration screen.

### Proxy Settings (beta)

Name        | Type     | Description                              
----------- |:--------:| -----------------------------------------
Use a proxy | checkbox | Enable proxy.                            
Proxy Host  | text     | IP address or URI of the proxy host.     
Proxy Port  | text     | Port to use to connect to the proxy host.

Depending on your server configuration, a proxy may be needed if WordPress is behind a firewall or within a company network.

### Miscellaneous Settings

Name                                             | Type     | Description                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    
------------------------------------------------ |:--------:| ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
Show WeChat name and pictures in Users list page | checkbox | Override the display of the WordPress account names and avatars.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               
Official Account menu language awareness         | checkbox | Customise the menu of the Official Account depending on user's language. By default, the language of the menu corresponding to the website's default language is used.<br/>This setting is only available if WPML is activated.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                
Use custom persistence for access_token          | checkbox | Use a custom persistence method for the Official Account access_token and its expiry timestamp.<br/>**Warning** - requires the implementation of:<ul><li>`add_filter( 'wp_weixin_get_access_info', $access_info, 10, 0 );`</li><li>`add_action( 'wp_weixin_save_access_info', $access_info, 10, 1 );`</li></ul>The parameter `$access_info` is an array with the keys `token` and `expiry`.<br/>Add the hooks above in a `plugins_loaded` action with a priority of `5` or less.<br/>Useful to avoid a race condition if the access_token information needs to be shared between multiple platforms.<br/>When unchecked, access_token & expiry timestamp are stored in the WordPress options table in the database.

## Functions
The functions listed below are made publicly available by the plugin for theme and plugin developers. Although the main classes can theoretically be instanciated without side effect if the `$hook_init` parameter is set to `false`, it is recommended to use only the following functions as there is no guarantee future updates won't introduce changes of behaviors.

```php
wp_weixin_is_wechat();
```  

**Description**  
Wether the visitor is using the WeChat browser.  

**Return value**  
> (bool) true if using the WeChat browser, false otherwise

```php
wp_weixin_get_user_by_openid( string $openid );
```  

**Description**  
Get a WordPress user by WeChat openid.  

**Parameters**  
> (string) A WeChat openid.

**Return value**  
> (mixed) a WP_User if a WordPress user with a corresponding WeChat openid exists, false otherwise

```php
wp_weixin_get_user_by_unionid( string $openid );
```  

**Description**  
Get a WordPress user by WeChat unionid.  

**Parameters**  
> (string) A WeChat unionid.

**Return value**  
> (mixed) a WP_User if a WordPress user with a corresponding WeChat unionid exists, false otherwise

## Hooks - actions & filters

WP Weixin gives developers the possibilty to customise its behavior with a series of custom actions and filters. 

### Actions

```php
do_action( 'wp_weixin_responder', array $request_data );
```

**Description**  
Fired after receiving a request from WeChat.  

**Parameters**  
> (array) The data sent in the request from WeChat
___

```php
do_action( 'wp_weixin_save_access_info', array $access_info );

```

**Description**  
Fired after renewing the Official Account access_token if custom persistence is used. Used to save the access information - particularly useful to avoid a race condition if the access_token needs to be shared between multiple platforms.

**Parameters**  
$access_info
> (array) The access information in an associative array. Keys are `token` and `expiry`.
___

### Filters
```php
apply_filters( 'wp_weixin_browser_page_qr_src', string $src );
```

**Description**  
Filter the source of the QR code to show on other browsers for a page only accessible through WeChat browser.  

**Parameters**  
$src
> (string) The source of the QR code to show on other browsers - default empty

**Hooked**
WP_Weixin_Auth::get_browser_page_qr_src()
___

```php
apply_filters( 'wp_weixin_subscribe_src', string $src );
```

**Description**  
Filter the source of the QR code used to follow the Official Account.  

**Parameters**  
$src
> (string) The source of the QR code - default empty

**Hooked**
WP_Weixin_Auth::get_subscribe_src()
___

```php
apply_filters( 'wp_weixin_follower_notice_title', string $title );
```

**Description**  
Filter the title of the page displaying the QR code to follow the Official Account.  

**Parameters**  
> (string) The title of the page - default "Follow Us!"
___

```php
apply_filters( 'wp_weixin_follower_notice', string $notice );
```

**Description**  
Filter the message displayed on the page displaying the QR code to follow the Official Account.  

**Parameters**  
> (string) The displayed message - default "Please scan this QR Code to follow us before accessing this content."
___

```php
apply_filters( 'wp_weixin_auth_needed', bool $needs_auth );
```

**Description**  
Wether the page needs the user to be authenticated using WeChat. When "Enable WeChat mobile authentication" is checked in the settings, pages need authentication by default, unless they are whitelisted using this filter. By default, all the admin pages are whitelisted and accessible outside WeChat.  

**Parameters**  
$needs_auth
> (bool) true if authentication is needed to visit the page, false otherwise
___

```php
apply_filters( 'wp_weixin_debug', bool $debug );
```

**Description**  
Filter wether to activate debug mode (php error logs and javascript console message).  

**Parameters**  
$debug
> (bool) true if debug mode is activated, false otherwise - default false
___

```php
apply_filters( 'wp_weixin_follower_welcome_title', string $title, mixed $before_subscription );
```

**Description**  
Filter the title of the message the user receives when following the Official Account.  

**Parameters**  
$title
> (string) The title  

$before_subscription
> (mixed) If numeric, the WP_Post ID of the last page the user was visiting ; if string, the URL of the last page the user was visiting - default site_url()
___

```php
apply_filters( 'wp_weixin_follower_welcome_description', string $description, mixed $before_subscription );
```

**Description**  
Filter the description of the message the user receives when following the Official Account.  

**Parameters**  
$description
> (string) The description  

$before_subscription
> (mixed) If numeric, the WP_Post ID of the last page the user was visiting ; if string, the URL of the last page the user was visiting - default site_url()
___

```php
apply_filters( 'wp_weixin_follower_welcome_url', string $url, mixed $before_subscription );
```

**Description**  
Filter the URL of the message the user receives when following the Official Account.  

**Parameters**  
$url
> (string) The URL  

$before_subscription
> (mixed) If numeric, the WP_Post ID of the last page the user was visiting ; if string, the URL of the last page the user was visiting - default site_url()
___

```php
apply_filters( 'wp_weixin_follower_welcome_pic_url', string $pic_url, mixed $before_subscription );
```

**Description**  
Filter the URL of the picture displayed on the message the user receives when following the Official Account.  

**Parameters**  
$pic_url
> (string) The URL of the picture  

$before_subscription
> (mixed) If numeric, the WP_Post ID of the last page the user was visiting ; if string, the URL of the last page the user was visiting - default site_url()
___

```php
apply_filters( 'wp_weixin_get_access_info', array $access_info );
```

Filter the access_token and expiry when requesting the WeChat object if custom persistence is used - particularly useful to avoid a race condition if the access_token needs to be shared between multiple platforms.

**Parameters**  
$access_info
> (array) The access information in an associative array. Keys are `token` and `expiry`.
___

```php
apply_filters( 'wp_weixin_jsapi_urls', array $jsapi_urls );
```

As an effect only if [Woo WeChatPay](https://anyape.com/woo-wechatpay.html) payment gateway for WooCommerce and/or [WP Weixin Pay](https://anyape.com/wp-weixin-pay.html) extension is activated.  
Filter the URLs necessary to register on the WeChat merchant account's API configuration screen - particularly useful if another plugin implements some sort of custom checkout page with a URL not registered in WooCommerce.

**Parameters**  
$jsapi_urls
> (array) The URLs to register on the WeChat merchant account's API configuration screen.
___

```php
apply_filters( 'wp_weixin_auth_redirect', $redirect, $auth, $has_error );
```

Filter the url to redirect to when QR code authentication in classic browsers is performed.

**Parameters**  
$redirect
> (mixed) The url to redirect to when authentication is performed, or false if no redirect. Default is `home_url( '/' )` in case of successful authentication.  

$auth
> (bool) Wether the authentication was a performed - `true` if successful, `false` if an error occurred.  

$has_error
> (bool) Wether an error occurred.  
___

```php
apply_filters( 'wp_weixin_auth_heartbeat_frequency', $frequency );
```

Filter the frequency of the checks when performing QR code authentication in classic browsers.

**Parameters**  
$frequency
> (int) The frequency in milliseconds. Default `1000`.  
___

```php
apply_filters( 'wp_weixin_auth_qr_cleanup_frequency', $frequency );
```

Filter the frequency to clean up expired authentication QR code data.

**Parameters**  
$frequency
> (string) The frequency. Default `'hourly'`.  
___

```php
apply_filters( 'wp_weixin_auth_qr_lifetime', $lifetime );
```

Filter the lifetime of an authentication QR code.

**Parameters**  
$lifetime
> (int) The lifetime in seconds. Default `600`.  
___

```php
apply_filters( 'wp_weixin_ms_auto_add_to_blog', $auto_add_to_blog, $blog_id, $user_id );
```

Filter wether to automatically add the user to the visited blog on the network when authenticated with WeChat.

**Parameters**  
$auto_add_to_blog
> (bool) Wether to automatically add the user to the visited blog on the network when authenticated with WeChat. Default `true`.  

$blog_id
> (int) The ID of the visited blog.  

$user_id
> (int) The ID of the user visiting the blog.  
___

```php
apply_filters( 'wp_weixin_ms_auth_blog_id', $auth_blog_id );
```

Filter the blog id used for authentication - by default, it is assumend the domain name of the default blog is registered in WeChat backend.

**Parameters**  
$auth_blog_id
> (bool) The id of the blog to use when doing WeChat authentication. Default `0`.  
___

## Templates

The following plugin files are included using `locate_template()` function of WordPress. This means they can be overloaded in the active WordPress theme if a file with the same name exists at the root of the theme.  
The style applied to these templates is in `wp-weixin/css/main.css`.
___

```
wp-weixin-subscribe.php
```  

**Description**  
The template of the page displaying the QR code to follow the Official Account.  

**Variables**  
None. It uses the `wp_weixin_subscribe_src` filter to get the source of the QR code image.

___

```
wp-weixin-browser-qr.php
```  

**Description**  
The template of the page displaying the QR code when the website is accessible only through the WeChat browser.  

**Variables**  
None. It uses the `wp_weixin_browser_page_qr_src` filter to get the source of the QR code image.

___

```
wp-weixin-auth-form-link.php
```  

**Description**  
The template of the link displayed below the login, registration and forgot password forms.  

**Variables**  
None.

___

```
wp-weixin-auth-page.php
```  

**Description**  
The template of the WeChat screen displayed for QR code authentication in classic browsers.  

**Variables**  
None.

___

```
wp-weixin-mobile-auth-check.php
```  

**Description**  
The template of the WeChat mobile browser screen displayed when authenticating via QR code authentication in classic browsers.  

**Variables**  
$auth_qr_data
(array) Data related to the authentication. Value type and keys: (bool) `auth`, (int) `user_id`, (array) `error`, (bool|string) `redirect`. The `redirect` value is not actually used for redirection by default on mobile (used after authentication on desktop).
___


## Javascript

The global variable `wx` is already properly signed and initialised with the complete `jsApiList`.  
To use it properly, developers must include their scripts with a priority of `6` or more and `wp-weixin-main-script` as a dependency.  

In addition, the following listeners may be subscribed to:
___
```Javascript
window.wpWeixinShareTimelineSuccessListener( callback );
```

Subscribing to this listener will execute the `callback` function after sharing the post on WeChat Moments succeeded.  

**Parameters passed to the callback**  
shareInfo
> (object) The share information sent to the WeChat JS_SDK. Attributes are `title`, `desc`, `link`, `imgUrl`.  
___
```Javascript
window.wpWeixinShareTimelineFailureListener( callback );
```

Subscribing to this listener will execute the `callback` function after sharing the post on WeChat Moments failed.  

**Parameters passed to the callback**  
shareInfo
> (object) The share information sent to the WeChat JS_SDK. Attributes are `title`, `desc`, `link`, `imgUrl`.  
___
```Javascript
window.wpWeixinShareAppMessageSuccessListener( callback );`
```

Subscribing to this listener will execute the `callback` function after sharing the post with WeChat "Send to chat" succeeded.  

**Parameters passed to the callback**  
shareInfo
> (object) The share information sent to the WeChat JS_SDK. Attributes are `title`, `desc`, `link`, `imgUrl`.  
___
```Javascript
window.wpWeixinShareAppMessageFailureListener( callback );
```

Subscribing to this listener will execute the `callback` function after sharing the post with WeChat "Send to chat" failed.  

**Parameters passed to the callback**  
shareInfo
> (object) The share information sent to the WeChat JS_SDK. Attributes are `title`, `desc`, `link`, `imgUrl`.  