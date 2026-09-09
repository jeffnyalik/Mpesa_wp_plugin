<?php
/**
 * Fired when the plugin is deleted from wp-admin (not on deactivate).
 *
 * @package Plug_One
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

delete_option( 'woocommerce_plug_one_mpesa_settings' );
delete_option( 'plug_one_db_version' );
delete_transient( 'plug_one_mpesa_token_' );

$table = $wpdb->prefix . 'plug_one_transactions';
$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
