<?php

function get_address_table_prefix()
{
	global $wpdb;

	$setting_address_site_wide = get_site_option('setting_address_site_wide', 'yes');

	return $setting_address_site_wide == 'yes' ? $wpdb->base_prefix : $wpdb->prefix;
}

function deleted_user_address($user_id)
{
	global $wpdb;

	$wpdb->query($wpdb->prepare("UPDATE ".get_address_table_prefix()."address SET userID = '%d' WHERE userID = '%d'", get_current_user_id(), $user_id));
}

function init_address()
{
	if(!session_id())
	{
		@session_start();
	}

	$labels = array(
		'name' => _x(__("Address Book", 'lang_address'), 'post type general name'),
		'singular_name' => _x(__("Address Book", 'lang_address'), 'post type singular name'),
		'menu_name' => __("Address Book", 'lang_address')
	);

	$args = array(
		'labels' => $labels,
		'public' => false,
		'exclude_from_search' => true,
	);

	register_post_type('mf_address', $args);
}

function uninit_address()
{
	@session_destroy();
}

function settings_address()
{
	$options_area = __FUNCTION__;

	add_settings_section($options_area, "", $options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array();

	if(IS_SUPER_ADMIN && is_multisite())
	{
		$arr_settings['setting_address_site_wide'] = __("Use Master Table on All Sites", 'lang_address');
	}

	$arr_settings['setting_address_extra'] = __("Name for Extra Address Field", 'lang_address');
	$arr_settings['setting_address_extra_profile'] = __("Display Settings for Extra in Profile", 'lang_address');
	$arr_settings['setting_show_member_id'] = __("Display Member ID", 'lang_address');

	show_settings_fields(array('area' => $options_area, 'settings' => $arr_settings));
}

function settings_address_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);

	echo settings_header($setting_key, __("Address Book", 'lang_address'));
}

function setting_address_site_wide_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	settings_save_site_wide($setting_key);
	$option = get_site_option($setting_key, 'yes');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
}

function setting_show_member_id_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, 'yes');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
}

function setting_address_extra_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option));
}

function setting_address_extra_profile_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, 'yes');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
}

function menu_address()
{
	$menu_root = 'mf_address/';
	$menu_start = $menu_root."list/index.php";
	$menu_capability = "edit_posts";

	$menu_title = __("Address Book", 'lang_address');
	add_menu_page("", $menu_title, $menu_capability, $menu_start, '', 'dashicons-email-alt', 99);

	$menu_title = __("List", 'lang_address');
	add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_start);

	$menu_title = __("Add New", 'lang_address');
	add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_root."create/index.php");

	$menu_capability = "edit_pages";

	$menu_title = __("Import", 'lang_address');
	add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_root."import/index.php");
}

function show_profile_address($user)
{
	global $wpdb;

	if(IS_ADMIN && get_option_or_default('setting_address_extra_profile', 'yes') == 'yes')
	{
		$meta_address_permission = get_user_meta($user->ID, 'meta_address_permission', true);

		$result = $wpdb->get_results("SELECT addressExtra FROM ".get_address_table_prefix()."address WHERE addressExtra != '' GROUP BY addressExtra");

		if($wpdb->num_rows > 0)
		{
			echo "<table class='form-table'>
				<tr class='user-address-permission-wrap'>
					<th><label for='meta_address_permission'>".__("Address Permissions to Users", 'lang_address').":</label></th>
					<td>";

						$meta_address_permission = get_user_meta($user->ID, 'meta_address_permission', true);
						$meta_address_permission = explode(",", $meta_address_permission);

						$arr_data = array();

						foreach($result as $r)
						{
							$strTableValue = $r->addressExtra;

							if($strTableValue != '')
							{
								$arr_data[$strTableValue] = $strTableValue;
							}
						}

						echo show_select(array('data' => $arr_data, 'name' => 'meta_address_permission[]', 'value' => $meta_address_permission))
					."</td>
				</tr>
			</table>";
		}
	}
}

function save_portfolio_address($user_id)
{
	if(IS_ADMIN && get_option_or_default('setting_address_extra_profile', 'yes') == 'yes')
	{
		$meta_address_permission = isset($_POST['meta_address_permission']) ? $_POST['meta_address_permission'] : "";

		if(is_array($meta_address_permission))
		{
			update_user_meta($user_id, 'meta_address_permission', implode(",", $meta_address_permission));
		}

		else
		{
			delete_user_meta($user_id, 'meta_address_permission');
		}
	}
}