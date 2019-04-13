<?php

if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
global $tadn_jal_db_version;

$table_name = $wpdb->prefix . 'wallet_address';

$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE IF NOT EXISTS $table_name (
	id int(11) NOT NULL AUTO_INCREMENT,
	time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	address varchar(200) NOT NULL,
	label varchar(50) NOT NULL,
	status int(11) NOT NULL,
	PRIMARY KEY  (id)
) $charset_collate;";

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
dbDelta( $sql );

add_option( 'tadn_jal_db_version', $tadn_jal_db_version );

$table_name = $wpdb->prefix . 'adsegment';
		
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE IF NOT EXISTS $table_name (
	id int(11) NOT NULL AUTO_INCREMENT,
	time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
	address_id int(11) NOT NULL,
	status int(11) NOT NULL,
	size_id int(11) NOT NULL,
	ad_segment_name varchar(256) NOT NULL,
	PRIMARY KEY  (id)
) $charset_collate;";

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
dbDelta( $sql );

add_option( 'tadn_jal_db_version', $tadn_jal_db_version );