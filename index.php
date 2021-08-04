<?php
/*
Plugin Name: MF Address Book
Plugin URI: https://github.com/frostkom/mf_address
Description: 
Version: 3.3.8
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://frostkom.se
Text Domain: lang_address
Domain Path: /lang

Depends: MF Base
GitHub Plugin URI: frostkom/mf_address
*/

if(function_exists('is_plugin_active') && is_plugin_active("mf_base/index.php"))
{
	include_once("include/classes.php");
	include_once("include/functions.php");

	load_plugin_textdomain('lang_address', false, dirname(plugin_basename(__FILE__))."/lang/");

	$obj_address = new mf_address();

	add_action('cron_base', 'activate_address', mt_rand(1, 10));
	add_action('cron_base', array($obj_address, 'cron_base'), mt_rand(1, 10));

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

	add_action('wp_login', array($obj_address, 'wp_login'));
	add_action('wp_logout', array($obj_address, 'wp_login'));

	function activate_address()
	{
		global $wpdb;

		$default_charset = (DB_CHARSET != '' ? DB_CHARSET : 'utf8');

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
			addressSyncedDate DATETIME DEFAULT NULL,
			userID INT UNSIGNED DEFAULT NULL,
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

		if(get_option('setting_address_api_url') != '')
		{
			$arr_add_column[get_address_table_prefix()."address"]['addressSyncedDate'] = "ALTER TABLE [table] ADD [column] DATETIME DEFAULT NULL AFTER addressCreated";
		}

		else
		{
			$arr_update_column[get_address_table_prefix()."address"]['addressSyncedDate'] = "ALTER TABLE [table] DROP COLUMN [column]";
		}

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
	}

	function uninstall_address()
	{
		global $obj_address;

		mf_uninstall_plugin(array(
			'uploads' => $obj_address->post_type,
			'options' => array('setting_address_site_wide', 'setting_address_extra', 'setting_address_extra_profile', 'setting_address_display_member_id', 'setting_address_api_url', 'setting_address_debug', 'option_address_api_used'),
			'meta' => array('meta_address_permission'),
			'post_types' => array($obj_address->post_type),
			'tables' => array('address'),
		));
	}
}