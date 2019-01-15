# WP Weixin - WordPress WeChat integration

* [General Description](#user-content-general-description)
	* [Companion Plugins](#user-content-companion-plugins)
	* [Important Notes](#user-content-important-notes)
	* [Registering a domain in the Official Account's backend](#user-content-registering-a-domain-in-the-official-accounts-backend)
	* [Overview](#user-content-overview)
	* [Object Cache considerations](#user-content-object-cache-considerations)
* [Settings](#user-content-settings)
	* [Main Settings](#user-content-main-settings)
	* [Multisite Settings](#user-content-multisite-settings)
	* [WeChat Responder Settings](#user-content-wechat-responder-settings)
	* [WeChat Pay Settings](#user-content-wechat-pay-settings)
	* [Proxy Settings (beta)](#user-content-proxy-settings-beta)
	* [Miscellaneous Settings](#user-content-miscellaneous-settings)
* [Multisite](#user-content-multisite)
* [WeChat SDK](#user-content-wechat-sdk)
* [Functions](#user-content-functions)
* [Hooks - actions & filters](#user-content-hooks---actions--filters)
	* [Actions](#user-content-actions)
	* [Filters](#user-content-filters)
* [Templates](#user-content-templates)
* [JavaScript](#user-content-javascript)

## General Description

WP Weixin provides integration between WordPress and WeChat. Register or authenticate users automatically in WeChat browser, use WeChat to create an account or authenticate on computers by scanning a QR code with WeChat, share posts in WeChat Moments and conversations or extend the plugin for more features!

### Companion Plugins

* [Woo WeChatPay](https://wordpress.org/plugins/woo-wechatpay): a payment gateway for WooCommerce.
* [WP Weixin Pay](https://wordpress.org/plugins/wp-weixin-pay): an extension to enable money transfers to an Official Account.

Developers are encouraged to build plugins and themes integrated with WeChat with WP Weixin as a core, leveraging its publicly available [functions](#user-content-functions), [actions](#user-content-actions) and [filters](#user-content-filters), or directly [make use of the provided SDK](#user-content-wechat-sdk).  

*If you wish to see your plugin added to this list, please contact the author.*

### Important Notes

* Requires a [China Mainland WeChat Official Account](https://mp.weixin.qq.com) (Subscription or Service - Service is required if used with companion plugins dealing with payments).
* A domain used by WordPress **must** be registered in an Official Account's backend
* The plugin itself does not require programming knowledge, and provides really useful functionalities out of the box. Where it really shines though is when used by developers to extend its functionalities (mainly through the pre-initialised JS SDK, the WeChat Responder, and various provided [functions](#user-content-functions), [actions](#user-content-actions) and [filters](#user-content-filters)).

### Registering a domain in the Official Account's backend

To register a domain and authorize communication between it and the WeChat APIs (frontend JS and server side), the domain **must** be linked with an ICP license first. Then, on [https://mp.weixin.qq.com](https://mp.weixin.qq.com):

* connect to the Official Account's backend
* click the "Interface Privilege" menu item
* in the list, search for "Web Page Authorization" and click the "Modify" link
* add the domain in both "JS interface security domain name" and "Webpage authorization domain name" by clicking the "Set-up" link for both sections (without the protocol `http` or `https`) - making sure to include the `MP_verify_[some_code].txt` files to the root of the website corresponding to the domain registered as instructed, accessible publicly.

### Overview

This plugin adds the following major features to WordPress:

* **WP Weixin settings page:** configure the plugin with an Official Account (or as many as you want in multisite) in English or Chinese out of the box, with instructions for each option.
* **WeChat Authentication:** automatically create and authenticate users in WordPress in the WeChat browser, or allow users to scan a QR code with WeChat when using classic browsers (social login).
* **WeChat Account Binding:** let users bind/unbind their existing WordPress account with their WeChat account. Integrated with WooCommerce and Ultimate Member account pages, and may be integrated with any membership/account/profile plugin easily.
* **WeChat Share:** Share posts and pages on Moments or Send to chat, in a pretty way. Triggers JavaScript events for developers on success and failure.
* **Force WeChat mobile:** to prevent users from browsing the website outside of the WeChat browser. If accessed with a classic browser, the page displays a QR code.
* **Force following the Official Account:** to harvest WeChat followers, forcing users to follow the Official Account before accessing the content.
* **WordPress Users screen override:** to display WeChat names and WeChat avatars if they exist, instead of the default values in the user screen.
* **WP Weixin QR code generator:** to create custom codes.
* **Menu integration:** allows to set the Official Account menus in WordPress when the WeChat Responder is enabled.
* **Welcome message:** sends a welcome message in WeChat when a user follows the Official Account ; allows to do so with WordPress when the WeChat Responder is enabled.
* **Developers - WeChat Responder:** for developers to receive and respond to calls made by WeChat's API.
* **Developers - WeChat JS_SDK:** the `wx` JavaScript global variable is pre-configured with a signed package to leverage the JavaScript SDK of WeChat in WordPress themes more easily.  

Compatible with [WooCommerce](https://wordpress.org/plugins/woocommerce/), [WooCommerce Multilingual](https://wordpress.org/plugins/woocommerce-multilingual/), [WPML](http://wpml.org/), [Ultimate Member](https://wordpress.org/plugins/ultimate-member/), [WordPress Multisite](https://codex.wordpress.org/Create_A_Network), and [many caching plugins](#user-content-object-cache-considerations).

### Object Cache considerations

This plugin uses WordPress `WP_Object_Cache` to optimise the number of database queries, ensuring only the proper amount is fired on each page load. Because the `WP_Object_Cache` object can be affected by third-party plugins, it is required that such plugins implement the `wp_cache_add_non_persistent_groups` function to avoid side effects.  

See below examples of popular cache plugins compatible with WP Weixin:

* [W3 Total Cache](http://wordpress.org/extend/plugins/w3-total-cache/)
* [APC Object Cache](https://github.com/l3rady/WordPress-APC-Object-Cache) / [APCu Object Cache](https://github.com/l3rady/WordPress-APCu-Object-Cache)
* [Redis Object Cache](https://github.com/ericmann/Redis-Object-Cache) 

## Settings

The following settings are available on the WP Weixin settings page.

### Main Settings

Required settings below are the **minimal configuration** necessary for the plugin to have any effect.  

Name                                | Required | Type      | Description                                                                                                                  
----------------------------------- |:--------:|:---------:| -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
Enable                              | **Yes**  | checkbox  | Enable WP Weixin - requires a valid configuration.                                                                                                                                         
WeChat App ID                       | **Yes**  | text      | The AppId in the backend at `https://mp.weixin.qq.com/` under Development > Basic configuration.                                                                                           
WeChat App Secret                   | **Yes**  | text      | The AppSecret in the backend at `https://mp.weixin.qq.com/` under Development > Basic configuration.                                                                                       
WeChat OA Name                      | No       | text      | The name of the Official Account (recommended to enter the actual name).                                                                                                                   
WeChat OA Logo URL                  | No       | text      | A URL to the logo of the Official Account - (recommended enter the URL of a picture of the actual logo).                                                                                   
Enable WeChat authentication        | No       | checkbox  | If enabled, users will be authenticated with their WeChat account in WordPress when visiting the site with the WeChat browser(if not, a session cookie with key `'wx_openId-' . apply_filters( 'wp_weixin_ms_auth_blog_id', 1 )` is set).
Force WeChat mobile                 | No       | checkbox  | Make the website accessible only through the WeChat browser.<br>If accessed with another browser, the page displays a QR code.                                                             
Force follow (any page)             | No       | checkbox  | Require the user to follow the Official Account before accessing the site with the WeChat browser.                                                                                         

### Multisite Settings

These settings are hidden by default and only available when:
- WordPress Multisite is installed
- The current user has the `manage_network_options` capability

They affect the entire multisite network.  

Name                             | Type   | Description                                                                                                                  
-------------------------------- |:------:| ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
Force a blog for authentication  | select | Replaced by an info text if a callback is hooked to [wp_weixin_ms_auth_blog_id](#user-content-wp_weixin_ms_auth_blog_id).<br/>Blog to use as proxy when authenticating users.                                                                                                                                                                                                                                                                                                                                                          
Force a blog for WeChat payments | select | Replaced by an info text if a callback is hooked to [wp_weixin_ms_pay_blog_id](#user-content-wp_weixin_ms_pay_blog_id).<br/>Remains hidden if "Use merchant platform" option is not checked (needs WeChat payment integrated in a companion plugin).<br/>Blog to use as proxy when processing payments. If default, the JSAPI Payment Authorization URLs must be entered for all the blogs of the network doing payments, and the QR Payment callback URL must be capable of handling all the notifications coming from WeChat Pay API.

### WeChat Responder Settings

Name                      | Type      | Description                                                                                                                                                                                                                                                                            
------------------------- |:---------:| ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
Use WeChat Responder      | checkbox  | Allow the website to receive messages from WeChat and respond to them. Server configuration must be enabled and configured in `https://mp.weixin.qq.com/` under Development > Basic configuration. Required if using "Force follow" option in the Main Settings or WeChat Pay settings.                                                                                                                                                                                                                                                                                                                 
WeChat Token              | text      | The Token in the backend at `https://mp.weixin.qq.com/` under Development > Basic configuration.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         
Encode messages           | checkbox  | Encode the communication between the website and the WeChat API (recommended).                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           
WeChat AES Key            | text      | The EncodingAESKey in the backend at `https://mp.weixin.qq.com/` under<br/> Development > Basic configuration.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           
Send welcome message      | checkbox  | Send a welcome message when a user follows the Official Account.<br/>The following filters can be used to change the default values of the message:<ul><li>[wp_weixin_follower_welcome_title](#user-content-wp_weixin_follower_welcome_title)</li><li>[wp_weixin_follower_welcome_description](#user-content-wp_weixin_follower_welcome_description)</li><li>[wp_weixin_follower_welcome_url](#user-content-wp_weixin_follower_welcome_url)</li><li>[wp_weixin_follower_welcome_pic_url](#user-content-wp_weixin_follower_welcome_pic_url)</li></ul>
Welcome message image URL | text      | A URL to the image used for the welcome message sent after a user follows the Official Account (external or from the Media Library).<br>Default image is in `/wp-weixin/images/default-welcome.png`.                                                                                                                                                                                                                                                                                                                                                                                                    

### WeChat Pay Settings

These settings are hidden by default and only available if a WeChat Pay integration plugin such as [WP Weixin Pay](https://wordpress.org/plugins/wp-weixin-pay) or [Woo WeChatPay](https://wordpress.org/plugins/woo-wechatpay) is installed and activated (this behavior may be altered using the [wp_weixin_show_settings_section](#user-content-wp_weixin_show_settings_section) filter).

Name                                | Type      | Description                                                                                                                                                                                                                                                                                                                                                                     
----------------------------------- |:---------:|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
Use merchant platform               | checkbox  | Allow users to send money to the Service Account with WeChat - an account at `https://pay.weixin.qq.com/` is necessary. This setting is not configurable (forced to checked and hidden) if Woo WeChatPay plugin is activated.                                                                                                                                                   
WeChat Merchant App ID              | text      | The AppID in the backend at `https://pay.weixin.qq.com/` - can be different from the WeChat App ID as the WeChat Pay account may be linked to a different AppID. Leave empty to use the WeChat App ID.                                                                                                                                                                          
WeChat Merchant ID                  | text      | The Merchant ID in the backend at `https://pay.weixin.qq.com/index.php/extend/pay_setting`.                                                                                                                                                                                                                                                                                     
PEM certificate prefix              | text      | The prefix of the certificate files downloaded from `https://pay.weixin.qq.com/index.php/core/cert/api_cert`.<br/>Certificate files default prefix is `apiclient` (for `apiclient_cert.pem` and `apiclient_key.pem` files).<br/>Required notably to handle refunds through WeChat Pay.                                                                                          
PEM certificate files path          | text      | The absolute path to the containing folder of the certificate files downloaded from `https://pay.weixin.qq.com/index.php/core/cert/api_cert` on the current file system.<br/>Example: `/home/user/wechat-certificates`.<br/>Must have read permissions for the user running PHP, and located outside of the web root.<br/>Required notably to handle refunds through WeChat Pay.

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
------------------------------------------------ |:--------:| --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
Show WeChat name and picture in Users list page | checkbox | Override the display of the WordPress account names and avatars.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          
Show WeChat public info	                         | checkbox | Show the WeChat public information on user profile pages. Integrates with WooCommece and Ultimate Member.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 
Show WeChat Account binding link                 | checkbox | Show a link to bind or unbind a WordPress account with a WeChat account on user profile pages. Integrates with WooCommece and Ultimate Member.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            
Show WeChat Account authentication link	         | checkbox | Show a link to authenticate via QR code using a WeChat account on the WordPress login form.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               
Official Account menu language awareness         | checkbox | Customise the menu of the Official Account depending on user's language. By default, the language of the menu corresponding to the website's default language is used.<br/>This setting is only available if WPML is activated.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           
Use custom persistence for access_token          | checkbox | Use a custom persistence method for the Official Account access_token and its expiry timestamp.<br/>**Warning** - requires the implementation of:<ul><li>[wp_weixin_get_access_info](#user-content-wp_weixin_get_access_info)</li><li>[wp_weixin_save_access_info](#user-content-wp_weixin_save_access_info)</li></ul>The parameter `$access_info` is an array with the keys `token` and `expiry`.<br/>Add the hooks above in a `plugins_loaded` action with a priority of `5` or less.<br/>Useful to avoid a race condition if the access_token information needs to be shared between multiple platforms.<br/>When unchecked, access_token & expiry timestamp are stored in the WordPress options table in the database.

## Multisite

WP Weixin supports multisite installs of WordPress, wether using domain/subdomains or subdirectories. WP Weixin needs to be configured with the same settings and enabled on all the blogs where authentication is needed for a given Official Account.  

With WeChat mobile authentication enabled, users visiting one of the blogs are automatically registered to the network, and added to the visited blog with the blog's default user role.
Users are also automatically added to other blogs of the network upon visit when already registered on one of the blogs. This behavior can be changed with the [wp_weixin_ms_auto_add_to_blog](#user-content-wp_weixin_ms_auto_add_to_blog) filter, for example if some of the blogs should not accept pre-authenticated WeChat users.  

When using a domain/subdomain-based network of blogs, the main blog's domain/subdomain is used for cross-domain authentication. The behavior can be changed with the setting "Force a blog for authentication" in the Multisite Settings section of the plugin's page.

WeChat Pay integrated plugins can also support domain/subdomain-based network installs of WordPress Multisite by leveraging the functions, actions and filters provided by WP Weixin. The blog used for payment can be forced with the "Force a blog for WeChat payments"  in the Multisite Settings section of the plugin's page.  
[WP Weixin Pay](https://wordpress.org/plugins/wp-weixin-pay) and [Woo WeChatPay](https://wordpress.org/plugins/woo-wechatpay) are examples of plugins integrated with WeChat Pay, working no matter the type of Multisite installation (subdirectory or domain/subdomain).

Unlike some plugins (commercial, obfuscated, and with dubious security standards), WP Weixin does not and will not rely on a crossdomain script dumped at the root of WordPress, but prefers to leverage the WordPress actions and filters. 

It is possible to use the plugin with multiple Official Accounts on the same network, as long as the developer leverages [wp_weixin_ms_auth_blog_id](#user-content-wp_weixin_ms_auth_blog_id) and [wp_weixin_ms_pay_blog_id](#user-content-wp_weixin_ms_pay_blog_id) filter hooks to account for the different possible scenarios (see a simple example plugin [here](https://gist.github.com/froger-me/c918d7f2c4f88eff1b330a50d8962f23)).

## WeChat SDK

One of the most poweful tools provided by WP Weixin is its PHP Wechat Software Development Kit. To get an instance of the WeChat SDK, developers can use the following snippet:

```php
$wechat_sdk = wp_weixin_get_wechat();
```

The returned value is an instance of `WP_Weixin_Wechat`, which is a wrapper class for `Wechat_SDK`: it ensures all settings and tokens are valid and initialised. Developers are discouraged from using the `Wechat_SDK` class directly.

**All the public methods of `Wechat_SDK` are callable through the `WP_Weixin_Wechat` object and should be used only for advanced purposes. These are low level methods compared to the [provided functions](#user-content-functions): the latter should be used where possible, and developers should only use the SDK if no function achieving the intended result exists.**  
For the public methods available, please refer to the [source code of `Wechat_SDK`](https://github.com/froger-me/wp-weixin/blob/master/lib/wechat-sdk/wechat-sdk.php) directly.

Quick, non-optimised example of advanced use - do something with the list of followers' openIDs, with error handling:
```php
$wechat      = wp_weixin_get_wechat();
$next_openid = true;
$result      = $wechat->users();
$error       = $wechat->getError();

// Warning - will loop until WeChat stops providing results ; do not use in production 
while ( false !== $next_openid && ! $error ) {

	if ( is_array( $result ) ) {
		$next_openid = ( ! empty( $result['next_openid'] ) ) ? $result['next_openid'] : false;

		// Do something with the returned data
		do_something( $result['data'] );
	} else {
		$next_openid = false;
	}

	if ( $next_openid ) {
		$result = $wechat->users( $next_openid );
		$error  = $wechat->getError();
	}
}

if ( $error ) {
	// Handle the error with the array containing the error information
	handle_error( $error );
}
```

## Functions

The functions listed below are made publicly available by the plugin for theme and plugin developers. Although the plugin's main classes can theoretically be instanciated without side effect if the `$hook_init` parameter is set to `false`, it is recommended to use only the following functions as there is no guarantee future updates won't introduce changes of behaviors.

Functions index:
* [wp_weixin_is_wechat](#user-content-wp_weixin_is_wechat)
* [wp_weixin_get_user_by_openid](#user-content-wp_weixin_get_user_by_openid)
* [wp_weixin_get_user_by_unionid](#user-content-wp_weixin_get_user_by_unionid)
* [wp_weixin_get_wechat](#user-content-wp_weixin_get_wechat)
* [wp_weixin_get_options](#user-content-wp_weixin_get_options)
* [wp_weixin_get_option](#user-content-wp_weixin_get_option)
* [wp_weixin_wpml_switch_lang](#user-content-wp_weixin_wpml_switch_lang)
* [wp_weixin_get_signed_package](#user-content-wp_weixin_get_signed_package)
* [wp_weixin_get_user_wechat_info](#user-content-wp_weixin_get_user_wechat_info)
* [wp_weixin_get_user_wechat_openid](#user-content-wp_weixin_get_user_wechat_openid)
* [wp_weixin_get_auth_link](#user-content-wp_weixin_get_auth_link)
* [wp_weixin_get_bind_link](#user-content-wp_weixin_get_bind_link)
* [wp_weixin_unbind](#user-content-wp_weixin_unbind)
* [wp_weixin_bind](#user-content-wp_weixin_bind)
___

#### wp_weixin_is_wechat

```php
wp_weixin_is_wechat();
```

**Description**  
Wether the visitor is using the WeChat browser.  

**Return value**  
> (bool) Wether the vistor is using the WeChat browser.  
___

#### wp_weixin_get_user_by_openid

```php
wp_weixin_get_user_by_openid( string $openid );
```

**Description**  
Get a WordPress user by WeChat openID.  

**Parameters**  
$openid
> (string) A WeChat openID.

**Return value**  
> (mixed) A `WP_User` if a WordPress user bound with a corresponding WeChat openID exists, `false` otherwise.  
___

#### wp_weixin_get_user_by_unionid

```php
wp_weixin_get_user_by_unionid( string $unionid, int $blog_id = false );
```

**Description**  
Get a WordPress user by WeChat unionID, or a collection of WordPress users if several matches exist (possible only in the case of Multisite with multiple Official Accounts).  

**Parameters**  
$unionid
> (string) A WeChat unionID.

**Return value**  
> (mixed) A `WP_User` object if a WordPress user with a corresponding WeChat unionID exists, an array of `WP_User` objects if several matches exist, `false` otherwise.  
___

#### wp_weixin_get_wechat

```php
wp_weixin_get_wechat();
```

**Description**  
Get an instance of `WP_Weixin_Wechat` (wrapper object for `Wechat_SDK` - see [WeChat SDK](#user-content-wechat-sdk)).  

**Return value**  
> (WP_Weixin_Wechat) An instance of the wrapper object for `Wechat_SDK`.  
___

#### wp_weixin_get_options

```php
wp_weixin_get_options();
```

**Description**  
Get all the options used to configure the plugin.  

**Return value**  
> (array) An associative array with all the options used to configure the plugin.  
___

#### wp_weixin_get_option

```php
wp_weixin_get_option( $key );
```

**Description**  
Get a specific option value used to configure the plugin.  

**Parameters**  
$key
> (string) The option key.

**Return value**  
> (mixed) A string, boolean, or integer if the option has a value, `null` otherwise.  
___

#### wp_weixin_wpml_switch_lang

```php
wp_weixin_wpml_switch_lang( $force = true );
```

**Description**  
If WPML is active and the current user's WeChat language is known, switch the language to the value provided by the user's WeChat account.  
Uses `SitePress::switch_lang( $code = null, $cookie_lang = false )` - it is up to the developer to get up to speed with WMPL code base and documentation.  

**Parameters**  
$force
> (bool) If set to `true`, will always switch the language ; if `false`, the language will be switched only if "Browser language redirect" is enabled in WPML - default `true`.

**Return value**  
> (bool) Wether `SitePress::switch_lang( $code = null, $cookie_lang = false )` was called.  
___

#### wp_weixin_get_signed_package

```php
wp_weixin_get_signed_package();
```

**Description**  
Get a WeChat signed package to use with the WeChat JSAPI.  
Note: the JavaScript global variable `wx` is already properly signed and initialised with the complete `jsApiList` if the `wp-weixin-main-script` is already enqueued.  
See the ["JavaScript" section of the documentation](#user-content-javascript) for more details.

**Return value**  
> (array) The signed package to pass to a script via `wp_localize_script( $handle, $object_name, $l10n )`.  
___

#### wp_weixin_get_user_wechat_info

```php
wp_weixin_get_user_wechat_info( int $user_id = false, bool $output = false );
```
**Description**  
Get a user's WeChat information. Gets the current user's if the user ID is omitted.  

**Parameters**  
$user_id
> (int) The ID of the user - default `false`.

$output
> (bool) Wheter to output the information (using the [wp-weixin-public-info](#user-content-wp-weixin-public-info) template) - default `false`.

**Return value**  
> (mixed) An array of WeChat information if exists, `false` otherwise.  
___

#### wp_weixin_get_user_wechat_openid

```php
wp_weixin_get_user_wechat_openid( int $user_id = false );
```
**Description**  
Get a user's WeChat openID. Gets the current user's if the user ID is omitted.  

**Parameters**  
$user_id
> (int) The ID of the user - default `false`.

**Return value**  
> (mixed) A WeChat openID if exists, `false` otherwise.  
___

#### wp_weixin_get_auth_link

```php
wp_weixin_get_auth_link( bool $output = false, string $target = '' );
```
**Description**  
Get a link to the WeChat authentication page.  
This function has no effect in the WeChat browser.  

**Parameters**  
$output
> (bool) Wether to output the link.

$target
> (string) The target of the link.

**Return value**  
> (mixed) If `$output` is set to `true`, the link's markup - `false` otherwise. 
___

#### wp_weixin_get_bind_link

```php
wp_weixin_get_bind_link( bool $output = false, string $target = '_blank' );
```
**Description**  
Get a link to the WeChat account binding page.  
This function has no effect in the WeChat browser.  

**Parameters**  
$output
> (bool) Wether to output the link.

$target
> (string) The target of the link.

**Return value**  
> (mixed) If `$output` is set to `true`, the link's markup - `false` otherwise. 
___

#### wp_weixin_unbind

```php
wp_weixin_unbind( int $user_id, string $open_id = '' );
```
**Description**  
Unbind a WordPress user account previously bound with WeChat, effectively deleting all the recorded information related to the associated WeChat account.  
Note: a WeChat-only WordPress user account is a WordPress account that was created automatically by WP Weixin when opening the website in WeChat browser (Username following the `wx-[openid]` pattern).  
If a `user_id` corresponding to a WeChat-only WordPress user account that may or may not have been previously bound is provided (Username following the `wx-[openid]` or `wx-bound-[openid]` pattern), the Username is updated with the `wx-unbound-[openid]` pattern.  

**Parameters**  
$user_id
> (int) The ID of the user.

$open_id
> (string) The openID of the WeChat account - if left empty, set to the current user's recorded value.

**Return value**  
> (bool) Wether the account was unbound.  
___

#### wp_weixin_bind

```php
wp_weixin_bind( int $user_id, string $openid );
```
**Description**  
Bind a WordPress user account with WeChat, effectively overwritting all the recorded information related to an associated WeChat account if exist.  
Note: a WeChat-only WordPress user account is a WordPress account that was created automatically by WP Weixin when opening the website in WeChat browser (Username following the `wx-[openid]` pattern).  
A WeChat-only WordPress user account with the provided `$openid` recorded must exist.  
If a value for `$user_id` corresponding to a WeChat-only WordPress user account that may or may not have been previously unbound is provided (Username following the `wx-[openid]` or `wx-unbound-[openid]` pattern), the Username is updated with the `wx-bound-[openid]` pattern.  
A given openID cannot be used to bind WeChat with multiple WordPress user accounts.

**Parameters**  
$user_id
> (int) The ID of the user.

$open_id
> (string) The openID corresponding to a WeChat-only WordPress user account.

**Return value**  
> (bool) Wether the account was bound.  
___

## Hooks - actions & filters

WP Weixin gives developers the possibilty to customise its behavior with a series of custom actions and filters. 

### Actions

Actions index:
* [wp_weixin_extensions](#user-content-wp_weixin_extensions)
* [wp_weixin_responder](#user-content-wp_weixin_responder)
* [wp_weixin_save_access_info](#user-content-wp_weixin_save_access_info)
* [wp_weixin_before_user_profile_wechat_info](#user-content-wp_weixin_before_user_profile_wechat_info)
* [wp_weixin_after_user_profile_wechat_info](#user-content-wp_weixin_after_user_profile_wechat_info)
* [wp_weixin_before_bind_account](#user-content-wp_weixin_before_bind_account)
* [wp_weixin_after_bind_account](#user-content-wp_weixin_after_bind_account)
* [wp_weixin_before_unbind_account](#user-content-wp_weixin_before_unbind_account)
* [wp_weixin_after_unbind_account](#user-content-wp_weixin_after_unbind_account)
* [wp_weixin_before_tabs_settings](#user-content-wp_weixin_before_tabs_settings)
* [wp_weixin_before_main_tab_settings](#user-content-wp_weixin_before_main_tab_settings)
* [wp_weixin_before_main_settings_inner](#user-content-wp_weixin_before_main_settings_inner)
* [wp_weixin_after_main_settings_inner](#user-content-wp_weixin_after_main_settings_inner)
* [wp_weixin_after_main_tab_settings](#user-content-wp_weixin_after_main_tab_settings)
* [wp_weixin_before_qr_tab_settings](#user-content-wp_weixin_before_qr_tab_settings)
* [wp_weixin_after_qr_tab_settings](#user-content-wp_weixin_after_qr_tab_settings)
* [wp_weixin_after_tabs_settings](#user-content-wp_weixin_after_tabs_settings)
* [wp_weixin_before_settings](#user-content-wp_weixin_before_settings)
* [wp_weixin_before_main_settings](#user-content-wp_weixin_before_main_settings)
* [wp_weixin_after_main_settings](#user-content-wp_weixin_after_main_settings)
* [wp_weixin_before_qr_settings](#user-content-wp_weixin_before_qr_settings)
* [wp_weixin_before_qr_settings_inner](#user-content-wp_weixin_before_qr_settings_inner)
* [wp_weixin_after_qr_settings_inner](#user-content-wp_weixin_after_qr_settings_inner)
* [wp_weixin_after_qr_settings](#user-content-wp_weixin_after_qr_settings)
* [wp_weixin_after_settings](#user-content-wp_weixin_after_settings)
* [wp_weixin_handle_payment_notification](#user-content-wp_weixin_handle_payment_notification)
* [wp_weixin_endpoints](#user-content-wp_weixin_endpoints)
* [wp_weixin_handle_auto_refund](#user-content-wp_weixin_handle_auto_refund)
* [wp_weixin_pay_refund_failed](#user-content-wp_weixin_pay_refund_failed)

___

#### wp_weixin_extensions

```php
do_action( 'wp_weixin_extensions', mixed $wechat, mixed $wp_weixin_settings, mixed $wp_weixin, mixed $wp_weixin_auth, mixed $wp_weixin_responder, mixed $wp_weixin_menu );
```

**Description**  
Fired when WP Weixin is fully loaded and if "Enabled" is checked in WP Weixin Main Settings. Typically used to build plugins using WP Weixin as a core.  
Note: it is recommended to use the [provided functions](#user-content-functions) where possible instead of the methods of this action's parameters, as there is no guarantee future updates won't introduce changes of behaviors.

**Parameters**  
$wechat
> (mixed) A `WP_Weixin_Wechat` object.

$wp_weixin_settings
> (mixed) A `WP_Weixin_Settings` object.

$wp_weixin
> (mixed) A `WP_Weixin` object.

$wp_weixin_auth
> (mixed) A `WP_Weixin_Auth` object.

$wp_weixin_responder
> (mixed) A `WP_Weixin_Responder` object if the WeChat Responder is enabled, `false` otherwise.

$wp_weixin_menu
> (mixed) A `WP_Weixin_Menu` object if the WeChat Responder is enabled, `false` otherwise.  
___

#### wp_weixin_responder

```php
do_action( 'wp_weixin_responder', array $request_data );
```

**Description**  
Fired after receiving a request from WeChat.  

**Parameters**  
$request_data
> (array) The data sent in the request from WeChat.  
___

#### wp_weixin_save_access_info

```php
do_action( 'wp_weixin_save_access_info', array $access_info );
```

**Description**  
Fired after renewing the Official Account access_token if custom persistence is used. Used to save the access information - particularly useful to avoid a race condition if the access_token needs to be shared between multiple platforms.

**Parameters**  
$access_info
> (array) The access information in an associative array. Keys are `token` and `expiry`.  
___

#### wp_weixin_before_user_profile_wechat_info

```php
do_action( 'wp_weixin_before_user_profile_wechat_info', mixed $wechat_info, mixed $user );
```

**Description**  
Fired before displaying WeChat public info on the user profile.

**Parameters**  
$wechat_info
> (mixed) An array of WeChat public info to display on the user profile if they exist, `false` otherwise.  

$user
> (mixed) A `WP_User` object if the user exists, `false` otherwise.  
___

#### wp_weixin_after_user_profile_wechat_info

```php
do_action( 'wp_weixin_after_user_profile_wechat_info', mixed $wechat_info, mixed $user );
```

**Description**  
Fired after displaying WeChat public info on the user profile.

**Parameters**  
$wechat_info
> (mixed) An array of WeChat public info displayed on the user profile, `false` otherwise.  

$user
> (mixed) A `WP_User` object if the user exists, `false` otherwise.  
___

#### wp_weixin_before_bind_account

````php
do_action( 'wp_weixin_before_bind_account', int $user_id, int $wechat_user_id, array $wechat_user_blog_ids, int $current_blog_id );
````
**Description**  
Fired before binding a WordPress user account with WeChat.  

**Parameters**  
$user_id
> (int) The user ID.  

$wechat_user_id
> (int) ID of a WeChat-only WordPress user account (Username following the `wx-[openid]` pattern).  

$wechat_user_blog_ids
> (array) List of blog IDs the WeChat-only WordPress user account belongs to.

$current_blog_id
> (int) The blog ID of the current blog.  
___

#### wp_weixin_after_bind_account

````php
do_action( 'wp_weixin_after_bind_account', bool $bound, int $user_id, int $wechat_user_id, array $wechat_user_blog_ids, int $current_blog_id );
````
**Description**  
Fired after binding a WordPress user account with WeChat.  

**Parameters**  
$bound
> (bool) Wether the WordPress user account was successfully bound with WeChat.

$user_id
> (int) The user ID.  

$wechat_user_id
> (int) ID of a WeChat-only WordPress user account (Username following the `wx-[openid]` pattern).  

$wechat_user_blog_ids
> (array) List of blog IDs the WeChat-only WordPress user account belongs to.

$current_blog_id
> (int) The blog ID of the current blog.  
___

#### wp_weixin_before_unbind_account

````php
do_action( 'wp_weixin_before_unbind_account', int $user_id, string $openid );
````
**Description**  
Fire before unbinding a WordPress user account from WeChat.  

**Parameters**  
$user_id
> (int) The user ID.  

$openid
> (string) The WeChat openID.  
___

#### wp_weixin_after_unbind_account

````php
do_action( 'wp_weixin_after_unbind_account', bool $unbound, int $user_id, string $openid );
````
**Description**  
Fire after unbinding a WordPress user account from WeChat.  

**Parameters**  
$unbound
> (bool) Wether the WordPress user account was successfully unbound from WeChat.

$user_id
> (int) The user ID.  

$openid
> (string) The WeChat openID.  
___

#### wp_weixin_before_tabs_settings

```php
do_action( 'wp_weixin_before_tabs_settings' );
```

**Description**
Fired before outputting the tabs of the WP Weixin page.

___

#### wp_weixin_before_main_tab_settings

```php
do_action( 'wp_weixin_before_main_tab_settings' );
```

**Description**
Fired before outputting the main settings tab of the WP Weixin page.

___

#### wp_weixin_before_main_settings_inner

```php
do_action( 'wp_weixin_before_main_settings_inner' );
```

**Description**
Fired before outputting the main settings content on the WP Weixin page.

___

#### wp_weixin_after_main_settings_inner

```php
do_action( 'wp_weixin_after_main_settings_inner' );
```

**Description**
Fired after outputting the main settings content on the WP Weixin page.

___

#### wp_weixin_after_main_tab_settings

```php
do_action( 'wp_weixin_after_main_tab_settings' );
```

**Description**
Fired after outputting the main settings tab of the WP Weixin page.

___

#### wp_weixin_before_qr_tab_settings

```php
do_action( 'wp_weixin_before_qr_tab_settings' );
```

**Description**
Fired before outputting the QR code generator tab of the WP Weixin page.

___

#### wp_weixin_after_qr_tab_settings

```php
do_action( 'wp_weixin_after_qr_tab_settings' );
```

**Description**
Fired after outputting the QR code generator tab of the WP Weixin page.

___

#### wp_weixin_after_tabs_settings

```php
do_action( 'wp_weixin_after_tabs_settings' );
```

**Description**
Fired after outputting the tabs of the WP Weixin page.

___

#### wp_weixin_before_settings

```php
do_action( 'wp_weixin_before_settings' );
```

**Description**
Fired before outputting the settings on the WP Weixin page.

___

#### wp_weixin_before_main_settings

```php
do_action( 'wp_weixin_before_main_settings' );
```

**Description**
Fired before outputting the main settings box on the WP Weixin page.

___

#### wp_weixin_after_main_settings

```php
do_action( 'wp_weixin_after_main_settings' );
```

**Description**
Fired after outputting the main settings box on the WP Weixin page.

___

#### wp_weixin_before_qr_settings

```php
do_action( 'wp_weixin_before_qr_settings' );
```

**Description**
Fired before outputting the QR code generator on the WP Weixin page.

___

#### wp_weixin_before_qr_settings_inner

```php
do_action( 'wp_weixin_before_qr_settings_inner' );
```

**Description**
Fired before outputting the QR code generator box on the WP Weixin page.

___

#### wp_weixin_after_qr_settings_inner

```php
do_action( 'wp_weixin_after_qr_settings_inner' );
```

**Description**
Fired after outputting the QR code generator box on the WP Weixin page.

___

#### wp_weixin_after_qr_settings

```php
do_action( 'wp_weixin_after_qr_settings' );
```

**Description**
Fired after outputting the QR code generator on the WP Weixin page.

___

#### wp_weixin_after_settings

```php
do_action( 'wp_weixin_after_settings' );
```

**Description**
Fired after outputting the settings on the WP Weixin page.

___

#### wp_weixin_endpoints

```php
do_action( 'wp_weixin_endpoints' );
```
**Description**  
Fired when adding WP Weixin rewrite rules. Useful for companion plugins to add their own, and make sure they are registered properly (rules are flushed when WP Weixin settings are saved).

___

#### wp_weixin_handle_payment_notification

```php
do_action( 'wp_weixin_handle_payment_notification' );
```

**Description**  
Fired when handling a WeChat Pay transaction notification.

Fired last  by WP Weixin (`PHP_INT_MIN`) ; should be fired earlier by companion plugins integrating with WeChat Pay.  
See a [WeChat Pay integration plugin skeleton](https://gist.github.com/froger-me/2c66a842ef8900b017809d7c738130c9) for how to handle WeChat Pay notifications.  

___

#### wp_weixin_handle_auto_refund

```php
do_action( 'wp_weixin_handle_auto_refund', mixed $refund_result, array $payment_result );
```

**Description**  
Fired after an automatic refund for a failed transaction has been attempted.

See a [WeChat Pay integration plugin skeleton](https://gist.github.com/froger-me/2c66a842ef8900b017809d7c738130c9) for how to handle WP Weixin automatic refund results.  

**Parameters**  
$refund_result
> (mixed) An array containing the WeChat Pay API's response in case the refund wass successful, `false` otherwise.  

$payment_result
> (array) A payment notification result. Structure of a result:
```php
array(
	'success'      => false,    // optional - (bool) wether the transaction to handle was found ; default false
	'data'         => $data,    // required - (array) return value of WP_Weixin_Wechat::getNotify()
	'refund'       => false,    // optional - (mixed) false if no refund needed, true or an refund message for the user otherwise ; default false
	'notify_error' => false,    // optional - (mixed) false if no error, true or an error message otherwise ; if truthy and pay_handler set to true, WeChat Pay API will continue to send notifications for the transaction ; default false
	'blog_id'      => $blog_id, // required for multisite, optional otherwise - (int) the ID of the blog where the original transaction was made ; default the return value of get_current_blog_id()
	'pay_handler'  => false,    // optional - (bool) wether the result is for the callback registered in the WeChat Pay backend ; default false
	/* More custom items can safely be added to the array */
);
```  
___

### Filters

Filters index:

* [wp_weixin_browser_page_qr_src](#user-content-wp_weixin_browser_page_qr_src)
* [wp_weixin_subscribe_src](#user-content-wp_weixin_subscribe_src)
* [wp_weixin_follower_notice_title](#user-content-wp_weixin_follower_notice_title)
* [wp_weixin_follower_notice](#user-content-wp_weixin_follower_notice)
* [wp_weixin_auth_needed](#user-content-wp_weixin_auth_needed)
* [wp_weixin_debug](#user-content-wp_weixin_debug)
* [wp_weixin_follower_welcome_title](#user-content-wp_weixin_follower_welcome_title)
* [wp_weixin_follower_welcome_description](#user-content-wp_weixin_follower_welcome_description)
* [wp_weixin_follower_welcome_url](#user-content-wp_weixin_follower_welcome_url)
* [wp_weixin_follower_welcome_pic_url](#user-content-wp_weixin_follower_welcome_pic_url)
* [wp_weixin_get_access_info](#user-content-wp_weixin_get_access_info)
* [wp_weixin_jsapi_urls](#user-content-wp_weixin_jsapi_urls)
* [wp_weixin_pay_callback_url](#user-content-wp_weixin_pay_callback_url)
* [wp_weixin_settings](#user-content-wp_weixin_settings)
* [wp_weixin_show_settings_section](#user-content-wp_weixin_show_settings_section)
* [wp_weixin_show_setting](#user-content-wp_weixin_show_setting)
* [wp_weixin_settings_fields](#user-content-wp_weixin_settings_fields)
* [wp_weixin_auth_redirect](#user-content-wp_weixin_auth_redirect)
* [wp_weixin_scan_heartbeat_frequency](#user-content-wp_weixin_scan_heartbeat_frequency)
* [wp_weixin_qr_cleanup_frequency](#user-content-wp_weixin_qr_cleanup_frequency)
* [wp_weixin_qr_lifetime](#user-content-wp_weixin_qr_lifetime)
* [wp_weixin_user_wechat_info](#user-content-wp_weixin_user_wechat_info)
* [wp_weixin_ms_auto_add_to_blog](#user-content-wp_weixin_ms_auto_add_to_blog)
* [wp_weixin_ms_auth_blog_id](#user-content-wp_weixin_ms_auth_blog_id)
* [wp_weixin_ms_pay_blog_id](#user-content-wp_weixin_ms_pay_blog_id)
* [wp_weixin_locate_template_paths](#user-content-wp_weixin_locate_template_paths)
* [wp_weixin_get_user_by_openid](#user-content-wp_weixin_get_user_by_openid)
* [wp_weixin_pay_notify_results](#user-content-wp_weixin_pay_notify_results)

___

#### wp_weixin_browser_page_qr_src

```php
apply_filters( 'wp_weixin_browser_page_qr_src', string $src );
```

**Description**  
Filter the source of the QR code to show on classic browsers for a page only accessible through WeChat browser.  

**Parameters**  
$src
> (string) The source of the QR code to show on classic browsers.  
___

#### wp_weixin_subscribe_src

```php
apply_filters( 'wp_weixin_subscribe_src', string $src );
```

**Description**  
Filter the source of the QR code used to follow the Official Account.  

**Parameters**  
$src
> (string) The source of the QR code.  
___

#### wp_weixin_follower_notice_title

```php
apply_filters( 'wp_weixin_follower_notice_title', string $title );
```

**Description**  
Filter the title of the page displaying the QR code to follow the Official Account.  

**Parameters**  
$title
> (string) The title of the page - default "Follow Us!".  
___

#### wp_weixin_follower_notice

```php
apply_filters( 'wp_weixin_follower_notice', string $notice );
```

**Description**  
Filter the message displayed on the page displaying the QR code to follow the Official Account.  

**Parameters**  
$notice
> (string) The displayed message - default "Please scan this QR Code to follow us before accessing this content.".  
___

#### wp_weixin_auth_needed

```php
apply_filters( 'wp_weixin_auth_needed', bool $needs_auth );
```

**Description**  
Wether the URL needs the user to be authenticated using WeChat. When "Enable WeChat authentication" is checked in the settings, URLs triggering WordPress's `init` action hook need authentication by default, unless they are whitelisted using this filter. By default, all the admin pages, the WP Weixin classic browser authentication page, the WordPress ajax endpoint, the WeChat responder endpoint, and the WooCommerce API endpoints are whitelisted and accessible outside WeChat.  

**Parameters**  
$needs_auth
> (bool) Wether authentication is needed to visit the URL.  
___

#### wp_weixin_debug

```php
apply_filters( 'wp_weixin_debug', bool $debug );
```

**Description**  
Filter wether to activate debug mode (PHP error logs, JavaScript console messages, JavaScript alerts).  

**Parameters**  
$debug
> (bool) Wether debug mode is activated - default `WP_DEBUG` constant value.  
___

#### wp_weixin_follower_welcome_title

```php
apply_filters( 'wp_weixin_follower_welcome_title', string $title, mixed $before_subscription );
```

**Description**  
Filter the title of the message the user receives when following the Official Account.  

**Parameters**  
$title
> (string) The title - default "'Welcome `user_name`!'" where `user_name` is the user's WeChat Name.  

$before_subscription
> (mixed) If numeric, the `WP_Post` ID of the last page the user was visiting ; if string, the URL of the last page the user was visiting - default `home_url()`.  
___

#### wp_weixin_follower_welcome_description

```php
apply_filters( 'wp_weixin_follower_welcome_description', string $description, mixed $before_subscription );
```

**Description**  
Filter the description of the message the user receives when following the Official Account.  

**Parameters**  
$description
> (string) The description - default "Thank you for subscribing our official account!".  

$before_subscription
> (mixed) If numeric, the `WP_Post` ID of the last page the user was visiting ; if string, the URL of the last page the user was visiting - default `home_url()`.  
___

#### wp_weixin_follower_welcome_url

```php
apply_filters( 'wp_weixin_follower_welcome_url', string $url, mixed $before_subscription );
```

**Description**  
Filter the URL the user will be redirected to when interacting with the message received when following the Official Account.  

**Parameters**  
$url
> (string) The URL the user will be redirected to - default `home_url()` if no URL was recorded before sending the templated message.  

$before_subscription
> (mixed) If numeric, the `WP_Post` ID of the last page the user was visiting ; if string, the URL of the last page the user was visiting - default `home_url()`.  
___

#### wp_weixin_follower_welcome_pic_url

```php
apply_filters( 'wp_weixin_follower_welcome_pic_url', string $pic_url, mixed $before_subscription );
```

**Description**  
Filter the URL of the picture displayed on the message the user receives when following the Official Account.  

**Parameters**  
$pic_url
> (string) The URL of the picture - default `WP_PLUGIN_URL . '/wp-weixin/images/default-welcome.png'`.  

$before_subscription
> (mixed) If numeric, the `WP_Post` ID of the last page the user was visiting ; if string, the URL of the last page the user was visiting - default `home_url()`.  
___

#### wp_weixin_get_access_info

```php
apply_filters( 'wp_weixin_get_access_info', array $access_info );
```

**Description**  
Filter the access token and token expiry when requesting the `WP_Weixin_WeChat` object (wrapper of a `Wechat_SDK` object) if custom persistence is used - particularly useful to avoid a race condition if the access token needs to be shared between multiple platforms.

**Parameters**  
$access_info
> (array) The access information in an associative array. Value types and keys: (string) `token`, (int) `expiry`.  
___

#### wp_weixin_jsapi_urls

```php
apply_filters( 'wp_weixin_jsapi_urls', array $jsapi_urls );
```

**Description**  
Filter the URLs necessary to register on the WeChat merchant account's API configuration screen - used when another plugin implements WeChat Pay integration.

**Parameters**  
$jsapi_urls
> (array) The URLs to register on the WeChat merchant account's API configuration screen.  
___

#### wp_weixin_pay_callback_endpoint

```php
apply_filters( 'wp_weixin_pay_callback_endpoint', string $endpoint );
```

**Description**  
Filter the endpoint of the QR Payment URL necessary to register on the WeChat merchant account's API configuration screen - used when implementing WeChat Pay integration.

**Parameters**  
$callback_url
> (string) The endpoint of the QR Payment URL to register on the WeChat merchant account's API configuration screen (example: `/my_plugin/notify`).  
___

#### wp_weixin_settings

```php
apply_filter( 'wp_weixin_settings', $settings );
```

**Description**  
Filter the settings used to configure the plugin.
Hooked functions or methods need to be added to this filter in a `plugins_loaded` action hook of priority of `5` or less.

**Parameters**  
$settings
> (array) The settings used to configure the plugin.  
___

#### wp_weixin_show_settings_section

```php
apply_filters( 'wp_weixin_show_settings_section', bool $show_section, string $section_name, array $section );
```

**Description**  
Filter wether to show a settings section on the WP Weixin settings page.

**Parameters**  
$show_section
> (bool) Wether to show the settings section on the WP Weixin settings page.  

$section_name
> (string) The name of the settings section.  

$section
> (array) The section's settings.  

#### wp_weixin_show_setting

```php
apply_filters( 'wp_weixin_show_setting', bool $show_setting, string $section_name, int $index, array $value );
```

**Description**  
Filter wether to show a setting on the WP Weixin settings page.

**Parameters**  
$show_setting
> (bool) Wether to show the setting on the WP Weixin settings page.  

$section_name
> (string) The name of the section the setting belongs to.  

$index
> (int) The index of the setting in the section.  

$value
> (array) The setting.  

___

#### wp_weixin_settings_fields

```php
apply_filters( 'wp_weixin_settings_fields', array $settings_fields );
```

**Description**  
Filter the settings fields displayed on the WP Weixin settings page.

**Parameters**  
$include_section
> (array) The settings fields displayed on the WP Weixin settings page.  
___

#### wp_weixin_auth_redirect

```php
apply_filters( 'wp_weixin_auth_redirect', mixed $redirect, bool $auth, bool $has_error );
```

**Description**  
Filter the URL to redirect to when QR code authentication in classic browsers is performed.

**Parameters**  
$redirect
> (mixed) The URL to redirect to when authentication is performed, or `false` if no redirect. Default is `home_url()` in case of successful authentication.  

$auth
> (bool) Wether the authentication was a performed - `true` if successful, `false` if an error occurred.  

$has_error
> (bool) Wether an error occurred.  
___

#### wp_weixin_scan_heartbeat_frequency

```php
apply_filters( 'wp_weixin_scan_heartbeat_frequency', int $frequency );
```

**Description**  
Filter the frequency of the checks when waiting for QR code scan confirmation in classic browsers.

**Parameters**  
$frequency
> (int) The frequency in milliseconds. Default `1000`.  
___

#### wp_weixin_qr_cleanup_frequency

```php
apply_filters( 'wp_weixin_qr_cleanup_frequency', string $frequency );
```

**Description**  
Filter the frequency to clean up expired QR code data.

**Parameters**  
$frequency
> (string) The frequency. Default `'hourly'`.  
___

#### wp_weixin_qr_lifetime

```php
apply_filters( 'wp_weixin_qr_lifetime', int $lifetime );
```

**Description**  
Filter the lifetime of a potentially sensitive QR code, such as WeChat authentication or WeChat account binding.

**Parameters**  
$lifetime
> (int) The lifetime in seconds. Default `600`.  
___

#### wp_weixin_user_wechat_info

```php
apply_filters( 'wp_weixin_user_wechat_info', mixed $wechat_info, int $user_id );
```

**Description**  
Filter the user WeChat information.

**Parameters**  
$wechat_info
> (mixed) An array of WeChat information if exist, `false` otherwise.  

$lifetime
> (int) The user ID - default `0`.  
___

#### wp_weixin_ms_auto_add_to_blog

```php
apply_filters( 'wp_weixin_ms_auto_add_to_blog', bool $auto_add_to_blog, int $blog_id, int $user_id );
```

**Description**  
Filter wether to automatically add the user to the visited blog on the network when authenticated with WeChat.

**Parameters**  
$auto_add_to_blog
> (bool) Wether to automatically add the user to the visited blog on the network when authenticated with WeChat - default `true`.  

$blog_id
> (int) The ID of the visited blog.  

$user_id
> (int) The ID of the user visiting the blog.  
___

#### wp_weixin_ms_auth_blog_id

```php
apply_filters( 'wp_weixin_ms_auth_blog_id', int $auth_blog_id );
```

**Description**  
Filter the blog ID used for authentication - by default, it is assumed the domain name of the default blog is registered in WeChat backend.

**Warning:** to make sure WP Weixin supports multiple Official Accounts, the openIDs of bound accounts are stored using a user meta record containing the value of `$auth_blog_id` in its meta key (`'wx_openid-' . $auth_blog_id`).  
If WeChat-bound WordPress users already exist (manually bound or automatically created when visiting the site with the WeChat browser), applying this filter and return an altered value of `$auth_blog_id` will break the relationship between the user and the recorded openID during runtime.  
It is up to the developer to update the database directly, or run a one-time use code snippet like below.

Example of code snippet to run after changing the blog ID used for authentication in case WordPress users are already bound with WeChat: 

```php
global $wpdb;

$old_auth_blog_id = 1;
$new_auth_blog_id = 2;

$wpdb->query(
	$wpdb->prepare(
		"UPDATE $wpdb->usermeta SET `meta_key` = 'wx_openid-%d' WHERE `meta_key` = 'wx_openid-%d';",
		$new_auth_blog_id,
		$old_auth_blog_id
	)
);
```

**Parameters**  
$auth_blog_id
> (int) The ID of the blog to use when doing WeChat authentication. Default `1`.  
___

#### wp_weixin_ms_pay_blog_id

```php
apply_filters( 'wp_weixin_ms_pay_blog_id', int $pay_blog_id );
```

**Description**  
Filter the blog ID used to build the URLs allowed to call and receive payment notifications from the WeChat Pay API - by default, it is assumed the domain ( or subdomain ) corresponding to the ID of the the current blog is registered in WeChat backend. Useful in case several instances of WooCommerce are running on the same network, or in the case of a network connected to several Official Accounts.

**Parameters**  
$pay_blog_id
> (int) The ID of the blog used to build the QR Payment callback URL. Default `get_current_blog_id()`.  
___

#### wp_weixin_locate_template_paths

```php
apply_filters( 'wp_weixin_locate_template_paths', array $paths, string $plugin_name );
```

**Description**  
Filter the possible paths of templates included by WP Weixin and companion plugins.

**Parameters**  
$paths
> (array) The possible paths. Default (where `$template_name` is the template's file name):
> ```php
> array(
> 	'plugins/wp-weixin/' . $plugin_name . $template_name,
> 	'wp-weixin/' . $plugin_name . $template_name,
> 	'plugins/' . $plugin_name . $template_name,
> 	$plugin_name . $template_name,
> 	'wp-weixin/' . $template_name,
> 	$template_name,
> );
> ```

$plugin_name
> (string) The name of the plugin the template belongs to.

___
#### wp_weixin_get_user_by_openid

```php
apply_filters( 'wp_weixin_get_user_by_openid', $user, $openid );
```

**Description**  
Filter the result of a query getting a WordPress user associated with a recorded WeChat openID.

**Parameters**  
$user
> (mixed) The `WP_User` object if the user was found, `false` otherwise.

$openid
> (string) The openID used to search for the user
___

#### wp_weixin_pay_notify_results

```php
apply_filters( 'wp_weixin_pay_notify_results', (array) $results );
```
**Description**  
Filter the results of handling a payment notification.

Not actually applied by WP Weixin directly itself, but only after a companion plugin has fired [wp_weixin_handle_payment_notification](#user-content-wp_weixin_handle_payment_notification).  
See a [WeChat Pay integration plugin skeleton](https://gist.github.com/froger-me/2c66a842ef8900b017809d7c738130c9) for how to add payment notification results.  

**Parameters**  
$results
> (array) An array of payment notification results. Structure of a result:
```php
array(
	'success'      => false,    // optional - (bool) wether the transaction to handle was found ; default false
	'data'         => $data,    // required - (array) return value of WP_Weixin_Wechat::getNotify()
	'refund'       => false,    // optional - (mixed) false if no refund needed, true or an refund message for the user otherwise ; default false
	'notify_error' => false,    // optional - (mixed) false if no error, true or an error message otherwise ; if truthy and pay_handler set to true, WeChat Pay API will continue to send notifications for the transaction ; default false
	'blog_id'      => $blog_id, // required for multisite, optional otherwise - (int) the ID of the blog where the original transaction was made ; default the return value of get_current_blog_id()
	'pay_handler'  => false,    // optional - (bool) wether the result is for the callback registered in the WeChat Pay backend ; default false
	/* More custom items can safely be added to the array */
);
```
___

## Templates

The following template files are selected using the `locate_template()` and included with `load_template()` functions provided by WordPress. This means they can be overloaded in the active WordPress theme. Developers may place their custom template files in the following directories under the theme's folder (in order of selection priority):

* `plugins/wp-weixin/`
* `wp-weixin/`
* `plugins/`
* at the root of the theme's folder

The available paths of the templates may be customised with the [wp_weixin_locate_template_paths](#user-content-wp_weixin_locate_template_paths) filter. 
The style applied to all the templates below is enqueued as `'wp-weixin-main-style'`.  

Templates index:
* [wp-weixin-subscribe](#user-content-wp-weixin-subscribe)
* [wp-weixin-browser-qr](#user-content-wp-weixin-browser-qr)
* [wp-weixin-auth-form-link](#user-content-wp-weixin-auth-form-link)
* [wp-weixin-auth-page](#user-content-wp-weixin-auth-page)
* [wp-weixin-mobile-auth-check](#user-content-wp-weixin-mobile-auth-check)
* [wp-weixin-bind-form-link](#user-content-wp-weixin-bind-form-link)
* [wp-weixin-bind-page](#user-content-wp-weixin-bind-page)
* [wp-weixin-mobile-bind-check](#user-content-wp-weixin-mobile-bind-check)
* [wp-weixin-public-info](#user-content-wp-weixin-public-info)
___

#### wp-weixin-subscribe

```
wp-weixin-subscribe.php
```

**Description**  
The template of the page displaying the QR code to follow the Official Account. Used when "Force follow" is enabled in the settings.

$title
> (string) The title of the screen presented to the user.

$message
> (string) The message describing why the user sees this screen.

$qr_src 
> (string) The source of the QR code image.

___

#### wp-weixin-browser-qr

```
wp-weixin-browser-qr.php
```

**Description**  
The template of the page displaying the QR code when the website is accessible only through the WeChat browser.  

**Variables**  
$page_qr_src
> (string) The source of the QR code image.  
___

#### wp-weixin-auth-form-link

```
wp-weixin-auth-form-link.php
```

**Description**  
The template of the WeChat authentication link.  

**Variables**  
$class
> (string) The class attribute of the link.

$target
> (string) The target attribute of the link.  
___

#### wp-weixin-auth-page

```
wp-weixin-auth-page.php
```

**Description**  
The template of the WeChat screen displayed for QR code authentication in classic browsers. 
___

#### wp-weixin-mobile-auth-check

```
wp-weixin-mobile-auth-check.php
```

**Description**  
The template of the WeChat mobile browser screen displayed when authenticating via QR code authentication in classic browsers.  

**Variables**  
$auth_qr_data
> (array) Data related to the authentication. Value types and keys: (bool) `auth`, (int) `user_id`, (array) `error`, (bool|string) `redirect`. The `redirect` value is not actually used for redirection by default on mobile (used after authentication on desktop).  
___

#### wp-weixin-bind-form-link

```
wp-weixin-bind-form-link.php
```

**Description**  
The template of the WeChat account binding link.  

**Variables**  
$link_text
> (string) The text of the link.

$wechat_info
> (mixed) An array of WeChat information if exists, `false` otherwise.

$class
> (string) The class attribute of the link.

$target
> (string) The target attribute of the link.  
___

#### wp-weixin-bind-page

```
wp-weixin-bind-page.php
```

**Description**  
The template of the WeChat screen displayed for WeChat account bindind in classic browsers.  

**Variables**  
$user_id
> (int) The ID of the user to bind to a WeChat account.

$wechat_info
> (mixed) An array of WeChat information if exists, `false` otherwise.  
___

#### wp-weixin-mobile-bind-check

```
wp-weixin-mobile-bind-check.php
```

**Description**  
The template of the WeChat mobile browser screen displayed when attempting to a WeChat account via QR code in classic browsers.  

**Variables**  
$bind_qr_data
> (array) Data related to the account binding. Value types and keys: (bool) `bind`, (int) `user_id`, (array) `error`, (bool|string) `redirect`. The `redirect` value is always `false` on mobile (populated and used after account binding on desktop).  
___

#### wp-weixin-public-info

```
wp-weixin-public-info.php
```
**Description**  
The template to output the WeChat public information - notably used when calling [wp_weixin_get_user_wechat_info](#user-content-wp_weixin_get_user_wechat_info) with the `$output` parameter set to `false`.

**Variables**  
$wechat_info
> (array) The WeChat public information. Value are all of type (string), with keys: `nickname`, `headimgurl`, `sex`, `language`, `city`, `province`, `country`, `unionid`.  
___

## JavaScript

The global variable `wx` is already properly signed and initialised with the complete `jsApiList`.  
To use it properly, developers must:
- include their scripts in `wp_enqueue_scripts` action hook with a priority of `6` or more,
- make sure to set `wp-weixin-main-script` as a dependency
- make sure "Enabled" is checked in WP Weixin Main Settings

In addition, a provided list of listeners may be subscribed to.  

JavaScript listeners index:
* [wpWeixinShareTimelineSuccessListener](#user-content-wpweixinsharetimelinesuccesslistener)
* [wpWeixinShareTimelineFailureListener](#user-content-wpweixinsharetimelinefailurelistener)
* [wpWeixinShareAppMessageSuccessListener](#user-content-wpweixinshareappmessagesuccesslistener)
* [wpWeixinShareAppMessageFailureListener](#user-content-wpweixinshareappmessagefailurelistener)

Example for how to subscribe to the `wpWeixinShareTimelineSuccessListener` listener:

```JavaScript
window.wpWeixinShareTimelineSuccessListener( handleShareTimelineSuccess );

function handleShareTimelineSuccess( shareInfo ) {
	// do something with the data
	do_something( shareInfo );
}

```
___

#### wpWeixinShareTimelineSuccessListener

```JavaScript
window.wpWeixinShareTimelineSuccessListener( callback );
```

Subscribing to this listener will execute the `callback` function after sharing the post on WeChat Moments succeeded.  

**Parameters passed to the callback**  
shareInfo
> (object) The share information sent to the WeChat JS_SDK. Attributes are `title`, `desc`, `link`, `imgUrl`.  
___

#### wpWeixinShareTimelineFailureListener

```JavaScript
window.wpWeixinShareTimelineFailureListener( callback );
```

Subscribing to this listener will execute the `callback` function after sharing the post on WeChat Moments failed.  

**Parameters passed to the callback**  
shareInfo
> (object) The share information sent to the WeChat JS_SDK. Attributes are `title`, `desc`, `link`, `imgUrl`.  
___

#### wpWeixinShareAppMessageSuccessListener

```JavaScript
window.wpWeixinShareAppMessageSuccessListener( callback );`
```

Subscribing to this listener will execute the `callback` function after sharing the post with WeChat "Send to chat" succeeded.  

**Parameters passed to the callback**  
shareInfo
> (object) The share information sent to the WeChat JS_SDK. Attributes are `title`, `desc`, `link`, `imgUrl`.  
___

#### wpWeixinShareAppMessageFailureListener

```JavaScript
window.wpWeixinShareAppMessageFailureListener( callback );
```

Subscribing to this listener will execute the `callback` function after sharing the post with WeChat "Send to chat" failed.  

**Parameters passed to the callback**  
shareInfo
> (object) The share information sent to the WeChat JS_SDK. Attributes are `title`, `desc`, `link`, `imgUrl`.  