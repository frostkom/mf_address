<?php
/*
Plugin Name: MF Address Book
Plugin URI: https://github.com/frostkom/mf_address
Description: Add support for an address book
Version: 3.6.8
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://martinfors.se
Text Domain: lang_address
Domain Path: /lang
*/

if(!function_exists('is_plugin_active') || function_exists('is_plugin_active') && is_plugin_active("mf_base/index.php"))
{
	include_once("include/classes.php");

	$obj_address = new mf_address();

	add_action('cron_base', 'activate_address', 1);
	add_action('cron_base', array($obj_address, 'cron_base'), mt_rand(2, 10));

	if(is_admin())
	{
		register_activation_hook(__FILE__, 'activate_address');
		register_uninstall_hook(__FILE__, 'uninstall_address');

		add_action('init', array($obj_address, 'init'));

		add_action('admin_init', array($obj_address, 'settings_address'));
		add_action('admin_menu', array($obj_address, 'admin_menu'));

		add_action('show_user_profile', array($obj_address, 'edit_user_profile'));
		add_action('edit_user_profile', array($obj_address, 'edit_user_profile'));
		add_action('profile_update', array($obj_address, 'profile_update'));

		add_action('deleted_user', array($obj_address, 'deleted_user'));

		add_action('restrict_manage_posts', array($obj_address, 'restrict_manage_posts'));

		add_filter('wp_privacy_personal_data_exporters', array($obj_address, 'wp_privacy_personal_data_exporters'), 10);
		add_filter('wp_privacy_personal_data_erasers', array($obj_address, 'wp_privacy_personal_data_erasers'), 10);
	}

	else
	{
		add_filter('filter_profile_fields', array($obj_address, 'filter_profile_fields'));
	}

	function activate_address()
	{
		global $wpdb;

		include_once("include/classes.php");

		$obj_address = new mf_address();

		$default_charset = (DB_CHARSET != '' ? DB_CHARSET : 'utf8');

		$arr_add_column = $arr_update_column = $arr_add_index = [];

		$wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."address (
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
			addressSyncedDate DATETIME DEFAULT NULL,
			userID INT UNSIGNED DEFAULT NULL,
			addressDeleted ENUM('0','1') NOT NULL DEFAULT '0',
			addressDeletedDate DATETIME DEFAULT NULL,
			addressDeletedID INT UNSIGNED DEFAULT NULL,
			PRIMARY KEY (addressID),
			KEY userID (userID),
			KEY addressDeleted (addressDeleted)
		) DEFAULT CHARSET=".$default_charset);

		$arr_add_column[$wpdb->prefix."address"]['addressSyncedDate'] = "ALTER TABLE [table] ADD [column] DATETIME DEFAULT NULL AFTER addressCreated";

		update_columns($arr_update_column);
		add_columns($arr_add_column);
		add_index($arr_add_index);
	}

	function uninstall_address()
	{
		include_once("include/classes.php");

		$obj_address = new mf_address();

		mf_uninstall_plugin(array(
			'uploads' => $obj_address->post_type,
			'options' => array('setting_address_site_wide', 'setting_address_extra', 'setting_address_extra_field', 'setting_address_extra_profile', 'setting_address_display_member_id', 'setting_address_api_url', 'setting_address_debug', 'option_address_api_full_used', 'option_address_api_full_next', 'option_address_api_used', 'option_address_api_next'),
			'user_meta' => array('meta_address_permission'),
			'post_types' => array($obj_address->post_type),
			'tables' => array('address'),
		));
	}
}