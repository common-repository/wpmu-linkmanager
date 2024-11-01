<?php
/**
 * If the admin uninstall this plugin over the uninstall routine from wp, do
 * the follow actions to erase all plugin data restless from wp-installation.
 *
 * @author		WPler <plugins@wpler.com>
 * @version		1.0
 * @copyright	2012 WPler <http://www.wpler.com>
 */

if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

if ( $wpdb->query( "SHOW TABLES LIKE '{$wpdb->base_prefix}wplerlm'" ) == "1" )
	$wpdb->query( "DROP TABLE {$wpdb->base_prefix}wplerlm" );
