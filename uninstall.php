<?php

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit; // Exit if accessed directly
}

global $wpdb;

if ( is_multisite() ) {
	$sql      = "DELETE FROM $wpdb->sitemeta WHERE `meta_key` LIKE 'wp_weixin%'";
	$blog_ids = array_map( function( $site ) {

		return absint( $site->blog_id );
	}, get_sites() );

	$wpdb->query( $sql ); // @codingStandardsIgnoreLine
} else {
	$blog_ids = array( get_current_blog_id() );
}

foreach ( $blog_ids as $blog_id ) {

	if ( is_multisite() ) {
		switch_to_blog( $blog_id );
	}

	$sql = "DELETE FROM $wpdb->options WHERE `option_name` LIKE '_transient_wp_weixin_%' OR `option_name` LIKE 'wp_weixin_%'";

	$wpdb->query( $wpdb->prepare( $sql ) ); // @codingStandardsIgnoreLine

	if ( is_multisite() ) {
		restore_current_blog();
	}
}

$sql = "DELETE FROM $wpdb->usermeta WHERE `meta_key` LIKE 'wx_openid%' OR `meta_key` IN ( 'wx_unionid', 'wp_weixin_rawdata' )";

$wpdb->query( $sql ); // @codingStandardsIgnoreLine
