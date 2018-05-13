<?php

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit; // Exit if accessed directly
}

global $wpdb;

$option_prefix    = $wpdb->esc_like( '_transient_wp_weixin_' ) . '%';
$transient_prefix = $wpdb->esc_like( 'wp_weixin_' ) . '%';
$sql              = "DELETE FROM $wpdb->options WHERE `option_name` LIKE '%s' OR `option_name` LIKE '%s'";

$wpdb->query( $wpdb->prepare( $sql, $option_prefix . '%', $transient_prefix . '%' ) );// @codingStandardsIgnoreLine
