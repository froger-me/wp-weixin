
# WP Weixin - WordPress Wechat integration

* [General description](#user-content-general-description)
	* [Overview](#user-content-overview)
	* [Object Cache considerations](#user-content-object-cache-considerations)
* [Settings](#user-content-settings)
	* [Main Settings](#user-content-main-settings)
	* [Wechat Responder Settings](#user-content-wechat-responder-settings)
	* [Wechat Pay Settings](#user-content-wechat-pay-settings---pro)
	* [Proxy Settings (beta)](#user-content-proxy-settings-beta)
	* [Miscellaneous Settings](#user-content-miscellaneous-settings)
* [Go PRO!](#user-content-go-pro)
* [Functions](#user-content-functions)
* [Hooks - actions & filters](#user-content-hooks---actions--filters)
	* [Actions](#user-content-actions)
	* [Filters](#user-content-filters)
* [Templates](#user-content-templates)
* [Javascript](#user-content-javascript)

## General Description

WP Weixin enables integration between WordPress and Wechat. It is fully functional as a standalone plugin, and also acts as a core for [Woo WechatPay](https://anyape.com/woo-wechatpay.html) payment gateway for WooCommerce and [WP Weixin Pay](https://anyape.com/wp-weixin-pay.html) extension. It is also a library for WordPress developers to build their own integration with Wechat.  
It can be used with both Official Subscription Account and Official Service Account.

### Overview

This plugin adds the following major features to WordPress:

* **WP Weixin settings page:** to configure the plugin with an Official Account.
* **Wechat Share:** Share posts and pages on Moments or Send to chat, in a pretty way. Triggers javascript events for developers on success and failure.
* **Wechat JS_SDK:** the `wx` global variable is pre-configured with a signed package to leverage the javascript SDK of Wechat in WordPress themes more easily. 
* **WP Weixin QR code generator:** to create custom codes.
* **Wechat Authentication:** to automatically create and authenticate a user in WordPress.
* **Force Wechat mobile:** to prevent users from browsing the website outside of Wechat. If accessed with an other browser, the page displays a QR code.
* **Wechat Responder:** acts as an API for developers to receive and respond to calls made by Wechat.
* **Force following the Official Account:** to harvest Wechat followers, forcing users to follow the Official Account before accessing the content.
* **Welcome message:** sends a welcome message in Wechat when a user follows the Official Account ; allows to do so with WordPress when the Wechat Responder is enabled.
* **Menu integration:** allows to set the Official Account menus in WordPress when the Wechat Responder is enabled.
* **Proxy (beta):** use a proxy to connect to Wechat.
* **WordPress Users screen override:** to display Wechat names and Wechat avatars if they exist, instead of the default values in the user screen.

Developers can also build plugins and themes integrated with Wechat with WP Weixin as a core, leveraging its publicly available functions, actions and filters.

### Object Cache considerations

This plugin uses WordPress `WP_Object_Cache` to optimise the number of database queries, ensuring only the proper amount is fired on each pageload. Because the `WP_Object_Cache` object can be affected by third-party plugins, it is required that such plugins implement the `wp_cache_add_non_persistent_groups` function to avoid side effects.  

See below examples of popular cache plugins compatible with WP Weixin:

* [W3 Total Cache](http://wordpress.org/extend/plugins/w3-total-cache/)
* [APC Object Cache](https://github.com/l3rady/WordPress-APC-Object-Cache) / [APCu Object Cache](https://github.com/l3rady/WordPress-APCu-Object-Cache)
* [Redis Object Cache](https://github.com/ericmann/Redis-Object-Cache) 

## Settings

The following settings can be accessed on the WP Weixin settings page.

### Main Settings

Name                                | Required | Type      | Description                                                                                                                  
----------------------------------- |:--------:|:---------:| ------------------------------------------------------------------------------------------------------------------------------
Enable                              | Yes      | checkbox  | Enable WP Weixin - requires a valid configuration.                                                                            
Wechat App ID                       | Yes      | text      | The AppId in the backend at `https://mp.weixin.qq.com/` under Development > Basic configuration.                              
Wechat App Secret                   | Yes      | text      | The AppSecret in the backend at `https://mp.weixin.qq.com/` under Development > Basic configuration.                          
Wechat OA Name                      | No       | text      | The name of the Official Account (recommended to enter the actual name).                                                      
Wechat OA Logo URL                  | No       | text      | A URL to the logo of the Official Account - (recommended enter the URL of a picture of the actual logo).                      
Enable Wechat mobile authentication | No       | checkbox  | If enabled, users will be authenticated with their wechat account in WordPress (if not, a session cookie `wx_openId` is set). 
Force Wechat mobile                 | No       | checkbox  | Make the website accessible only through the Wechat browser.<br>If accessed with another browser, the page displays a QR code.
Force follow (any page)             | No       | checkbox  | Require the user to follow the Official Account before accessing the site with the Wechat browser.                            


Required settings above are the **minimal configuration** to enable the plugin.

### Wechat Responder Settings

Name                      | Type      | Description                                                                                                                                                                                                                                                                            
------------------------- |:---------:| ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
Use Wechat Responder      | checkbox  | Allow the website to receive messages from Wechat and respond to them. Server configuration must be enabled and configured in `https://mp.weixin.qq.com/` under Development > Basic configuration. Required if using "Force follow" option in the Main Settings or Wechat Pay settings.                                                                                                                                                                                                                                                                                                                 
Wechat Token              | text      | The Token in the backend at `https://mp.weixin.qq.com/` under Development > Basic configuration.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         
Encode messages           | checkbox  | Encode the communication between the website and the Wechat API (recommended).                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           
Wechat AES Key            | text      | The EncodingAESKey in the backend at `https://mp.weixin.qq.com/` under<br/> Development > Basic configuration.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           
Send welcome message      | checkbox  | Send a welcome message when a user follows the Official Account.<br/>The following filters can be used to change the default values of the message:<ul><li>`apply_filters('wp_weixin_follower_welcome_title', string $title, mixed $before_subscription);`</li><li>`apply_filters('wp_weixin_follower_welcome_description', string $description, mixed $before_subscription);`</li><li>`apply_filters('wp_weixin_follower_welcome_url', string $url, mixed $before_subscription);`</li><li>`apply_filters('wp_weixin_follower_welcome_pic_url', string $pic_url, mixed $before_subscription);`</li></ul>
Welcome message image URL | text      | A URL to the image used for the welcome message sent after a user follows the Official Account (external or from the Media Library).<br>Default image is in `/wp-weixin/images/default-welcome.png`.                                                                                                                                                                                                                                                                                                                                                                                                    

### Wechat Pay Settings - PRO

These settings are only available if WP Weixin Pay and/or Woo WechatPay are installed and activated. See [Go PRO!](#user-content-go-pro) for more details.

Name                                | Type      | Description                                                                                                                                                                                                                     | Requirement                             
----------------------------------- |:---------:|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | :--------------------------------------:
Use merchant platform               | checkbox  | Allow users to send money to the Service Account with Wechat - an account at `https://pay.weixin.qq.com/` is necessary. This setting is not configurable (forced to checked and hidden) if Woo WechatPay plugin is activated.   | WP Weixin Pay                           
Custom amount transfer 			    | checkbox  | Allow users to do custom amount transfers and admins to create payment QR Codes.                                          																									      | WP Weixin Pay                           
Force follow (account and checkout) | checkbox  | Require the user to follow the Official Account before accessing the checkout and account pages with the Wechat browser. This setting is only available if WooCommerce is activated.                                            | Woo WechatPay                           
Wechat Merchant App ID              | text      | The AppID in the backend at `https://pay.weixin.qq.com/` - can be different from the Wechat App ID as the Wechat Pay account may be linked to a different AppID. Leave empty to use the Wechat App ID.                          | WP Weixin Pay<br>**or**<br>Woo WechatPay  
Wechat Merchant ID                  | text      | The Merchant ID in the backend at `https://pay.weixin.qq.com/index.php/extend/pay_setting`.                                                                                                                                     | WP Weixin Pay<br>**or**<br>Woo WechatPay  
Wechat Merchant Key                 | text      | The Merchant Key in the backend at `https://pay.weixin.qq.com/`.                                                                                                                                                                | WP Weixin Pay<br>**or**<br>Woo WechatPay  

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
------------------------------------------------ |:--------:| --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
Show Wechat name and pictures in Users list page | checkbox | Override the display of the WordPress account names and avatars.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              
Official Account menu language awareness         | checkbox | Customise the menu of the Official Account depending on user's language. By default, the language of the menu corresponding to the website's default language is used.<br/>This setting is only available if WPML is activated.                                                                                                                                                                                                                                                                                                                                                                                                                                                                               
Use custom persistence for access_token          | checkbox | Use a custom persistence method for the Official Account access_token and its expiry timestamp.<br/>**Warning** - requires the implementation of:<ul><li>`add_filter('wp_weixin_get_access_info', $access_info, 10, 0);`</li><li>`add_action('wp_weixin_save_access_info', $access_info, 10, 1);`</li></ul>The parameter `$access_info` is an array with the keys `token` and `expiry`.<br/>Add the hooks above in a `plugins_loaded` action with a priority of `4` or less.<br/>Useful to avoid a race condition if the access_token information need to be shared between multiple platforms.<br/>When unchecked, access_token & expiry timestamp are stored in the WordPress options table in the database.

## Go PRO!

To integrate Wechat Pay with WordPress, there are several possibilities using exclusive plugins:
* **Use Woo WechatPay** with WP Weixin on a Woocommerce website: Woo WechatPay is a payment gateway for Woocommerce allowing a website to receive payments for orders via Wechat, both on mobile and desktop/laptop. See [Woo WechatPay details](https://anyape.com/woo-wechatpay.html) to see how to get it.
* **Use WP Weixin Pay** with WP Weixin: with this extension, you can receive payments with an emulated "Transfer" screen, without needing any e-commerce plugin. See [WP Weixin Pay details](https://anyape.com/wp-weixin-pay.html) to see how to get it.
* Combine all the plugins!

The combination of WP Weixin, WP Weixin Pay and Woo WechatPay is maybe the best clean, fully documented, i18n-ready, powerful suite of plugins integrating WordPress with Wechat.

## Functions
The functions listed below are made publicly available by the plugin for theme and plugin developers. Although the main classes can theoretically be instanciated without side effect if the `$hook_init` parameter is set to `false`, it is recommended to use only the following functions as there is no guarantee future updates won't introduce changes of behaviors.

```php
wp_weixin_is_wechat();
```  

**Description**  
Wether the visitor is using the Wechat browser.  

**Return value**  
> (bool) true if using the Wechat browser, false otherwise

## Hooks - actions & filters

WP Weixin gives developers the possibilty to customise its behavior with a series of custom actions and filters. 

### Actions

```php
do_action('wp_weixin_responder', array $request_data);
```

**Description**  
Fired after receiving a request from Wechat.  

**Parameters**  
> (array) The data sent in the request from Wechat
___

```php
do_action('wp_weixin_save_access_info', array $access_info);

```

**Description**  
Fired after renewing the Official Account access_token if custom persistence is used. Used to save the access information - particularly useful to avoid a race condition if the access_token needs to be shared between multiple platforms.

**Parameters**  
$access_info
> (array) The access information in an associative array. Keys are `token` and `expiry`.
___

### Filters
```php
apply_filters('wp_weixin_browser_page_qr_src', string $src);
```

**Description**  
Filter the source of the QR code to show on other browsers for a page only accessible through Wechat browser.  

**Parameters**  
$src
> (string) The source of the QR code to show on other browsers - default empty

**Hooked**
WP_Weixin_Auth::get_browser_page_qr_src()
___

```php
apply_filters('wp_weixin_subscribe_src', string $src);
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
apply_filters('wp_weixin_follower_notice_title', string $title);
```

**Description**  
Filter the title of the page displaying the QR code to follow the Official Account.  

**Parameters**  
> (string) The title of the page - default "Follow Us!"
___

```php
apply_filters('wp_weixin_follower_notice', string $notice);
```

**Description**  
Filter the message displayed on the page displaying the QR code to follow the Official Account.  

**Parameters**  
> (string) The displayed message - default "Please scan this QR Code to follow us before accessing this content."
___

```php
apply_filters('wp_weixin_auth_needed', bool $needs_auth);
```

**Description**  
Wether the page needs the user to be authenticated using Wechat. When "Enable Wechat mobile authentication" is checked in the settings, pages need authentication by default, unless they are whitelisted using this filter. By default, all the admin pages are whitelisted and accessible outside Wechat.  

**Parameters**  
$needs_auth
> (bool) true if authentication is needed to visit the page, false otherwise
___

```php
apply_filters('wp_weixin_debug', bool $debug);
```

**Description**  
Filter wether to activate debug mode (php error logs and javascript console message).  

**Parameters**  
$debug
> (bool) true if debug mode is activated, false otherwise - default false
___

```php
apply_filters('wp_weixin_follower_welcome_title', string $title, mixed $before_subscription);
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
apply_filters('wp_weixin_follower_welcome_description', string $description, mixed $before_subscription);
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
apply_filters('wp_weixin_follower_welcome_url', string $url, mixed $before_subscription);
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
apply_filters('wp_weixin_follower_welcome_pic_url', string $pic_url, mixed $before_subscription);
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
apply_filters('wp_weixin_get_access_info', array $access_info);
```

Filters the access_token and expiry when requesting the Wechat object if custom persistence is used - particularly useful to avoid a race condition if the access_token needs to be shared between multiple platforms.

**Parameters**  
$access_info
> (array) The access information in an associative array. Keys are `token` and `expiry`.

___

```php
apply_filters('wp_weixin_jsapi_urls', array $jsapi_urls);
```

Filters the URLs necessary to register on the Wechat merchant account's API configuration screen - particularly useful another plugin implements some sort of custom checkout page with a URL not registered in WooCommerce.

**Parameters**  
$jsapi_urls
> (array) The URLs to register on the Wechat merchant account's API configuration screen.

___

## Templates

The following plugin files are included using `locate_template()` function of WordPress. This means they can be overloaded in the active WordPress theme if a file with the same name exists at the root of the theme.
___

`wp-weixin/inc/templates/wp-weixin-subscribe.php`  

**Description**  
The template of the page displaying the QR code to follow the Official Account.  

**Variables**  
No variable is provided to this template by default: it uses the `wp_weixin_subscribe_src` filter to get the source of the QR code image.

**Associated styles**  
`wp-weixin/css/main.css`  

**Associated scripts**  
None

## Javascript

The global variable `wx` is already properly signed and initialised with the complete `jsApiList`.  
To use it properly, developers must include their scripts with a priority of `6` or more and `wp-weixin-main-script` as a dependency.  

In addition, the following events may be subscribed to.
___
```Javascript
window.wpWeixinShareTimelineSuccessListener(callback);
```

Subscribing to this event will execute the `callback` function when sharing the post on Wechat Moments succeeded.  

**Parameters passed to the callback**  
shareInfo
> (object) The share information sent to the Wechat JS_SDK. Attributes are `title`, `desc`, `link`, `imgUrl`.  
___
```Javascript
window.wpWeixinShareTimelineFailureListener(callback);
```

Subscribing to this event will execute the `callback` function when sharing the post on Wechat Moments failed.  

**Parameters passed to the callback**  
shareInfo
> (object) The share information sent to the Wechat JS_SDK. Attributes are `title`, `desc`, `link`, `imgUrl`.  
___
```Javascript
window.wpWeixinShareAppMessageSuccessListener(callback);`
```

Subscribing to this event will execute the `callback` function when sharing the post with Wechat "Send to chat" succeeded.  

**Parameters passed to the callback**  
shareInfo
> (object) The share information sent to the Wechat JS_SDK. Attributes are `title`, `desc`, `link`, `imgUrl`.  
___
```Javascript
window.wpWeixinShareAppMessageFailureListener(callback);
```

Subscribing to this event will execute the `callback` function when sharing the post with Wechat "Send to chat" failed.  

**Parameters passed to the callback**  
shareInfo
> (object) The share information sent to the Wechat JS_SDK. Attributes are `title`, `desc`, `link`, `imgUrl`.  