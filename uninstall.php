<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;
$prefix = $wpdb->prefix;
$wpdb->query( "DROP TABLE IF EXISTS {$prefix}ad_appointments" );
$wpdb->query( "DROP TABLE IF EXISTS {$prefix}ad_customers" );
$wpdb->query( "DROP TABLE IF EXISTS {$prefix}ad_cards" );

