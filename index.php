<?php
/*
Plugin Name: MF Address Book
Plugin URI: https://github.com/frostkom/mf_address
Description: 
Version: 2.6.13
Licence: GPLv2 or later
Author: Martin Fors
Author URI: http://frostkom.se
Text Domain: lang_address
Domain Path: /lang

Depends: MF Base
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

add_action('wp_login', 'uninit_address');
add_action('wp_logout', 'uninit_address');

load_plugin_textdomain('lang_address', false, dirname(plugin_basename(__FILE__)).'/lang/');

function activate_address()
{
	global $wpdb;

	$default_charset = DB_CHARSET != '' ? DB_CHARSET : "utf8";

	$arr_add_column = $arr_update_column = $arr_add_index = array();

	$wpdb->query("CREATE TABLE IF NOT EXISTS ".get_address_table_prefix()."address (
		addressID INT UNSIGNED NOT NULL AUTO_INCREMENT,
		addressPublic ENUM('0','1') NOT NULL DEFAULT '1',
		addressError INT UNSIGNED NOT NULL DEFAULT '0',
		addressMemberID INT UNSIGNED NOT NULL DEFAULT '0',
		addressBirthDate VARCHAR(12) DEFAULT NULL,
		addressFirstName VARCHAR(25) DEFAULT NULL,
		addressSurName VARCHAR(25) DEFAULT NULL,
		addressCo VARCHAR(30) DEFAULT NULL,
		addressAddress VARCHAR(60) DEFAULT NULL,
		addressZipCode MEDIUMINT UNSIGNED DEFAULT NULL,
		addressCity VARCHAR(100),
		addressCountry TINYINT UNSIGNED DEFAULT NULL,
		addressTelNo VARCHAR(13) DEFAULT NULL,
		addressWorkNo VARCHAR(13) DEFAULT NULL,
		addressCellNo VARCHAR(13) DEFAULT NULL,
		addressEmail VARCHAR(60) DEFAULT NULL,
		addressExtra VARCHAR(100),
		addressCreated DATETIME DEFAULT NULL,
		userID INT UNSIGNED NOT NULL DEFAULT '0',
		addressDeleted ENUM('0','1') NOT NULL DEFAULT '0',
		addressDeletedDate DATETIME DEFAULT NULL,
		addressDeletedID INT UNSIGNED DEFAULT NULL,
		PRIMARY KEY (addressID),
		KEY userID (userID),
		KEY addressDeleted (addressDeleted)
	) DEFAULT CHARSET=".$default_charset);

	$arr_add_column[get_address_table_prefix()."address"] = array(
		'addressCity' => "ALTER TABLE [table] ADD [column] VARCHAR(100) AFTER addressZipCode",
		'addressExtra' => "ALTER TABLE [table] ADD [column] VARCHAR(100) AFTER addressEmail",
		'addressError' => "ALTER TABLE [table] ADD [column] INT UNSIGNED NOT NULL DEFAULT '0' AFTER addressPublic",
		'addressCountry' => "ALTER TABLE [table] ADD [column] TINYINT UNSIGNED DEFAULT NULL AFTER addressCity",
	);

	$arr_update_column[get_address_table_prefix()."address"] = array(
		'addressZipCode' => "ALTER TABLE [table] CHANGE [column] [column] MEDIUMINT UNSIGNED DEFAULT NULL",
	);

	$arr_add_index[get_address_table_prefix()."address"] = array(
		'addressDeleted' => "ALTER TABLE [table] ADD INDEX [column] ([column])",
	);

	update_columns($arr_update_column);
	add_columns($arr_add_column);
	add_index($arr_add_index);

	delete_base(array(
		'table_prefix' => get_address_table_prefix(),
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

	replace_user_meta(array('old' => 'profile_address_permission', 'new' => 'meta_address_permission'));
}

function uninstall_address()
{
	mf_uninstall_plugin(array(
		'options' => array('setting_address_extra', 'setting_show_member_id'),
		'meta' => array('meta_address_permission'),
		'post_types' => array('mf_address'),
		'tables' => array('address'),
	));
}