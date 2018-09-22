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

WP Weixin enables integration between WordPress and WeChat. It is fully functional as a standalone plugin, and also acts as a core for [Woo WeChatPay](https://anyape.com/woo-wechatpay.html) payment gateway for WooCommerce and [WP Weixin Pay](https://anyape.com/wp-weixin-pay.html) extension. It is also a library for WordPress developers to build their own integration with WeChat.  
It can be used with both Official Subscription Account and Official Service Account.

### Overview

This plugin adds the following major features to WordPress:

* **WP Weixin settings page:** to configure the plugin with an Official Account.
* **WeChat Share:** Share posts and pages on Moments or Send to chat, in a pretty way. Triggers javascript events for developers on success and failure.
* **WeChat JS_SDK:** the `wx` global variable is pre-configured with a signed package to leverage the javascript SDK of WeChat in WordPress themes more easily. 
* **WP Weixin QR code generator:** to create custom codes.
* **WeChat Authentication:** to automatically create and authenticate a user in WordPress.
* **Force WeChat mobile:** to prevent users from browsing the website outside of WeChat. If accessed with an other browser, the page displays a QR code.
* **WeChat Responder:** acts as an API for developers to receive and respond to calls made by WeChat.
* **Force following the Official Account:** to harvest WeChat followers, forcing users to follow the Official Account before accessing the content.
* **Welcome message:** sends a welcome message in WeChat when a user follows the Official Account ; allows to do so with WordPress when the WeChat Responder is enabled.
* **Menu integration:** allows to set the Official Account menus in WordPress when the WeChat Responder is enabled.
* **WordPress Users screen override:** to display WeChat names and WeChat avatars if they exist, instead of the default values in the user screen.

Developers can also build plugins and themes integrated with WeChat with WP Weixin as a core, leveraging its publicly available functions, actions and filters.  

For more information, see [the full documentation](https://github.com/froger-me/wp-weixin).

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the plugin files to the `/wp-content/plugins/wp-weixin` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Edit plugin settings

== Changelog ==

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