<?php
/*
Plugin Name: MF Address Book
Plugin URI: https://github.com/frostkom/mf_address
Description: 
Version: 2.4.1
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

	$arr_add_column = $arr_add_index = array();

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
		KEY userID (userID),
		KEY addressDeleted (addressDeleted)
	) DEFAULT CHARSET=".$default_charset);

	$arr_add_column[$wpdb->base_prefix."address"] = array(
		'addressCity' => "ALTER TABLE [table] ADD [column] VARCHAR(100) AFTER addressZipCode",
		'addressExtra' => "ALTER TABLE [table] ADD [column] VARCHAR(100) AFTER addressEmail",
		'addressError' => "ALTER TABLE [table] ADD [column] INT unsigned NOT NULL DEFAULT '0' AFTER addressPublic",
	);

	$arr_add_index[$wpdb->base_prefix."address"] = array(
		'addressDeleted' => "ALTER TABLE [table] ADD INDEX [column] ([column])",
	);

	add_columns($arr_add_column);
	add_index($arr_add_index);

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

	//Remove duplicates
	if(1 == 1)
	{
		$result = $wpdb->get_results("SELECT addressID, addressBirthDate, addressFirstName, addressSurName, addressEmail, SUBSTRING(addressBirthDate, 1, 10) AS birthDate, COUNT(addressID) AS addressAmount FROM ".$wpdb->base_prefix."address WHERE addressPublic = '1' AND addressDeleted = '0' AND addressBirthDate IS NOT NULL GROUP BY (birthDate) ORDER BY addressAmount DESC");

		$i = 0;

		foreach($result as $r)
		{
			$arr_address = array();

			$intAddressID = $r->addressID;

			$wpdb->get_results($wpdb->prepare("SELECT groupID FROM ".$wpdb->base_prefix."address2group WHERE addressID = '%d'", $intAddressID));

			$arr_address[$intAddressID] = array(
				'birthdate' => $r->addressBirthDate,
				'name' => $r->addressFirstName." ".$r->addressSurName,
				'email' => $r->addressEmail,
				'groups' => $wpdb->num_rows,
			);

			$strBirthDate = $r->birthDate;
			$intAddressAmount = $r->addressAmount;

			if($intAddressAmount > 1)
			{
				if($i < 20)
				{
					$result2 = $wpdb->get_results($wpdb->prepare("SELECT addressID, addressBirthDate, addressFirstName, addressSurName, addressEmail FROM ".$wpdb->base_prefix."address WHERE addressPublic = '1' AND addressDeleted = '0' AND addressBirthDate LIKE %s AND addressID != '%d'", $arr_address[$intAddressID]['birthdate']."%", $intAddressID));

					foreach($result2 as $r)
					{
						$intAddressID_2 = $r->addressID;

						$wpdb->get_results($wpdb->prepare("SELECT groupID FROM ".$wpdb->base_prefix."address2group WHERE addressID = '%d'", $intAddressID_2));

						$arr_address[$intAddressID]['not_id'] = $r->addressID;

						$arr_address[$intAddressID_2] = array(
							'not_id' => $intAddressID,
							'birthdate' => $r->addressBirthDate,
							'name' => $r->addressFirstName." ".$r->addressSurName,
							'email' => $r->addressEmail,
							'groups' => $wpdb->num_rows,
						);

						if($arr_address[$intAddressID]['name'] == $arr_address[$intAddressID_2]['name'] || $arr_address[$intAddressID]['email'] == $arr_address[$intAddressID_2]['email'])
						{
							$intAddressID_birthdate = strlen($arr_address[$intAddressID_2]['birthdate']) > strlen($arr_address[$intAddressID]['birthdate']) ? $intAddressID_2 : $intAddressID;
							$intAddressID_group = $arr_address[$intAddressID_2]['groups'] > $arr_address[$intAddressID]['groups'] ? $intAddressID_2 : $intAddressID;

							if($intAddressID_birthdate == $intAddressID_group)
							{
								$not_id = $arr_address[$intAddressID_birthdate]['not_id'];

								$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."address SET addressDeleted = '1', addressDeletedDate = NOW() WHERE addressPublic = '1' AND addressDeleted = '0' AND addressID = '%d'", $not_id));

								if($wpdb->rows_affected > 0)
								{
									do_log(sprintf("Kept %d (%s, %d) but removed %d (%s, %d)", $intAddressID_birthdate, $arr_address[$intAddressID_birthdate]['birthdate'], $arr_address[$intAddressID_birthdate]['groups'], $not_id, $arr_address[$not_id]['birthdate'], $arr_address[$not_id]['groups']));
								}
							}

							else
							{
								$not_id = $arr_address[$intAddressID_birthdate]['not_id'];

								$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."address SET addressBirthDate = %s WHERE addressPublic = '1' AND addressDeleted = '0' AND addressID = '%d'", $arr_address[$intAddressID_birthdate]['birthdate'], $not_id));

								if($wpdb->rows_affected > 0)
								{
									do_log(sprintf("Updated BirthDate on %d (%s -> %s)", $not_id, $arr_address[$not_id]['birthdate'], $arr_address[$intAddressID_birthdate]['birthdate']));
								}
							}
						}

						$i++;
					}
				}

				else
				{
					break;
				}
			}
		}
	}
}

function uninstall_address()
{
	mf_uninstall_plugin(array(
		'options' => array('setting_address_extra', 'setting_show_member_id'),
		'post_types' => array('mf_address'),
		'tables' => array('address'),
	));
}