<?php
/**
 * Revora Uninstall
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Cleanup settings
delete_option( 'revora_settings' );

// Cleanup database table (Optional - usually better to keep data or have a setting for this)
/*
global $wpdb;
$table_name = $wpdb->prefix . 'revora_reviews';
$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
*/
