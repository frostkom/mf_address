<?php

function deleted_user_address($user_id)
{
	global $wpdb;

	$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."address SET userID = '%d' WHERE userID = '%d'", get_current_user_id(), $user_id));
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
		'public' => false
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

	$arr_settings = array(
		"setting_address_extra" => __("Name for extra address field", 'lang_address'),
		"setting_show_member_id" => __("Show memberID", 'lang_address'),
	);

	foreach($arr_settings as $handle => $text)
	{
		add_settings_field($handle, $text, $handle."_callback", BASE_OPTIONS_PAGE, $options_area);

		register_setting(BASE_OPTIONS_PAGE, $handle);
	}
}

function settings_address_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);

	echo settings_header($setting_key, __("Address Book", 'lang_address'));
}

function setting_show_member_id_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key, 'yes');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'compare' => $option));
}

function setting_address_extra_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option));
}

function menu_address()
{
	$menu_root = 'mf_address/';
	$menu_start = $menu_root."list/index.php";
	$menu_capability = "edit_posts";

	add_menu_page("", __("Address Book", 'lang_address'), $menu_capability, $menu_start, '', 'dashicons-email-alt');

	add_submenu_page($menu_start, __("List", 'lang_address'), __("List", 'lang_address'), $menu_capability, $menu_start);
	add_submenu_page($menu_start, __("Add New", 'lang_address'), __("Add New", 'lang_address'), $menu_capability, $menu_root."create/index.php");

	$menu_capability = "edit_pages";

	add_submenu_page($menu_start, __("Import", 'lang_address'), __("Import", 'lang_address'), $menu_capability, $menu_root."import/index.php");
}

function show_profile_address($user)
{
	global $wpdb;

	$profile_address_permission = get_the_author_meta('profile_address_permission', $user->ID);

	$result = $wpdb->get_results("SELECT addressExtra FROM ".$wpdb->base_prefix."address WHERE addressExtra != '' GROUP BY addressExtra");

	if($wpdb->num_rows > 0)
	{
		echo "<table class='form-table'>
			<tr class='user-address-permission-wrap'>
				<th><label for='profile_address_permission'>".__("Address Permissions to Users", 'lang_address').":</label></th>
				<td>";

					$profile_address_permission = get_the_author_meta('profile_address_permission', $user->ID);
					$profile_address_permission = explode(",", $profile_address_permission);

					$arr_data = array();

					foreach($result as $r)
					{
						$strTableValue = $r->addressExtra;

						if($strTableValue != '')
						{
							$arr_data[$strTableValue] = $strTableValue;
						}
					}

					echo show_select(array('data' => $arr_data, 'name' => 'profile_address_permission[]', 'compare' => $profile_address_permission))
				."</td>
			</tr>
		</table>";
	}
}

function save_portfolio_address($user_id)
{
	if(current_user_can('update_core', $user_id))
	{
		save_register_address($user_id);
	}
}

function save_register_address($user_id)
{
	$profile_address_permission = isset($_POST['profile_address_permission']) ? $_POST['profile_address_permission'] : "";

	if(is_array($profile_address_permission))
	{
		update_user_meta($user_id, 'profile_address_permission', implode(",", $profile_address_permission));
	}

	else
	{
		delete_user_meta($user_id, 'profile_address_permission');
	}
}

function group_name($id)
{
	global $wpdb;

	return $wpdb->get_var($wpdb->prepare("SELECT post_title FROM ".$wpdb->posts." WHERE post_type = 'mf_group' AND ID = '%d'", $id));
}

function get_address_search_query($strSearch)
{
	global $wpdb, $is_part_of_group, $intGroupID;

	$query_join = $query_where = "";

	if($strSearch != '')
	{
		$query_where .= ($query_where != '' ? " AND " : "")."(addressFirstName LIKE '%".$strSearch."%' OR addressSurName LIKE '%".$strSearch."%' OR CONCAT(addressFirstName, ' ', addressSurName) LIKE '%".$strSearch."%'";

		$arr_address_search = explode(" ", $strSearch);

		$count_temp = count($arr_address_search);

		if($count_temp > 1)
		{
			$query_where .= " OR (";

				for($i = 0; $i < $count_temp; $i++)
				{
					$query_where .= ($i > 0 ? " AND " : "")."CONCAT(addressFirstName, ' ', addressSurName) LIKE '%".$arr_address_search[$i]."%'";
				}

			$query_where .= ")";
		}

		$query_where .= " OR addressAddress LIKE '%".$strSearch."%' OR addressZipCode LIKE '%".$strSearch."%' OR addressCity LIKE '%".$strSearch."%' OR addressTelNo LIKE '%".$strSearch."%' OR addressWorkNo LIKE '%".$strSearch."%' OR addressCellNo LIKE '%".$strSearch."%' OR addressEmail LIKE '%".$strSearch."%')";
	}

	if($is_part_of_group)
	{
		$query_join .= " INNER JOIN ".$wpdb->base_prefix."address2group USING (addressID)";
		$query_where .= ($query_where != '' ? " AND " : "")."groupID = '".$intGroupID."'";
	}

	if(!IS_EDITOR)
	{
		$profile_address_permission = get_the_author_meta('profile_address_permission', get_current_user_id());

		$query_where .= ($query_where != '' ? " AND " : "")."addressExtra IN('".str_replace(",", "','", $profile_address_permission)."')";
	}

	return array($query_join, $query_where);
}
