=== WP Weixin ===
Contributors: frogerme
Tags: wechat, wechat share, 微信, 微信分享, 微信公众号
Requires at least: 4.9.5
Tested up to: 5.0
Stable tag: trunk
Requires PHP: 7.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

WordPress WeChat integration

== Description ==

WP Weixin provides integration between WordPress and WeChat. Register or authenticate users automatically in WeChat browser, use WeChat to create an account or authenticate on computers by scanning a QR code with WeChat, share posts in WeChat Moments and conversations or extend the plugin for more features!

### Important notes

* Requires a [China Mainland WeChat Official Account](https://mp.weixin.qq.com) (Subscription or Service - Service is required if used with companion plugins dealing with payments).
* A domain used by WordPress **must** be [registered in an Official Account's backend](https://github.com/froger-me/wp-weixin/blob/master/README.md#user-content-registering-a-domain-in-the-official-accounts-backend).
* The plugin itself does not require programming knowledge, and provides really useful functionalities out of the box. Where it really shines though is when used by developers to extend its functionalities (mainly through the pre-initialised JS SDK, the WeChat Responder, and various provided [functions](https://github.com/froger-me/wp-weixin/blob/master/README.md#user-content-functions), [actions](https://github.com/froger-me/wp-weixin/blob/master/README.md#user-content-actions) and [filters](https://github.com/froger-me/wp-weixin/blob/master/README.md#user-content-filters)).
* Make sure to read the "TROUBLESHOOT, FEATURE REQUESTS AND 3RD PARTY INTEGRATION" section below and [the full documentation](https://github.com/froger-me/wp-weixin/blob/master/README.md) before contacting the author.

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

Compatible with [WooCommerce](https://wordpress.org/plugins/woocommerce/), [WooCommerce Multilingual](https://wordpress.org/plugins/woocommerce-multilingual/), [WPML](http://wpml.org/), [Ultimate Member](https://wordpress.org/plugins/ultimate-member/), [WordPress Multisite](https://codex.wordpress.org/Create_A_Network), and [many caching plugins](https://github.com/froger-me/wp-weixin/blob/master/README.md#user-content-object-cache-considerations).

### Companion Plugins

* [Woo WeChatPay](https://wordpress.org/plugins/woo-wechatpay): a payment gateway for WooCommerce.
* [WP Weixin Pay](https://wordpress.org/plugins/wp-weixin-pay): an extension to enable money transfers to an Official Account.

Developers are encouraged to build plugins and themes integrated with WeChat with WP Weixin as a core, leveraging its publicly available [functions](https://github.com/froger-me/wp-weixin/blob/master/README.md#user-content-functions), [actions](https://github.com/froger-me/wp-weixin/blob/master/README.md#user-content-actions) and [filters](https://github.com/froger-me/wp-weixin/blob/master/README.md#user-content-filters), or directly [make use of the provided SDK](https://github.com/froger-me/wp-weixin/blob/master/README.md#user-content-wechat-sdk).  

*If you wish to see your plugin added to this list, please [contact the author](https://froger.me/wp-content/uploads/2018/04/wechat-qr.png).*

### Advanced - Multisite

WP Weixin supports multisite installs of WordPress, wether using domain/subdomains or subdirectories. It can even support multiple Official Accounts, provided the proper filters are implemented. For more information, see [a more extensive description of the multisite settings](https://github.com/froger-me/wp-weixin/blob/master/README.md#user-content-multisite-settings), and the [Multisite section of the documentation](https://github.com/froger-me/wp-weixin/blob/master/README.md#user-content-multisite).

Unlike some plugins (commercial, obfuscated, and with dubious security standards), WP Weixin does not and will not rely on a crossdomain script dumped at the root of WordPress, but prefers to leverage the standard WordPress functions, actions and filters. 

### Troubleshoot, feature requests and 3rd party integration

Unlike most WeChat integration plugins, WP Weixin and its companion plugins published by the same author are provided for free.  

WP Weixin is regularly updated, and bug reports are welcome, preferably on [Github](https://github.com/froger-me/wp-weixin/issues). Each bug report will be addressed in a timely manner, but issues reported on WordPress may take significantly longer to receive a response.  

WP Weixin and all the companion plugins have been tested with the latest version of WordPress - in case of issue, please ensure you are able to reproduce it with a default installation of WordPress, Storefront theme if WooCommerce is active, and any of the aforementioned supported plugins if used before reporting a bug.  

Feature requests (such as "it would be nice to have XYZ") or 3rd party integration requests (such as "it is not working with XYZ plugin" or "it is not working with my theme") for WP Weixin and all its companion plugins will be considered only after receiving a red envelope (红包) of a minimum RMB 500 on WeChat (guarantee of best effort, no guarantee of result). 

To add the author on WeChat, click [here](https://froger.me/wp-content/uploads/2018/04/wechat-qr.png), scan the WeChat QR code, and add "WP Weixin" as a comment in your contact request.  

== Upgrade Notice ==

* Make sure to backup your database if you plan to go back to v1.2 of WP Weixin.
* Make sure to update all the companion plugins to their latest version after WP Weixin has been updated.
* Make sure to flush the permalinks by visiting Settings > Permalinks (`wp-admin/options-permalink.php`) after WP Weixin and its companion plugins have been update (if Multisite, on all the blogs where the plugin is active).
* Make sure to flush all the cache if an Object Cache plugin is used (Redis, W3 Total Cache, APC Object Cache, ...) after WP Weixin and its companion plugins have been update.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin files to the `/wp-content/plugins/wp-weixin` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Edit plugin settings

== Screenshots ==
 
1. The main settings to integrate WordPress with WeChat.
2. The WeChat Responder settings (for developers) and the proxy settings (beta).
3. Other settings.
4. The screen shown in classic browser when "Force WeChat mobile" is checked in the main settings.
5. The welcome message sent to a new subscriber when the WeChat Responder is active (customisable via filters).
6. The list of users displaying WeChat names and WeChat avatars if they exist, instead of the default values.
7. The default WordPress login form with WeChat QR code authentication link.
8. The WeChat QR code authentication page.
9. The public WeChat account informaton displayed on the default WordPress account page.
10. The page to bind a WordPress user acount with a WeChat account.
11. The page to unbind a WordPress user acount with a WeChat account.
12. The default WooCommerce login form with WeChat QR code authentication link (similar on Ultimate Member login form).
13. The default WooCommerce account page with WeChat account binding link (similar on Ultimate Member account page).
14. The public WeChat account informaton and WeChat account unbinding link displayed on the default WooCommerce account page (similar on Ultimate Member account page).
15. The WP Weixin QR code generator.
16. The interface to setup the WeChat Official Account menu when the WeChat Responder is active.

== Changelog ==

= 1.3.2 =
* Add server logs when user creation failed

= 1.3.1 =
* Replace `current_time( 'timestamp' )` by `time()` as per [WordPress trac ticket](https://core.trac.wordpress.org/ticket/40657)
* Update requirements

= 1.3 =
* Major overall code refactor
* File include optimisation
* Do authentication in `wp` (breaks multisite if done in `wp_loaded`)
* Add WeChat account binding from desktop, compatible WooCommerce and Ultimate Member out of the box ; may be integrated with any membership/account/profile plugin using provided functions and action & filter hooks
* Add links to WeChat authentication in classic browsers on login forms, compatible WooCommerce and Ultimate Member out of the box ; may be integrated with any membership/account/profile plugin using provided functions and action & filter hooks
* Add 11 new publicly available functions safe to use for developers
* Add 26 action hooks for integration with the WP Weixin settings page, integration with membership/account/profile plugins, better integration with WeChat Pay, and build companion plugins
* Add 8 filter hooks for better integration with WeChat Pay, customise the settings, manage users, and overload templates
* Add 3 templates related to WeChat account binding
* Support query variables in the URLs while doing authentication
* Sensitive information in settings visible only when field is focused
* SDK: add `extend` parameter to `refundOrder` - if string, value is attributed to `refund_desc`
* Improve the UI
* Update documentation - added "WeChat SDK" section, "Multisite" section, new functions and new hooks
* Update translation

Special thanks:

* Thanks @alexlii for extensive testing, translation, suggestions and donation!
* Thanks @lssdo for translation
* Thanks @kzgzs for improvement suggestions

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
* Do not require mobile authentication for ajax calls by default (can be altered with [wp_weixin_auth_needed](https://github.com/froger-me/wp-weixin/#user-content-wp_weixin_auth_needed) filter hook)

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