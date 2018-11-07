=== WP Weixin ===
Contributors: frogerme
Tags: wechat
Requires at least: 4.9.5
Tested up to: 4.9.5
Stable tag: trunk
Requires PHP: 7.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

WordPress WeChat integration

== Description ==

WP Weixin enables integration between WordPress and WeChat. It is fully functional as a standalone plugin, and acts as a core for [Woo WeChatPay](https://wordpress.org/plugins/woo-wechatpay) payment gateway for WooCommerce and [WP Weixin Pay](https://wordpress.org/plugins/wp-weixin-pay) money transfer extension.

### Important notes

* Although the plugin does provide really useful functionalities out of the box, such as WeChat authentication and Official Account menu integration, it really shines when used by developers to extend its functionalities (mainly through the pre-initialised JS SDK, the WeChat Responder, and various actions and filters).
* The plugin is to be used with a China Mainland WeChat Official Account (Subscription or Service - Service is required if used with companion plugins dealing with payments).
* Make sure to read the "TROUBLESHOOT, FEATURE REQUESTS AND 3RD PARTY INTEGRATION" section below and [the full documentation](https://github.com/froger-me/wp-weixin) before contacting the author.

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

### Multisite

WP Weixin supports multisite installs of WordPress, wether using subdomains or subdirectories. WP Weixin needs to be configured with the same settings and enabled on all the blogs where authentication is needed for a given Official Account.
With WeChat mobile authentication enabled, users visiting one of the blogs are automatically registered to the network, and added to the visited blog with the blog's default user role.
Users are also automatically added to other blogs of the network upon visit when already logged in on one of the blogs (the behavior can be changed with the `wp_weixin_ms_auto_add_to_blog` filter, for example if some of the blogs do not accept pre-authenticated WeChat users).
When using a domain-based network of blogs, the main blog's subdomain is used for cross-domain authentication (the behavior can be changed with the filter `wp_weixin_ms_auth_blog_id` if another domain is registered in the WeChat Official Account's backend).
Because WP Weixin's settings page can be edited for each blog of a network, it is possible to use the plugin with mutliple Official Accounts.

### Troubleshoot, feature requests and 3rd party integration

WP Weixin and its companion plugins are provided for free.  

WP Weixin is regularly updated, and bug reports are welcome, preferably on [Github](https://github.com/froger-me/wp-weixin/issues). Each bug report will be addressed in a timely manner, but issues reported on WordPress may take significantly longer to receive a response.  

Wp Weixin and all the companion plugins have been tested with the latest version of WordPress and WooCommerce - in case of issue, please ensure you are able to reproduce it with a default installation of WordPress, WooCommerce plugin, and Storefront theme before reporting a bug.  

Feature requests ("it would be nice to have XYZ") or 3rd party integration requests (such as "it is not working with XYZ plugin" or "it is not working with my theme") for WP Weixin and all its companion plugins will be considered only after receiving a WeChat red envelope (红包) of a minimum RMB 500 on WeChat. 

To add the author on WeChat, click [here](https://froger.me/wp-content/uploads/2018/04/wechat-qr.png), scan the WeChat QR code, and add "WP Weixin" as a comment in your contact request.  

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin files to the `/wp-content/plugins/wp-weixin` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Edit plugin settings

== Changelog ==

= 1.2.2 =
* Fixed bug redirecting to posts list after first authentication
* Do authentication in `wp_loaded` instead of `template_redirect`
* WeChat Pay Settings: add PEM certificates fields
* SDK: add `public function cert_files_exist()`
* SDK: fix refund method
* SDK: fix payment request parsing method
* Update documentation

= 1.2.1 =
* Better error log
* Fix persistent cache handling

= 1.2 =
* Optimized rewrite rules registration
* Multisite support with cross-domain authentication
* Fix unnecessary redirect when visiting QR code auth the first time
* Better compatibility with WPML and WooCommerce
* Better compatibility with Open Social

= 1.1.2 =
* Adjust hooks priorities and condition for authentication hooks registration
* Do not require mobile authentication for ajax calls by default (can be altered with `wp_weixin_auth_needed` filter hook)

= 1.1.1 =
* Proper 401 error if the server signature is not valid when visiting the WeChat Responder endpoint
* Fix menu integration - make sure all types of button can be configured
* Make sure authentication hooks registration is done only when necessary
* WeChat SDK: fix media upload methods
* WeChat SDK: add image response type

= 1.1 =
* Add WeChat authentication for browsers by using temporary, secure QR codes (social login)
* Cleanup and minor refactor
* Added 2 functions, 4 filters, 3 templates

= 1.0.4 =
* Add transient expiry to avoid deadlocks on somehow corrupted databases
* Add possibility to get WordPress users by openid and unionid

= 1.0.3 =
* Ensure compatibility with [Open Social](https://wordpress.org/plugins/open-social/)
* Improve formatting

= 1.0.2 =
* Adjust hooks priorities
* Add Chinese translation

= 1.0.1 =
* Fix activation settings issue

= 1.0 =
* First version