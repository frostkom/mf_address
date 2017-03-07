<?php
/*
Plugin Name: MF Address Book
Plugin URI: https://github.com/frostkom/mf_address
Description: 
Version: 2.3.10
Author: Martin Fors
Author URI: http://frostkom.se
Text Domain: lang_address
Domain Path: /lang

GitHub Plugin URI: frostkom/mf_address
*/

include_once("include/classes.php");
include_once("include/functions.php");

add_action('cron_base', 'activate_address', mt_rand(1, 10));

if(is_admin())
{
	register_activation_hook(__FILE__, 'activate_address');
	register_uninstall_hook(__FILE__, 'uninstall_address');

	add_action('init', 'init_address');

	add_action('admin_init', 'settings_address');
	add_action('admin_menu', 'menu_address');
	add_action('show_user_profile', 'show_profile_address');
	add_action('edit_user_profile', 'show_profile_address');
	add_action('personal_options_update', 'save_portfolio_address');
	add_action('edit_user_profile_update', 'save_portfolio_address');
	add_action('deleted_user', 'deleted_user_address');
}

add_action('wp_logout', 'uninit_address');
add_action('wp_login', 'uninit_address');

load_plugin_textdomain('lang_address', false, dirname(plugin_basename(__FILE__)).'/lang/');

function activate_address()
{
	global $wpdb;

	$default_charset = DB_CHARSET != '' ? DB_CHARSET : "utf8";

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->base_prefix."address (
		addressID INT unsigned NOT NULL AUTO_INCREMENT,
		addressPublic ENUM('0','1') NOT NULL DEFAULT '1',
		addressError INT unsigned NOT NULL DEFAULT '0',
		addressMemberID INT unsigned NOT NULL DEFAULT '0',
		addressBirthDate VARCHAR(12) DEFAULT NULL,
		addressFirstName VARCHAR(25) DEFAULT NULL,
		addressSurName VARCHAR(25) DEFAULT NULL,
		addressCo VARCHAR(30) DEFAULT NULL,
		addressAddress VARCHAR(60) DEFAULT NULL,
		addressZipCode INT unsigned DEFAULT NULL,
		addressCity VARCHAR(100),
		addressTelNo VARCHAR(13) DEFAULT NULL,
		addressWorkNo VARCHAR(13) DEFAULT NULL,
		addressCellNo VARCHAR(13) DEFAULT NULL,
		addressEmail VARCHAR(60) DEFAULT NULL,
		addressExtra VARCHAR(100),
		addressCreated DATETIME DEFAULT NULL,
		userID INT unsigned NOT NULL DEFAULT '0',
		addressDeleted ENUM('0','1') NOT NULL DEFAULT '0',
		addressDeletedDate DATETIME DEFAULT NULL,
		addressDeletedID INT unsigned DEFAULT NULL,
		PRIMARY KEY (addressID),
		KEY userID (userID)
	) DEFAULT CHARSET=".$default_charset);

	$arr_update_tables = array();

	$arr_update_tables[$wpdb->base_prefix."address"]['addressCity'] = "ALTER TABLE [table] ADD [column] VARCHAR(100) AFTER addressZipCode";
	$arr_update_tables[$wpdb->base_prefix."address"]['addressExtra'] = "ALTER TABLE [table] ADD [column] VARCHAR(100) AFTER addressEmail";
	$arr_update_tables[$wpdb->base_prefix."address"]['addressError'] = "ALTER TABLE [table] ADD [column] INT unsigned NOT NULL DEFAULT '0' AFTER addressPublic";

	add_columns($arr_update_tables);

	delete_base(array(
		'table' => "address",
		'field_prefix' => "address",
		'child_tables' => array(
			'group_queue' => array(
				'action' => "delete",
				'field_prefix' => "address",
			),
			'address2group' => array(
				'action' => "delete",
				'field_prefix' => "address",
			),
		),
	));
}

function uninstall_address()
{
	mf_uninstall_plugin(array(
		'options' => array('setting_address_extra', 'setting_show_member_id'),
		'tables' => array('address'),
	));
}