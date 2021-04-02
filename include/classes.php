<?php

class mf_address
{
	function __construct($data = array())
	{
		if(isset($data['id']) && $data['id'] > 0)
		{
			$this->id = $data['id'];
		}

		else
		{
			if(function_exists('check_var')) //MF Base might not be loaded yet
			{
				$this->id = check_var('intAddressID');
			}
		}

		$this->type = isset($data['type']) ? $data['type'] : '';

		$this->meta_prefix = 'mf_address_';
		$this->lang_key = 'lang_address';
	}

	function cron_base()
	{
		global $wpdb;

		$obj_cron = new mf_cron();
		$obj_cron->start(__CLASS__);

		if($obj_cron->is_running == false)
		{
			$setting_address_api_url = get_option('setting_address_api_url');

			if($setting_address_api_url != '')
			{
				$option_address_api_used = get_option('option_address_api_used', date("Y-m-d H:i:s", strtotime("-1 year")));

				$url = str_replace("[datetime]", urlencode($option_address_api_used), $setting_address_api_url);

				list($content, $headers) = get_url_content(array(
					'url' => $url,
					'catch_head' => true,
				));

				$log_message = "I could not get a successful result from the Address API";

				switch($headers['http_code'])
				{
					case 200:
						$json = json_decode($content, true);

						switch($json['status'])
						{
							case 'true':
								if(isset($json['data']))
								{
									$count_incoming = count($json['data']);

									if($count_incoming > 0)
									{
										$count_updated = $count_updated_error = $count_inserted = $count_inserted_error = $count_error = 0;

										if(get_option('setting_address_debug') == 'yes')
										{
											do_log("Address API: ".$url." -> ".$count_incoming);
										}

										foreach($json['data'] as $item)
										{
											$strAddressBirthDate = $item['memberSSN'];
											$strAddressFirstName = $item['memberFirstName'];
											$strAddressSurName = $item['memberSurName'];
											$strAddressAddress = $item['memberAddress'];
											$strAddressCo = $item['memberCo'];
											$intAddressZipCode = $item['memberZipCode'];
											$strAddressCity = $item['memberCity'];
											$intAddressCountry = $item['memberCountry'];
											$strAddressExtra = $item['associationName'];

											$intAddressID = 0;
											$strAddressTelNo = $strAddressCellNo = $strAddressWorkNo = $strAddressEmail = '';

											foreach($item['contact'] as $contact)
											{
												switch($contact['memberContactType'])
												{
													case 'phone':
														switch($contact['memberContactLocation'])
														{
															case 'home':
																$strAddressTelNo = $contact['memberContactInformation'];
															break;

															case 'sms':
																$strAddressCellNo = $contact['memberContactInformation'];
															break;

															case 'work':
																$strAddressWorkNo = $contact['memberContactInformation'];
															break;
														}
													break;

													case 'email':
														$strAddressEmail = $contact['memberContactInformation'];
													break;
												}
											}

											$result = $wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".get_address_table_prefix()."address WHERE addressBirthDate = %s AND addressDeleted = '0'", $strAddressBirthDate));
											$rows = $wpdb->num_rows;

											if($rows > 0)
											{
												if($rows == 1)
												{
													foreach($result as $r)
													{
														$intAddressID = $r->addressID;

														$wpdb->query($wpdb->prepare("UPDATE ".get_address_table_prefix()."address SET addressFirstName = %s, addressSurName = %s, addressZipCode = %s, addressCity = %s, addressCountry = '%d', addressAddress = %s, addressCo = %s, addressTelNo = %s, addressCellNo = %s, addressWorkNo = %s, addressEmail = %s, addressExtra = %s WHERE addressID = '%d'", $strAddressFirstName, $strAddressSurName, $intAddressZipCode, $strAddressCity, $intAddressCountry, $strAddressAddress, $strAddressCo, $strAddressTelNo, $strAddressCellNo, $strAddressWorkNo, $strAddressEmail, $strAddressExtra, $intAddressID));

														if($wpdb->rows_affected > 0)
														{
															$count_updated++;
														}

														else
														{
															$count_updated_error++;
														}
													}
												}

												else
												{
													do_log("<a href='".admin_url("admin.php?page=mf_address/list/index.php&s=".$strAddressBirthDate)."'>".sprintf("There were %d addresses with the same Social Security Number (%s)", $rows, $wpdb->last_query)."</a>");

													$count_error++;
												}
											}

											else
											{
												$wpdb->query($wpdb->prepare("INSERT INTO ".get_address_table_prefix()."address SET addressBirthDate = %s, addressFirstName = %s, addressSurName = %s, addressZipCode = %s, addressCity = %s, addressCountry = '%d', addressAddress = %s, addressCo = %s, addressTelNo = %s, addressCellNo = %s, addressWorkNo = %s, addressEmail = %s, addressExtra = %s, addressCreated = NOW()", $strAddressBirthDate, $strAddressFirstName, $strAddressSurName, $intAddressZipCode, $strAddressCity, $intAddressCountry, $strAddressAddress, $strAddressCo, $strAddressTelNo, $strAddressCellNo, $strAddressWorkNo, $strAddressEmail, $strAddressExtra));

												$intAddressID = $wpdb->insert_id;

												if($intAddressID > 0)
												{
													$count_inserted++;
												}

												else
												{
													$count_inserted_error++;
												}

												/*if($count_inserted < 10 && get_option('setting_address_debug') == 'yes')
												{
													do_log("Address API: Insert ".$strAddressFirstName." ".$strAddressSurName." into ".get_address_table_prefix()."address because it does not exist");
												}*/
											}

											if($intAddressID > 0)
											{
												$wpdb->query($wpdb->prepare("UPDATE ".get_address_table_prefix()."address SET addressSyncedDate = NOW() WHERE addressID = '%d'", $intAddressID));
											}
										}

										if(get_option('setting_address_debug') == 'yes')
										{
											do_log("Address API - Report: ".$count_incoming." incoming, ".$count_updated." updated, ".$count_updated_error." NOT updated, ".$count_inserted." inserted, ".$count_inserted_error." NOT inserted, ".$count_error." errors");
										}

										update_option('option_address_api_used', date("Y-m-d H:i:s"), 'no');
									}
								}

								if(isset($json['ended_data']))
								{
									$count_ended = count($json['ended_data']);

									if($count_ended > 0)
									{
										$count_not_exit = $count_removed = $count_removed_error = $count_not_found = 0;

										if(get_option('setting_address_debug') == 'yes')
										{
											do_log("Address API - Ended: ".$url." -> ".$count_ended);
										}

										foreach($json['ended_data'] as $item)
										{
											$strAddressBirthDate = $item['memberSSN'];
											//$ = $item['membershipEnded'];
											$strMembershipEndedReason = $item['membershipEndedReason'];

											if($strMembershipEndedReason == 'exit')
											{
												$result = $wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".get_address_table_prefix()."address WHERE addressBirthDate = %s AND addressDeleted = '0'", $strAddressBirthDate)); //, addressFirstName, addressSurName

												if($wpdb->num_rows > 0)
												{
													foreach($result as $r)
													{
														$intAddressID = $r->addressID;

														//do_log("Remove ".$r->addressFirstName." ".$r->addressSurName." because exited as member");

														if(is_plugin_active("mf_group/index.php"))
														{
															global $obj_group;

															if(!isset($obj_group))
															{
																$obj_group = new mf_group();
															}

															$obj_group->remove_address(array('address_id' => $intAddressID));
														}

														if($this->trash(array('address_id' => $intAddressID, 'force_admin' => true)))
														{
															$count_removed++;
														}

														else
														{
															$count_removed_error++;
														}
													}
												}

												else
												{
													$count_not_found++;
												}
											}

											else
											{
												$count_not_exit++;
											}
										}

										if(get_option('setting_address_debug') == 'yes')
										{
											do_log("Address API - Report: ".$count_ended." ended, ".$count_removed." removed, ".$count_removed_error." NOT removed, ".$count_not_exit." NOT exit, ".$count_not_found." NOT found");
										}

										update_option('option_address_api_used', date("Y-m-d H:i:s"), 'no');
									}
								}
							break;

							default:
								do_log("Address API Error: ".$url." -> ".htmlspecialchars(var_export($json, true)));
							break;
						}

						do_log($log_message, 'trash');
					break;

					default:
						do_log($log_message." (".$content.", ".htmlspecialchars(var_export($headers, true)).")");
					break;
				}
			}
		}

		$obj_cron->end();
	}

	function init()
	{
		if(!session_id())
		{
			@session_start();
		}

		$labels = array(
			'name' => _x(__("Address Book", $this->lang_key), 'post type general name'),
			'singular_name' => _x(__("Address Book", $this->lang_key), 'post type singular name'),
			'menu_name' => __("Address Book", $this->lang_key)
		);

		$args = array(
			'labels' => $labels,
			'public' => false,
			'exclude_from_search' => true,
		);

		register_post_type('mf_address', $args);
	}

	function settings_address()
	{
		$options_area = __FUNCTION__;

		add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = array();

		if(IS_SUPER_ADMIN && is_multisite())
		{
			$arr_settings['setting_address_site_wide'] = __("Use Master Table on All Sites", $this->lang_key);
		}

		$arr_settings['setting_address_extra'] = __("Name for Extra Address Field", $this->lang_key);
		$arr_settings['setting_address_extra_profile'] = __("Display Settings for Extra in Profile", $this->lang_key);
		$arr_settings['setting_address_display_member_id'] = __("Display Member ID", $this->lang_key);
		$arr_settings['setting_address_api_url'] = __("API URL", $this->lang_key);

		if(get_option('setting_address_api_url') != '')
		{
			$arr_settings['setting_address_debug'] = __("Debug", $this->lang_key);
		}

		show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
	}

	function settings_address_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Address Book", $this->lang_key));
	}

	function setting_address_site_wide_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		settings_save_site_wide($setting_key);
		$option = get_site_option($setting_key, 'yes');

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

	function setting_address_display_member_id_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key, 'yes');

		echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
	}

	function setting_address_api_url_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		echo show_textfield(array('type' => 'url', 'name' => $setting_key, 'value' => $option));
	}

	function setting_address_debug_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option_or_default($setting_key, 'no');

		echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));

		setting_time_limit(array('key' => $setting_key, 'value' => $option));
	}

	function admin_menu()
	{
		$menu_root = 'mf_address/';
		$menu_start = $menu_root."list/index.php";
		$menu_capability = override_capability(array('page' => $menu_start, 'default' => 'edit_posts'));

		$menu_title = __("Address Book", $this->lang_key);
		add_menu_page("", $menu_title, $menu_capability, $menu_start, '', 'dashicons-email-alt', 99);

		$menu_title = __("List", $this->lang_key);
		add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_start);

		$menu_title = __("Add New", $this->lang_key);
		add_submenu_page($menu_start, $menu_title, " - ".$menu_title, $menu_capability, $menu_root."create/index.php");

		$menu_capability = override_capability(array('page' => $menu_root."import/index.php", 'default' => 'edit_pages'));

		$menu_title = __("Import", $this->lang_key);
		add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_root."import/index.php");

		if(IS_SUPER_ADMIN)
		{
			$menu_title = __("Export", $this->lang_key);
			add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_root."export/index.php");
		}
	}

	function edit_user_profile($user)
	{
		global $wpdb;

		if(IS_ADMIN && get_option_or_default('setting_address_extra_profile', 'yes') == 'yes')
		{
			$result = $wpdb->get_results("SELECT addressExtra FROM ".get_address_table_prefix()."address WHERE addressExtra != '' GROUP BY addressExtra");

			if($wpdb->num_rows > 0)
			{
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

				echo "<table class='form-table'>
					<tr class='user-address-permission-wrap'>
						<th><label for='meta_address_permission'>".__("Address Permissions to Users", $this->lang_key).":</label></th>
						<td>".show_select(array('data' => $arr_data, 'name' => 'meta_address_permission[]', 'value' => $meta_address_permission))."</td>
					</tr>
				</table>";
			}
		}
	}

	function profile_update($user_id)
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

	function deleted_user($user_id)
	{
		global $wpdb;

		$wpdb->query($wpdb->prepare("UPDATE ".get_address_table_prefix()."address SET userID = '%d' WHERE userID = '%d'", get_current_user_id(), $user_id));
	}

	function restrict_manage_posts($post_type)
	{
		global $wpdb;

		if($post_type == get_address_table_prefix()."address" && is_plugin_active("mf_group/index.php"))
		{
			if(get_option('setting_address_api_url') != '')
			{
				$strFilterIsSynced = get_or_set_table_filter(array('key' => 'strFilterIsSynced', 'save' => true));

				$arr_data = get_yes_no_for_select(array('choose_here_text' => __("Syncronized Through API", $this->lang_key)));

				$rows_synced = $wpdb->get_var($wpdb->prepare("SELECT COUNT(addressID) FROM ".get_address_table_prefix()."address WHERE addressSyncedDate >= %s", DEFAULT_DATE));
				$rows_not_synced = $wpdb->get_var($wpdb->prepare("SELECT COUNT(addressID) FROM ".get_address_table_prefix()."address WHERE addressSyncedDate < %s", DEFAULT_DATE));

				if($rows_synced > 0)
				{
					$arr_data['yes'] .= " (".$rows_synced.")";
				}

				if($rows_not_synced > 0)
				{
					$arr_data['no'] .= " (".$rows_not_synced.")";
				}

				echo show_select(array('data' => $arr_data, 'name' => 'strFilterIsSynced', 'value' => $strFilterIsSynced));
			}

			$obj_group = new mf_group();

			$arr_data = $obj_group->get_for_select();

			if(count($arr_data) > 0)
			{
				$intGroupID = get_or_set_table_filter(array('key' => 'intGroupID', 'save' => true));

				echo show_select(array('data' => $arr_data, 'name' => 'intGroupID', 'value' => $intGroupID));

				if($intGroupID > 0)
				{
					$strFilterIsMember = get_or_set_table_filter(array('key' => 'strFilterIsMember', 'save' => true));
					$strFilterAccepted = get_or_set_table_filter(array('key' => 'strFilterAccepted', 'save' => true));
					$strFilterUnsubscribed = get_or_set_table_filter(array('key' => 'strFilterUnsubscribed', 'save' => true));

					if($obj_group->amount_in_group(array('id' => $intGroupID)) > 0) //$strFilterAccepted == '' && $strFilterUnsubscribed == '' && 
					{
						echo show_select(array('data' => get_yes_no_for_select(array('choose_here_text' => __("Part of Group", $this->lang_key))), 'name' => 'strFilterIsMember', 'value' => $strFilterIsMember));
					}

					else
					{
						remove_table_filter(array('key' => 'strFilterIsMember'));
					}

					if($obj_group->amount_in_group(array('id' => $intGroupID, 'accepted' => 0)) > 0) //$strFilterIsMember != 'no' && 
					{
						echo show_select(array('data' => get_yes_no_for_select(array('choose_here_text' => __("Accepted", $this->lang_key))), 'name' => 'strFilterAccepted', 'value' => $strFilterAccepted));
					}

					else
					{
						remove_table_filter(array('key' => 'strFilterAccepted'));
					}

					if($obj_group->amount_in_group(array('id' => $intGroupID, 'unsubscribed' => 1)) > 0) //$strFilterIsMember != 'no' && 
					{
						echo show_select(array('data' => get_yes_no_for_select(array('choose_here_text' => __("Unsubscribed", $this->lang_key))), 'name' => 'strFilterUnsubscribed', 'value' => $strFilterUnsubscribed));
					}

					else
					{
						remove_table_filter(array('key' => 'strFilterUnsubscribed'));
					}
				}

				else
				{
					remove_table_filter(array('key' => 'intGroupID'));
				}
			}
		}
	}

	function export_personal_data($email_address, $page = 1)
	{
		global $wpdb;

		$number = 200;
		$page = (int)$page;

		$group_id = $this->meta_prefix;
		$group_label = __("Address Book", $this->lang_key);

		$export_items = array();

		$result = $wpdb->get_results($wpdb->prepare("SELECT addressID, addressFirstName, addressSurName FROM ".get_address_table_prefix()."address WHERE addressEmail = %s AND addressDeleted = '0' LIMIT ".(($page - 1) * $number).", ".$number, $email_address));

		foreach($result as $r)
		{
			$item_id = $this->meta_prefix."-".$r->addressID;

			$data = array(
				array(
					'name' => __("First Name"),
					'value' => $r->addressFirstName,
				),
				array(
					'name' => __("Last Name"),
					'value' => $r->addressSurName,
				),
			);

			$export_items[] = array(
				'group_id' => $group_id,
				'group_label' => $group_label,
				'item_id' => $item_id,
				'data' => $data,
			);
		}

		return array(
			'data' => $export_items,
			'done' => (count($result) < $number),
		);
	}

	function wp_privacy_personal_data_exporters($exporters)
	{
		$exporters[$this->meta_prefix] = array(
			'exporter_friendly_name' => __("Address Book", $this->lang_key),
			'callback' => array($this, 'export_personal_data'),
		);

		return $exporters;
	}

	function erase_personal_data($email_address, $page = 1)
	{
		global $wpdb;

		$number = 200;
		$page = (int)$page;

		$items_removed = false;

		$result = $wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".get_address_table_prefix()."address WHERE addressEmail = %s AND addressDeleted = '0'", $email_address)); // LIMIT ".(($page - 1) * $number).", ".$number

		foreach($result as $r)
		{
			//$this->trash(array('address_id' => $r->addressID));
			do_log("Trash Address ".$r->addressID." (".$wpdb->prepare("UPDATE ".get_address_table_prefix()."address SET addressDeleted = '1', addressDeletedID = '%d', addressDeletedDate = NOW() WHERE addressID = '%d'".(IS_ADMIN ? "" : " AND addressPublic = '0' AND userID = '".get_current_user_id()."'"), get_current_user_id(), $this->id).")");

			$items_removed = true;
		}

		return array(
			'items_removed' => $items_removed,
			'items_retained' => false, // always false in this example
			'messages' => array(), // no messages in this example
			'done' => (count($result) < $number),
		);
	}

	function wp_privacy_personal_data_erasers($erasers)
	{
		$erasers[$this->meta_prefix] = array(
			'eraser_friendly_name' => __("Address Book", $this->lang_key),
			'callback' => array($this, 'erase_personal_data'),
		);

		return $erasers;
	}

	function filter_profile_fields($arr_fields)
	{
		global $wpdb;

		if(IS_ADMIN && get_option_or_default('setting_address_extra_profile', 'yes') == 'yes')
		{
			$result = $wpdb->get_results("SELECT addressExtra FROM ".get_address_table_prefix()."address WHERE addressExtra != '' GROUP BY addressExtra");

			if($wpdb->num_rows > 0)
			{
				$arr_data = array();

				foreach($result as $r)
				{
					$strTableValue = $r->addressExtra;

					if($strTableValue != '')
					{
						$arr_data[$strTableValue] = $strTableValue;
					}
				}

				$arr_fields[] = array('type' => 'select', 'options' => $arr_data, 'name' => 'meta_address_permission', 'multiple' => true, 'text' => __("Address Permissions to Users", $this->lang_key));
			}
		}

		return $arr_fields;
	}

	function wp_login()
	{
		@session_destroy();
	}

	function has_duplicate($data)
	{
		global $wpdb;

		$intAddressID = $data['item']['addressID'];
		$intAddressMemberID = $data['item']['addressMemberID'];
		$strAddressBirthDate = $data['item']['addressBirthDate'];
		$strAddressEmail = $data['item']['addressEmail'];

		$result = $wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".get_address_table_prefix()."address WHERE ((addressMemberID > '0' AND addressMemberID = '%d') OR (addressBirthDate != '' AND addressBirthDate = %s) OR (addressEmail != '' AND addressEmail = %s)) AND addressDeleted = '0' AND addressID != '%d'", $intAddressMemberID, $strAddressBirthDate, $strAddressEmail, $intAddressID));

		if($wpdb->num_rows > 0)
		{
			$this->result_duplicate = $result;

			return true;
		}

		else
		{
			$this->result_duplicate = array();

			return false;
		}
	}

	function do_merge($data)
	{
		global $wpdb, $error_text, $done_text;

		if(count($data['ids']) > 1)
		{
			$id_prev = 0;

			foreach($data['ids'] as $id)
			{
				if($id_prev > 0)
				{
					$arr_unique_columns = array('addressMemberID', 'addressBirthDate', 'addressEmail');
					$arr_columns = array('addressMemberID', 'addressBirthDate', 'addressFirstName', 'addressSurName', 'addressCo', 'addressAddress', 'addressZipCode', 'addressCity', 'addressCountry', 'addressTelNo', 'addressWorkNo', 'addressCellNo', 'addressEmail', 'addressExtra');

					$base_query = "SELECT addressID, addressPublic, ".implode(", ", $arr_unique_columns).", ".implode(", ", $arr_columns)." FROM ".get_address_table_prefix()."address WHERE addressID = '%d'";

					$result_prev = $wpdb->get_results($wpdb->prepare($base_query, $id_prev), ARRAY_A);
					$result = $wpdb->get_results($wpdb->prepare($base_query, $id), ARRAY_A);

					if($wpdb->num_rows > 0 && $result[0]['addressPublic'] == 1) // && $result_prev[0]['addressPublic'] == 1
					{
						$unique_column = '';

						foreach($arr_unique_columns as $str_unique_column)
						{
							if($result[0][$str_unique_column] != '' && $result[0][$str_unique_column] != '0' && $result_prev[0][$str_unique_column] != '' && $result_prev[0][$str_unique_column] != '0')
							{
								if(strtolower($result_prev[0][$str_unique_column]) == strtolower($result[0][$str_unique_column]))
								{
									$unique_column = $str_unique_column;
								}

								break;
							}
						}

						if($unique_column != '')
						{
							$query_set = '';

							foreach($arr_columns as $str_column)
							{
								if($result_prev[0][$str_column] != '' && strtolower($result_prev[0][$str_column]) != strtolower($result[0][$str_column]))
								{
									$query_set .= ($query_set != '' ? ", " : "").$str_column." = '".esc_sql($result_prev[0][$str_column])."'";
								}
							}

							if($query_set != '')
							{
								$wpdb->query($wpdb->prepare("UPDATE ".get_address_table_prefix()."address SET ".$query_set." WHERE addressID = '%d'", $id));

								do_log(get_user_info(array('id' => get_current_user_id()))." Merged/Updated ".$id_prev." -> ".$id." (".$wpdb->last_query.")", 'notification');
							}

							do_action('merge_address', $id_prev, $id);

							$this->trash(array('address_id' => $id_prev));
						}

						else
						{
							$error_text = __("I could not merge the addresses for you because no unique column matched", $this->lang_key);

							/*if(IS_SUPER_ADMIN)
							{
								$error_text .= " (".var_export($result_prev[0], true)." -> ".var_export($result[0], true).")";
							}*/

							break;
						}
					}

					else
					{
						$error_text = __("I could not merge the addresses for you because only public addresses are possible to merge", $this->lang_key);

						break;
					}
				}

				$id_prev = $id;
			}

			$done_text = __("The addresses have been merged succesfully", $this->lang_key);
		}

		else
		{
			$error_text = __("You have to choose at least two addresses to merge", $this->lang_key);
		}
	}

	function fetch_request()
	{
		switch($this->type)
		{
			case 'create':
				//$this->id = check_var('intAddressID'); //Is checked in __construct()
				$this->public = check_var('intAddressPublic');
				$this->member_id = check_var('intAddressMemberID');
				$this->birthdate = check_var('strAddressBirthDate');
				$this->first_name = check_var('strAddressFirstName');
				$this->sur_name = check_var('strAddressSurName');
				$this->address = check_var('strAddressAddress');
				$this->co = check_var('strAddressCo');
				$this->zipcode = check_var('intAddressZipCode');
				$this->city = check_var('strAddressCity');
				$this->country = check_var('intAddressCountry');
				$this->telno = check_var('strAddressTelNo');
				$this->cellno = check_var('strAddressCellNo');
				$this->workno = check_var('strAddressWorkNo');
				$this->email = check_var('strAddressEmail');

				if(IS_ADMIN)
				{
					$this->extra = check_var('strAddressExtra');
				}
			break;

			case 'list':
				$this->group_id = check_var('intGroupID');

				$this->ids = check_var('ids', 'char');
				$this->is_public = check_var('is_public', 'int');
			break;
		}
	}

	function save_data()
	{
		global $wpdb, $error_text, $done_text;

		if(is_plugin_active("mf_group/index.php"))
		{
			$obj_group = new mf_group();
		}

		$out = "";

		switch($this->type)
		{
			case 'create':
				if(isset($_POST['btnAddressUpdate']) && wp_verify_nonce($_POST['_wpnonce_address_update'], 'address_update_'.$this->id))
				{
					if($this->member_id != '' || $this->birthdate != '' || $this->first_name != '' || $this->sur_name != '' || $this->zipcode != '' || $this->address != '' || $this->co != '' || $this->telno != '' || $this->cellno != '' || $this->workno != '' || $this->email != '')
					{
						if($this->email != '' && !is_domain_valid($this->email))
						{
							$error_text = __("The e-mail address does not seam to be valid because the response is that the domain does not have e-mails connected to it", $this->lang_key);
						}

						else
						{
							$query_set = "";

							if(IS_ADMIN)
							{
								$query_set .= ", addressExtra = '".esc_sql($this->extra)."'";
							}

							if($this->id > 0)
							{
								$query_where = "";

								if(IS_ADMIN)
								{
									$query_set .= ", addressPublic = '".esc_sql($this->public)."'";
								}

								else
								{
									$query_where .= " AND (addressPublic = '1' OR addressPublic = '0' AND userID = '".get_current_user_id()."')";
								}

								$wpdb->query($wpdb->prepare("UPDATE ".get_address_table_prefix()."address SET addressMemberID = '%d', addressBirthDate = %s, addressFirstName = %s, addressSurName = %s, addressZipCode = %s, addressCity = %s, addressCountry = '%d', addressAddress = %s, addressCo = %s, addressTelNo = %s, addressCellNo = %s, addressWorkNo = %s, addressEmail = %s".$query_set." WHERE addressID = '%d'".$query_where, $this->member_id, $this->birthdate, $this->first_name, $this->sur_name, $this->zipcode, $this->city, $this->country, $this->address, $this->co, $this->telno, $this->cellno, $this->workno, $this->email, $this->id));

								if($wpdb->rows_affected > 0)
								{
									$type = 'updated';
								}

								else
								{
									$error_text = __("I could not update the address for you. Either you do not have the permission to update this address or you did not change any of the fields before saving", $this->lang_key);
								}
							}

							else
							{
								$wpdb->query($wpdb->prepare("INSERT INTO ".get_address_table_prefix()."address SET addressPublic = '%d', addressMemberID = '%d', addressBirthDate = %s, addressFirstName = %s, addressSurName = %s, addressZipCode = %s, addressCity = %s, addressCountry = '%d', addressAddress = %s, addressCo = %s, addressTelNo = %s, addressCellNo = %s, addressWorkNo = %s, addressEmail = %s, addressCreated = NOW(), userID = '%d'".$query_set, $this->public, $this->member_id, $this->birthdate, $this->first_name, $this->sur_name, $this->zipcode, $this->city, $this->country, $this->address, $this->co, $this->telno, $this->cellno, $this->workno, $this->email, get_current_user_id()));

								$this->id = $wpdb->insert_id;

								$type = 'created';
							}

							if($error_text != '')
							{
								// Do nothing. Just let it pass and get_notification() after save_data() will display the message
							}

							else if($this->id > 0)
							{
								mf_redirect(admin_url("admin.php?page=mf_address/list/index.php&".$type));
							}

							else
							{
								$error_text = __("The information was not submitted, contact an admin if this persists", $this->lang_key);

								do_log("Address Error: ".$wpdb->last_query);
							}
						}
					}
				}
			break;

			case 'list':
				if(isset($_REQUEST['btnAddressDelete']) && $this->id > 0 && wp_verify_nonce($_REQUEST['_wpnonce_address_delete'], 'address_delete_'.$this->id))
				{
					if($this->trash())
					{
						$done_text = __("The address was deleted", $this->lang_key);
					}

					else
					{
						$error_text = __("I could not delete the address", $this->lang_key);
					}
				}

				else if(isset($_REQUEST['btnAddressMerge']) && $this->id > 0 && wp_verify_nonce($_REQUEST['_wpnonce_address_merge'], 'address_merge_'.$this->id))
				{
					if($this->is_public)
					{
						$arr_ids = array_merge(explode(",", $this->ids), array($this->id));
					}

					else
					{
						$arr_ids = array_merge(array($this->id), explode(",", $this->ids));
					}

					$this->do_merge(array('ids' => $arr_ids));
				}

				else if(isset($_REQUEST['btnAddressRestore']) && $this->id > 0 && wp_verify_nonce($_REQUEST['_wpnonce_address_restore'], 'address_restore_'.$this->id))
				{
					$this->restore();

					$done_text = __("I recovered the address for you", $this->lang_key);
				}

				else if(isset($_GET['btnAddressAdd']) && $this->group_id > 0 && $this->id > 0 && wp_verify_nonce($_REQUEST['_wpnonce_address_add'], 'address_add_'.$this->id.'_'.$this->group_id) && is_plugin_active("mf_group/index.php"))
				{
					if($obj_group->has_address(array('address_id' => $this->id, 'group_id' => $this->group_id)) == false)
					{
						$obj_group->add_address(array('address_id' => $this->id, 'group_id' => $this->group_id));

						$done_text = __("The address was added to the group", $this->lang_key);
					}

					else
					{
						$error_text = __("The address already exists in the group", $this->lang_key);
					}
				}

				else if(isset($_GET['btnAddressRemove']) && $this->group_id > 0 && $this->id > 0 && wp_verify_nonce($_REQUEST['_wpnonce_address_remove'], 'address_remove_'.$this->id.'_'.$this->group_id) && is_plugin_active("mf_group/index.php"))
				{
					if($obj_group->has_address(array('address_id' => $this->id, 'group_id' => $this->group_id)) == true)
					{
						$obj_group->remove_address(array('address_id' => $this->id, 'group_id' => $this->group_id));

						$done_text = __("The address was removed from the group", $this->lang_key);
					}

					else
					{
						$error_text = __("The address could not be removed since it did not exist in the group", $this->lang_key);
					}
				}

				else if(isset($_GET['btnAddressAccept']) && $this->group_id > 0 && $this->id > 0 && wp_verify_nonce($_REQUEST['_wpnonce_address_accept'], 'address_accept_'.$this->id.'_'.$this->group_id) && is_plugin_active("mf_group/index.php"))
				{
					if($obj_group->accept_address(array('address_id' => $this->id, 'group_id' => $this->group_id)))
					{
						$done_text = __("The address has been manually accepted", $this->lang_key);
					}

					else
					{
						$error_text = __("I could not manually accept the address for you", $this->lang_key);
					}
				}

				else if(isset($_GET['btnAddressResend']) && $this->group_id > 0 && $this->id > 0 && wp_verify_nonce($_REQUEST['_wpnonce_address_resend'], 'address_resend_'.$this->id.'_'.$this->group_id) && is_plugin_active("mf_group/index.php"))
				{
					if($obj_group->send_acceptance_message(array('type' => 'reminder', 'address_id' => $this->id, 'group_id' => $this->group_id)))
					{
						$done_text = __("The message was sent", $this->lang_key);
					}

					else
					{
						$error_text = __("I could not send the message for you", $this->lang_key);
					}
				}

				else if(isset($_GET['created']))
				{
					$done_text = __("The address was created", $this->lang_key);
				}

				else if(isset($_GET['updated']))
				{
					$done_text = __("The address was updated", $this->lang_key);
				}
			break;
		}

		return $out;
	}

	function get_from_db()
	{
		global $wpdb;

		switch($this->type)
		{
			case 'create':
				if($this->id > 0 && !isset($_POST['btnAddressUpdate']))
				{
					$result = $wpdb->get_results($wpdb->prepare("SELECT addressPublic, addressMemberID, addressBirthDate, addressFirstName, addressSurName, addressAddress, addressCo, addressZipCode, addressCity, addressCountry, addressTelNo, addressCellNo, addressWorkNo, addressEmail, addressExtra, addressDeleted FROM ".get_address_table_prefix()."address WHERE addressID = '%d'", $this->id));

					foreach($result as $r)
					{
						$this->public = $r->addressPublic;
						$this->member_id = $r->addressMemberID;
						$this->birthdate = $r->addressBirthDate;
						$this->first_name = $r->addressFirstName;
						$this->sur_name = $r->addressSurName;
						$this->address = $r->addressAddress;
						$this->co = $r->addressCo;
						$this->zipcode = $r->addressZipCode;
						$this->city = $r->addressCity;
						$this->country = $r->addressCountry;
						$this->telno = $r->addressTelNo;
						$this->cellno = $r->addressCellNo;
						$this->workno = $r->addressWorkNo;
						$this->email = $r->addressEmail;

						if(IS_ADMIN)
						{
							$this->extra = $r->addressExtra;
						}

						$intAddressDeleted = $r->addressDeleted;

						if($intAddressDeleted == 1)
						{
							$wpdb->query($wpdb->prepare("UPDATE ".get_address_table_prefix()."address SET addressDeleted = '0', addressDeletedID = '', addressDeletedDate = '' WHERE addressPublic = '0' AND addressID = '%d' AND userID = '%d'", $this->id, get_current_user_id()));
						}
					}
				}
			break;
		}
	}

	function get_countries_for_select($data = array())
	{
		if(!isset($data['add_choose_here'])){	$data['add_choose_here'] = true;}
		if(!isset($data['exclude'])){			$data['exclude'] = array();}

		$arr_data = array();

		if($data['add_choose_here'] == true)
		{
			$arr_data[''] = "-- ".__("Choose Here", $this->lang_key)." --";
		}

		$arr_countries = array(
			1 	=> __("Afghanistan", $this->lang_key),
			2 	=> __("Albania", $this->lang_key),
			3 	=> __("Algeria", $this->lang_key),
			4	=> __("American Samoa", $this->lang_key),
			5	=> __("Andorra", $this->lang_key),
			6	=> __("Angola", $this->lang_key),
			7	=> __("Anguilla", $this->lang_key),
			8	=> __("Antarctica", $this->lang_key),
			9	=> __("Antigua and Barbuda", $this->lang_key),
			10	=> __("Argentina", $this->lang_key),
			11	=> __("Armenia", $this->lang_key),
			12	=> __("Aruba", $this->lang_key),
			13	=> __("Australia", $this->lang_key),
			14	=> __("Austria", $this->lang_key),
			15	=> __("Azerbaijan", $this->lang_key),
			16	=> __("Bahamas", $this->lang_key),
			17	=> __("Bahrain", $this->lang_key),
			18	=> __("Bangladesh", $this->lang_key),
			19	=> __("Barbados", $this->lang_key),
			20	=> __("Belarus", $this->lang_key),
			21	=> __("Belgium", $this->lang_key),
			22	=> __("Belize", $this->lang_key),
			23	=> __("Benin", $this->lang_key),
			24	=> __("Bermuda", $this->lang_key),
			25	=> __("Bhutan", $this->lang_key),
			26	=> __("Bolivia", $this->lang_key),
			27	=> __("Bosnia and Herzegowina", $this->lang_key),
			28	=> __("Botswana", $this->lang_key),
			29	=> __("Bouvet Island", $this->lang_key),
			30	=> __("Brazil", $this->lang_key),
			31	=> __("British Indian Ocean Territory", $this->lang_key),
			32	=> __("Brunei Darussalam", $this->lang_key),
			33	=> __("Bulgaria", $this->lang_key),
			34	=> __("Burkina Faso", $this->lang_key),
			35	=> __("Burundi", $this->lang_key),
			36	=> __("Cambodia", $this->lang_key),
			37	=> __("Cameroon", $this->lang_key),
			38	=> __("Canada", $this->lang_key),
			39	=> __("Cape Verde", $this->lang_key),
			40	=> __("Cayman Islands", $this->lang_key),
			41	=> __("Central African Republic", $this->lang_key),
			42	=> __("Chad", $this->lang_key),
			43	=> __("Chile", $this->lang_key),
			44	=> __("China", $this->lang_key),
			45	=> __("Christmas Island", $this->lang_key),
			46	=> __("Cocos (Keeling) Islands", $this->lang_key),
			47	=> __("Colombia", $this->lang_key),
			48	=> __("Comoros", $this->lang_key),
			49	=> __("Congo", $this->lang_key),
			50	=> __("Cook Islands", $this->lang_key),
			51	=> __("Costa Rica", $this->lang_key),
			52	=> __("Cote D'Ivoire", $this->lang_key),
			53	=> __("Croatia", $this->lang_key),
			54	=> __("Cuba", $this->lang_key),
			55	=> __("Cyprus", $this->lang_key),
			56	=> __("Czech Republic", $this->lang_key),
			57	=> __("Denmark", $this->lang_key),
			58	=> __("Djibouti", $this->lang_key),
			59	=> __("Dominica", $this->lang_key),
			60	=> __("Dominican Republic", $this->lang_key),
			61	=> __("East Timor", $this->lang_key),
			62	=> __("Ecuador", $this->lang_key),
			63	=> __("Egypt", $this->lang_key),
			64	=> __("El Salvador", $this->lang_key),
			65	=> __("Equatorial Guinea", $this->lang_key),
			66	=> __("Eritrea", $this->lang_key),
			67	=> __("Estonia", $this->lang_key),
			68	=> __("Ethiopia", $this->lang_key),
			69	=> __("Falkland Islands (Malvinas)", $this->lang_key),
			70	=> __("Faroe Islands", $this->lang_key),
			71	=> __("Fiji", $this->lang_key),
			72	=> __("Finland", $this->lang_key),
			73	=> __("France", $this->lang_key),
			74	=> __("France, Metropolitan", $this->lang_key),
			75	=> __("French Guiana", $this->lang_key),
			76	=> __("French Polynesia", $this->lang_key),
			77	=> __("French Southern Territories", $this->lang_key),
			78	=> __("Gabon", $this->lang_key),
			79	=> __("Gambia", $this->lang_key),
			80	=> __("Georgia", $this->lang_key),
			81	=> __("Germany", $this->lang_key),
			82	=> __("Ghana", $this->lang_key),
			83	=> __("Gibraltar", $this->lang_key),
			84	=> __("Greece", $this->lang_key),
			85	=> __("Greenland", $this->lang_key),
			86	=> __("Grenada", $this->lang_key),
			87	=> __("Guadeloupe", $this->lang_key),
			88	=> __("Guam", $this->lang_key),
			89	=> __("Guatemala", $this->lang_key),
			90	=> __("Guinea", $this->lang_key),
			91	=> __("Guinea-bissau", $this->lang_key),
			92	=> __("Guyana", $this->lang_key),
			93	=> __("Haiti", $this->lang_key),
			94	=> __("Heard and Mc Donald Islands", $this->lang_key),
			95	=> __("Honduras", $this->lang_key),
			96	=> __("Hong Kong", $this->lang_key),
			97	=> __("Hungary", $this->lang_key),
			98	=> __("Iceland", $this->lang_key),
			99	=> __("India", $this->lang_key),
			100	=> __("Indonesia", $this->lang_key),
			101	=> __("Iran (Islamic Republic of)", $this->lang_key),
			102	=> __("Iraq", $this->lang_key),
			103	=> __("Ireland", $this->lang_key),
			104	=> __("Israel", $this->lang_key),
			105	=> __("Italy", $this->lang_key),
			106	=> __("Jamaica", $this->lang_key),
			107	=> __("Japan", $this->lang_key),
			108	=> __("Jordan", $this->lang_key),
			109	=> __("Kazakhstan", $this->lang_key),
			110	=> __("Kenya", $this->lang_key),
			111	=> __("Kiribati", $this->lang_key),
			112	=> __("Korea, Democratic Peoples Republic of", $this->lang_key),
			113	=> __("Korea, Republic of", $this->lang_key),
			114	=> __("Kuwait", $this->lang_key),
			115	=> __("Kyrgyzstan", $this->lang_key),
			116	=> __("Lao Peoples Democratic Republic", $this->lang_key),
			117	=> __("Latvia", $this->lang_key),
			118	=> __("Lebanon", $this->lang_key),
			119	=> __("Lesotho", $this->lang_key),
			120	=> __("Liberia", $this->lang_key),
			121	=> __("Libyan Arab Jamahiriya", $this->lang_key),
			122	=> __("Liechtenstein", $this->lang_key),
			123	=> __("Lithuania", $this->lang_key),
			124	=> __("Luxembourg", $this->lang_key),
			125	=> __("Macau", $this->lang_key),
			126	=> __("Macedonia, The Former Yugoslav Republic of", $this->lang_key),
			127	=> __("Madagascar", $this->lang_key),
			128	=> __("Malawi", $this->lang_key),
			129	=> __("Malaysia", $this->lang_key),
			130	=> __("Maldives", $this->lang_key),
			131	=> __("Mali", $this->lang_key),
			132	=> __("Malta", $this->lang_key),
			133	=> __("Marshall Islands", $this->lang_key),
			134	=> __("Martinique", $this->lang_key),
			135	=> __("Mauritania", $this->lang_key),
			136	=> __("Mauritius", $this->lang_key),
			137	=> __("Mayotte", $this->lang_key),
			138	=> __("Mexico", $this->lang_key),
			139	=> __("Micronesia, Federated States of", $this->lang_key),
			140	=> __("Moldova, Republic of", $this->lang_key),
			141	=> __("Monaco", $this->lang_key),
			142	=> __("Mongolia", $this->lang_key),
			143	=> __("Montserrat", $this->lang_key),
			144	=> __("Morocco", $this->lang_key),
			145	=> __("Mozambique", $this->lang_key),
			146	=> __("Myanmar", $this->lang_key),
			147	=> __("Namibia", $this->lang_key),
			148	=> __("Nauru", $this->lang_key),
			149	=> __("Nepal", $this->lang_key),
			150	=> __("Netherlands", $this->lang_key),
			151	=> __("Netherlands Antilles", $this->lang_key),
			152	=> __("New Caledonia", $this->lang_key),
			153	=> __("New Zealand", $this->lang_key),
			154	=> __("Nicaragua", $this->lang_key),
			155	=> __("Niger", $this->lang_key),
			156	=> __("Nigeria", $this->lang_key),
			157	=> __("Niue", $this->lang_key),
			158	=> __("Norfolk Island", $this->lang_key),
			159	=> __("Northern Mariana Islands", $this->lang_key),
			160	=> __("Norway", $this->lang_key),
			161	=> __("Oman", $this->lang_key),
			162	=> __("Pakistan", $this->lang_key),
			163	=> __("Palau", $this->lang_key),
			164	=> __("Panama", $this->lang_key),
			165	=> __("Papua New Guinea", $this->lang_key),
			166	=> __("Paraguay", $this->lang_key),
			167	=> __("Peru", $this->lang_key),
			168	=> __("Philippines", $this->lang_key),
			169	=> __("Pitcairn", $this->lang_key),
			170	=> __("Poland", $this->lang_key),
			171	=> __("Portugal", $this->lang_key),
			172	=> __("Puerto Rico", $this->lang_key),
			173	=> __("Qatar", $this->lang_key),
			174	=> __("Reunion", $this->lang_key),
			175	=> __("Romania", $this->lang_key),
			176	=> __("Russian Federation", $this->lang_key),
			177	=> __("Rwanda", $this->lang_key),
			178	=> __("Saint Kitts and Nevis", $this->lang_key),
			179	=> __("Saint Lucia", $this->lang_key),
			180	=> __("Saint Vincent and the Grenadines", $this->lang_key),
			181	=> __("Samoa", $this->lang_key),
			182	=> __("San Marino", $this->lang_key),
			183	=> __("Sao Tome and Principe", $this->lang_key),
			184	=> __("Saudi Arabia", $this->lang_key),
			185	=> __("Senegal", $this->lang_key),
			186	=> __("Seychelles", $this->lang_key),
			187	=> __("Sierra Leone", $this->lang_key),
			188	=> __("Singapore", $this->lang_key),
			189	=> __("Slovakia (Slovak Republic)", $this->lang_key),
			190	=> __("Slovenia", $this->lang_key),
			191	=> __("Solomon Islands", $this->lang_key),
			192	=> __("Somalia", $this->lang_key),
			193	=> __("South Africa", $this->lang_key),
			194	=> __("South Georgia and the South Sandwich Islands", $this->lang_key),
			195	=> __("Spain", $this->lang_key),
			196	=> __("Sri Lanka", $this->lang_key),
			197	=> __("St. Helena", $this->lang_key),
			198	=> __("St. Pierre and Miquelon", $this->lang_key),
			199	=> __("Sudan", $this->lang_key),
			200	=> __("Suriname", $this->lang_key),
			201	=> __("Svalbard", $this->lang_key),
			202	=> __("Swaziland", $this->lang_key),
			203	=> __("Sweden", $this->lang_key),
			204	=> __("Switzerland", $this->lang_key),
			205	=> __("Syrian Arab Republic", $this->lang_key),
			206	=> __("Taiwan", $this->lang_key),
			207	=> __("Tajikistan", $this->lang_key),
			208	=> __("Tanzania, United Republic of", $this->lang_key),
			209	=> __("Thailand", $this->lang_key),
			210	=> __("Togo", $this->lang_key),
			211	=> __("Tokelau", $this->lang_key),
			212	=> __("Tonga", $this->lang_key),
			213	=> __("Trinidad and Tobago", $this->lang_key),
			214	=> __("Tunisia", $this->lang_key),
			215	=> __("Turkey", $this->lang_key),
			216	=> __("Turkmenistan", $this->lang_key),
			217	=> __("Turks and Caicos Islands", $this->lang_key),
			218	=> __("Tuvalu", $this->lang_key),
			219	=> __("Uganda", $this->lang_key),
			220	=> __("Ukraine", $this->lang_key),
			221	=> __("United Arab Emirates", $this->lang_key),
			222	=> __("United Kingdom", $this->lang_key),
			223	=> __("United States", $this->lang_key),
			224	=> __("United States Minor Outlying Islands", $this->lang_key),
			225	=> __("Uruguay", $this->lang_key),
			226	=> __("Uzbekistan", $this->lang_key),
			227	=> __("Vanuatu", $this->lang_key),
			228	=> __("Vatican City State (Holy See)", $this->lang_key),
			229	=> __("Venezuela", $this->lang_key),
			230	=> __("Vietnam", $this->lang_key),
			231	=> __("Virgin Islands (British)", $this->lang_key),
			232	=> __("Virgin Islands (U.S.)", $this->lang_key),
			233	=> __("Wallis and Futuna Islands", $this->lang_key),
			234	=> __("Western Sahara", $this->lang_key),
			235	=> __("Yemen", $this->lang_key),
			236	=> __("Yugoslavia", $this->lang_key),
			237	=> __("Zaire", $this->lang_key),
			238	=> __("Zambia", $this->lang_key),
			239	=> __("Zimbabwe", $this->lang_key),
		);

		foreach($arr_countries as $key => $value)
		{
			if(!in_array($key, $data['exclude']))
			{
				$arr_data[$key] = $value;
			}
		}

		return $arr_data;
	}

	function search($data)
	{
		global $wpdb;

		$result = array();

		switch($data['type'])
		{
			case 'sms':
				$result = $wpdb->get_results("SELECT addressCellNo FROM ".get_address_table_prefix()."address WHERE addressCellNo != '' AND (addressFirstName LIKE '%".$data['string']."%' OR addressSurName LIKE '%".$data['string']."%' OR CONCAT(addressFirstName, ' ', addressSurName) LIKE '%".$data['string']."%' OR REPLACE(REPLACE(REPLACE(addressCellNo, '/', ''), '-', ''), ' ', '') LIKE '%".$data['string']."%') GROUP BY addressCellNo ORDER BY addressSurName ASC, addressFirstName ASC");
			break;
		}

		return $result;
	}

	function get_address($id)
	{
		global $wpdb;

		if($id > 0)
		{
			$this->id = $id;
		}

		return $wpdb->get_var($wpdb->prepare("SELECT addressEmail FROM ".get_address_table_prefix()."address WHERE addressID = '%d'", $this->id));
	}

	function get_address_id($data)
	{
		global $wpdb;

		$this->id = $wpdb->get_var($wpdb->prepare("SELECT addressID FROM ".get_address_table_prefix()."address WHERE addressEmail = %s", $data['email']));
	}

	function insert($data)
	{
		global $wpdb;

		if(!isset($data['public'])){	$data['public'] = false;}

		if($data['email'] != '')
		{
			$wpdb->query($wpdb->prepare("INSERT INTO ".get_address_table_prefix()."address SET addressPublic = '%d', addressEmail = %s, addressCreated = NOW(), userID = '%d'", $data['public'], $data['email'], get_current_user_id()));

			return $wpdb->insert_id;
		}
	}

	function restore($id = 0)
	{
		global $wpdb;

		if($id > 0)
		{
			$this->id = $id;
		}

		$wpdb->query($wpdb->prepare("UPDATE ".get_address_table_prefix()."address SET addressDeleted = '0', addressDeletedID = '%d', addressDeletedDate = NOW() WHERE addressID = '%d'", get_current_user_id(), $this->id));

		return ($wpdb->rows_affected > 0);
	}

	function trash($data = array()) //$id = 0
	{
		global $wpdb;

		if(!isset($data['address_id'])){	$data['address_id'] = 0;}
		if(!isset($data['force_admin'])){	$data['force_admin'] = false;}

		if($data['address_id'] > 0)
		{
			$this->id = $data['address_id'];
		}

		$wpdb->query($wpdb->prepare("UPDATE ".get_address_table_prefix()."address SET addressDeleted = '1', addressDeletedID = '%d', addressDeletedDate = NOW() WHERE addressID = '%d'".(IS_ADMIN || $data['force_admin'] ? "" : " AND addressPublic = '0' AND userID = '".get_current_user_id()."'"), get_current_user_id(), $this->id));

		return ($wpdb->rows_affected > 0);
	}

	function delete($id = 0)
	{
		global $wpdb;

		if($id > 0)
		{
			$this->id = $id;
		}

		$wpdb->query($wpdb->prepare("DELETE FROM ".get_address_table_prefix()."address WHERE addressID = '%d'", $this->id));

		return ($rows_affected > 0);
	}

	function update_errors($data = array())
	{
		global $wpdb;

		if(!isset($data['action'])){		$data['action'] = "";}

		$address_error = "";

		switch($data['action'])
		{
			case 'reset':
				$address_error = "0";
			break;

			case 'increase':
				$address_error = "(addressError + 1)";
			break;
		}

		if($address_error != '')
		{
			$wpdb->query($wpdb->prepare("UPDATE ".get_address_table_prefix()."address SET addressError = 0 WHERE addressID = '%d'", $this->id));
		}
	}
}

if(!class_exists('mf_list_table'))
{
	require_once(ABSPATH."wp-content/plugins/mf_base/include/classes.php");
}

class mf_address_table extends mf_list_table
{
	function set_default()
	{
		$this->arr_settings['query_from'] = get_address_table_prefix()."address";
		$this->post_type = '';

		$this->arr_settings['query_select_id'] = "addressID";
		$this->arr_settings['query_all_id'] = "0";
		$this->arr_settings['query_trash_id'] = "1";
		$this->orderby_default = "addressSurName";

		$this->arr_settings['has_autocomplete'] = true;
		$this->arr_settings['plugin_name'] = 'mf_address';
	}

	function init_fetch()
	{
		global $wpdb, $obj_address, $obj_group;

		if(!IS_ADMIN)
		{
			$this->query_where .= ($this->query_where != '' ? " AND " : "")."(addressPublic = '1' OR addressPublic = '0' AND userID = '".get_current_user_id()."')";
		}

		if($this->search != '')
		{
			@list($first_name, $sur_name) = explode(" ", $this->search);

			$this->query_where .= ($this->query_where != '' ? " AND " : "")."("
				."addressBirthDate LIKE '%".$this->search."%'"
				//." OR addressFirstName LIKE '%".$this->search."%'"
				//." OR addressSurName LIKE '%".$this->search."%'"
				." OR CONCAT(addressFirstName, ' ', addressSurName) LIKE '%".$this->search."%'"
				." OR addressFirstName LIKE '%".$first_name."%' AND addressSurName LIKE '%".$sur_name."%'"
				." OR addressAddress LIKE '%".$this->search."%'"
				." OR addressZipCode LIKE '%".$this->search."%'"
				." OR addressCity LIKE '%".$this->search."%'"
				." OR addressTelNo LIKE '%".$this->search."%'"
				." OR addressWorkNo LIKE '%".$this->search."%'"
				." OR addressCellNo LIKE '%".$this->search."%'"
				." OR addressEmail LIKE '%".$this->search."%'"
				." OR SOUNDEX(CONCAT(addressFirstName, ' ', addressSurName)) = SOUNDEX('".$this->search."')"
				." OR SOUNDEX(addressFirstName) = SOUNDEX('".$first_name."') AND SOUNDEX(addressSurName) = SOUNDEX('".$sur_name."')"
				." OR SOUNDEX(addressAddress) = SOUNDEX('".$this->search."')"
				." OR SOUNDEX(addressCity) = SOUNDEX('".$this->search."')"
			.")";

			/*$this->query_where .= ($this->query_where != '' ? " AND " : "")."("
				."MATCH (addressFirstName, addressSurName, addressAddress, addressZipCode, addressCity, addressTelNo, addressWorkNo, addressCellNo, addressEmail) AGAINST ('%".$this->search."%' IN BOOLEAN MODE)"
				." OR addressFirstName LIKE '%".$first_name."%' AND addressSurName LIKE '%".$sur_name."%'"
			.")";*/
		}

		if(get_option('setting_address_api_url') != '')
		{
			$strFilterIsSynced = get_or_set_table_filter(array('key' => 'strFilterIsSynced', 'save' => true));

			switch($strFilterIsSynced)
			{
				case 'yes':
					$this->query_where .= ($this->query_where != '' ? " AND " : "")."addressSyncedDate >= '".DEFAULT_DATE."'";
				break;

				case 'no':
					$this->query_where .= ($this->query_where != '' ? " AND " : "")."(addressSyncedDate IS null OR addressSyncedDate < '".DEFAULT_DATE."')";
				break;
			}
		}

		$intGroupID = get_or_set_table_filter(array('key' => 'intGroupID'));

		if($intGroupID > 0)
		{
			$strFilterIsMember = get_or_set_table_filter(array('key' => 'strFilterIsMember'));
			$strFilterAccepted = get_or_set_table_filter(array('key' => 'strFilterAccepted'));
			$strFilterUnsubscribed = get_or_set_table_filter(array('key' => 'strFilterUnsubscribed'));

			if($strFilterIsMember != '' || $strFilterAccepted != '' || $strFilterUnsubscribed != '')
			{
				$this->query_join .= " LEFT JOIN ".$wpdb->prefix."address2group USING (addressID)";
			}

			switch($strFilterIsMember)
			{
				case 'yes':
					$this->query_where .= ($this->query_where != '' ? " AND " : "")."groupID = '".$intGroupID."'";
				break;

				case 'no':
					$this->query_where .= ($this->query_where != '' ? " AND " : "")."groupID != '".$intGroupID."'";
				break;

				default:
					if($strFilterAccepted != '' || $strFilterUnsubscribed != '')
					{
						$this->query_where .= ($this->query_where != '' ? " AND " : "")."groupID = '".$intGroupID."'";
					}
				break;
			}

			switch($strFilterAccepted)
			{
				case 'yes':
					$this->query_where .= ($this->query_where != '' ? " AND " : "")."groupAccepted = '1'";
				break;

				case 'no':
					$this->query_where .= ($this->query_where != '' ? " AND " : "")."groupAccepted = '0'";
				break;
			}

			switch($strFilterUnsubscribed)
			{
				case 'yes':
					$this->query_where .= ($this->query_where != '' ? " AND " : "")."groupUnsubscribed = '1'";
				break;

				case 'no':
					$this->query_where .= ($this->query_where != '' ? " AND " : "")."groupUnsubscribed = '0'";
				break;
			}
		}

		if(!IS_EDITOR)
		{
			$meta_address_permission = get_user_meta(get_current_user_id(), 'meta_address_permission', true);

			$this->query_where .= ($this->query_where != '' ? " AND " : "")."addressExtra IN('".str_replace(",", "','", $meta_address_permission)."')";
		}

		$this->set_views(array(
			'db_field' => 'addressDeleted',
			'types' => array(
				'0' => __("All", $obj_address->lang_key),
				'1' => __("Trash", $obj_address->lang_key),
			),
		));

		$arr_columns = array(
			'cb' => '<input type="checkbox">',
		);

		$arr_columns['addressSurName'] = __("Name", $obj_address->lang_key);
		$arr_columns['addressAddress'] = __("Address", $obj_address->lang_key);
		$arr_columns['addressIcons'] = __("Information", $obj_address->lang_key);

		if($intGroupID > 0)
		{
			$arr_columns['is_part_of_group'] = "<span class='nowrap'><i class='fa fa-plus-square'></i> / <i class='fa fa-minus-square'></i></span>";
		}

		$arr_columns['addressError'] = "";
		$arr_columns['addressContact'] = __("Contact", $obj_address->lang_key);
		$arr_columns['addressExtra'] = get_option_or_default('setting_address_extra', __("Extra", $obj_address->lang_key));

		$this->set_columns($arr_columns);

		$this->set_sortable_columns(array(
			'addressSurName',
			'addressExtra'
		));
	}

	function column_cb($item)
	{
		return "<input type='checkbox' name='".$this->arr_settings['query_from']."[]' value='".$item[$this->arr_settings['query_select_id']]."'>";
	}

	function get_bulk_actions()
	{
		global $obj_address;

		$actions = array();

		if(isset($this->columns['cb']))
		{
			if(!isset($_GET['addressDeleted']) || $_GET['addressDeleted'] != 1)
			{
				$actions['trash'] = __("Delete", $obj_address->lang_key);
			}

			else
			{
				$actions['restore'] = __("Restore", $obj_address->lang_key);
				$actions['delete'] = __("Permanently Delete", $obj_address->lang_key);
			}

			if(IS_ADMIN)
			{
				$actions['merge'] = __("Merge", $obj_address->lang_key);
			}
		}

		return $actions;
	}

	function process_bulk_action()
	{
		if(isset($_GET['_wpnonce']) && !empty($_GET['_wpnonce']))
		{
			switch($this->current_action())
			{
				case 'trash':
					$this->bulk_trash();
				break;

				case 'restore':
					$this->bulk_restore();
				break;

				case 'delete':
					$this->bulk_delete();
				break;

				case 'merge':
					$this->bulk_merge();
				break;
			}
		}
	}

	function bulk_trash()
	{
		global $obj_address;

		$arr_ids = check_var($this->arr_settings['query_from'], 'array');

		if(count($arr_ids) > 0)
		{
			foreach($arr_ids as $id)
			{
				$obj_address->trash(array('address_id' => $id));
			}
		}
	}

	function bulk_restore()
	{
		global $obj_address;

		$arr_ids = check_var($this->arr_settings['query_from'], 'array');

		if(count($arr_ids) > 0)
		{
			foreach($arr_ids as $id)
			{
				$obj_address->restore($id);
			}
		}
	}

	function bulk_delete()
	{
		global $obj_address;

		$arr_ids = check_var($this->arr_settings['query_from'], 'array');

		if(count($arr_ids) > 0)
		{
			foreach($arr_ids as $id)
			{
				$obj_address->delete($id);
			}
		}
	}

	function bulk_merge()
	{
		global $obj_address, $error_text, $done_text;

		$arr_ids = check_var($this->arr_settings['query_from'], 'array');

		if(!isset($obj_address))
		{
			$obj_address = new mf_address();
		}

		$obj_address->do_merge(array('ids' => $arr_ids));

		echo get_notification();
	}

	function column_default($item, $column_name)
	{
		global $wpdb, $obj_address, $obj_group;

		$out = "";

		if(!isset($obj_address))
		{
			$obj_address = new mf_address();
		}

		$intAddressID = $item['addressID'];

		switch($column_name)
		{
			case 'addressSurName':
				$intAddressPublic = $item['addressPublic'];
				$strAddressBirthDate = $item['addressBirthDate'];
				$strAddressFirstName = $item['addressFirstName'];
				$strAddressSurName = $item['addressSurName'];
				$intAddressMemberID = $item['addressMemberID'];
				$intAddressDeleted = $item['addressDeleted'];
				$dteAddressDeletedDate = $item['addressDeletedDate'];
				$intAddressDeletedID = $item['addressDeletedID'];

				if($strAddressFirstName != '' || $strAddressSurName != '')
				{
					$strAddressName = $strAddressFirstName." ".$strAddressSurName;
				}

				else
				{
					$strAddressName = "(".__("Unknown", $obj_address->lang_key).")";
				}

				$post_edit_url = admin_url("admin.php?page=mf_address/create/index.php&intAddressID=".$intAddressID);
				$list_url = admin_url("admin.php?page=mf_address/list/index.php&intAddressID=".$intAddressID);

				$actions = array();

				if($intAddressDeleted == 0)
				{
					if($intAddressPublic == 0 || IS_ADMIN)
					{
						$actions['edit'] = "<a href='".$post_edit_url."'>".__("Edit", $obj_address->lang_key)."</a>";

						$actions['delete'] = "<a href='".wp_nonce_url($list_url."&btnAddressDelete", 'address_delete_'.$intAddressID, '_wpnonce_address_delete')."'>".__("Delete", $obj_address->lang_key)."</a>";
					}
				}

				else
				{
					$actions['recover'] = "<a href='".wp_nonce_url($list_url."&btnAddressRestore", 'address_restore_'.$intAddressID, '_wpnonce_address_restore')."' title='".sprintf(__("Removed %s by %s", $obj_address->lang_key), format_date($dteAddressDeletedDate), get_user_info(array('id' => $intAddressDeletedID)))."'>".__("Restore", $obj_address->lang_key)."</a>";
				}

				if($intAddressMemberID > 0)
				{
					$actions['member_id'] = $intAddressMemberID;
				}

				if($strAddressBirthDate != '')
				{
					$actions['birth_date'] = $strAddressBirthDate;
				}

				$out .= "<a href='".$post_edit_url."'>"
					.$strAddressName
				."</a>"
				.$this->row_actions($actions);
			break;

			case 'addressAddress':
				$strAddressAddress = $item['addressAddress'];
				$strAddressCo = $item['addressCo'];
				$intAddressZipCode = $item['addressZipCode'] > 0 ? $item['addressZipCode'] : "";
				$strAddressCity = $item['addressCity'];
				$intAddressCountry = $item['addressCountry'];

				if($strAddressAddress != '')
				{
					$out .= $strAddressAddress."<br>";
				}

				$out .= $intAddressZipCode." ".$strAddressCity;

				if($intAddressCountry > 0)
				{
					$arr_countries = $obj_address->get_countries_for_select();

					if(isset($arr_countries[$intAddressCountry]))
					{
						$out .= " (".$arr_countries[$intAddressCountry].")";
					}
				}
			break;

			case 'addressIcons':
				$intAddressDeleted = $item['addressDeleted'];

				if(IS_ADMIN)
				{
					$out .= ($out != '' ? "&nbsp;" : "")."<i class='".($item['addressPublic'] == 1 ? "fa fa-check green" : "fa fa-times red")." fa-lg' title='".($item['addressPublic'] == 1 ? __("Public", $obj_address->lang_key) : __("Not Public", $obj_address->lang_key))."'></i>";

					if($intAddressDeleted == 0 && $obj_address->has_duplicate(array('item' => $item)))
					{
						$list_url = admin_url("admin.php?page=mf_address/list/index.php&intAddressID=".$intAddressID);

						$str_ids = "";

						foreach($obj_address->result_duplicate as $r)
						{
							$str_ids .= ($str_ids != '' ? "," : "").$r->addressID;
						}

						$out .= ($out != '' ? "&nbsp;" : "")."<a href='".wp_nonce_url($list_url."&btnAddressMerge&intAddressID=".$intAddressID."&is_public=".($item['addressPublic'] == 1)."&ids=".$str_ids."&paged=".check_var('paged'), 'address_merge_'.$intAddressID, '_wpnonce_address_merge')."' rel='confirm'>
							<i class='far fa-clone red fa-lg' title='".sprintf(__("Merge with %d other", $obj_address->lang_key), count($obj_address->result_duplicate))."'></i>
						</a>";
					}
				}

				if(get_option('setting_address_api_url') != '')
				{
					if(isset($item['addressSyncedDate']) && $item['addressSyncedDate'] > DEFAULT_DATE)
					{
						$out .= ($out != '' ? "&nbsp;" : "")."<i class='fas fa-network-wired' title='".sprintf(__("Syncronized %s", $obj_address->lang_key), format_date($item['addressSyncedDate']))."'></i>";
					}
				}

				if(function_exists('is_plugin_active') && is_plugin_active("mf_group/index.php") && isset($obj_group))
				{
					$str_groups = "";

					$resultGroups = $wpdb->get_results($wpdb->prepare("SELECT groupID FROM ".$wpdb->posts." INNER JOIN ".$wpdb->prefix."address2group ON ".$wpdb->posts.".ID = ".$wpdb->prefix."address2group.groupID WHERE addressID = '%d' AND post_type = %s AND post_status NOT IN ('trash', 'ignore') GROUP BY groupID", $intAddressID, $obj_group->post_type));

					foreach($resultGroups as $r)
					{
						$str_groups .= ($str_groups != '' ? "\n" : "").$obj_group->get_name(array('id' => $r->groupID));
					}

					if($str_groups != '')
					{
						$out .= ($out != '' ? "&nbsp;" : "")."<i class='fa fa-users' title='".$str_groups."'></i>";
					}
				}
			break;

			case 'is_part_of_group':
				$intGroupID = get_or_set_table_filter(array('key' => 'intGroupID'));

				if($intGroupID > 0)
				{
					$list_url = admin_url("admin.php?page=mf_address/list/index.php&intAddressID=".$intAddressID."&intGroupID=".$intGroupID);

					$intGroupID_check = $intGroupAccepted = $intGroupUnsubscribed = 0;

					$result_check = $wpdb->get_results($wpdb->prepare("SELECT groupID, groupAccepted, groupUnsubscribed FROM ".$wpdb->prefix."address2group WHERE addressID = '%d' AND groupID = '%d' LIMIT 0, 1", $intAddressID, $intGroupID));

					foreach($result_check as $r)
					{
						$intGroupID_check = $r->groupID;
						$intGroupAccepted = $r->groupAccepted;
						$intGroupUnsubscribed = $r->groupUnsubscribed;
					}

					if($intGroupID == $intGroupID_check)
					{
						if($intGroupUnsubscribed == 0)
						{
							$out .= "<a href='".wp_nonce_url($list_url."&btnAddressRemove", 'address_remove_'.$intAddressID.'_'.$intGroupID, '_wpnonce_address_remove')."' rel='confirm'>
								<i class='fa fa-minus-square fa-lg red'></i>
							</a>";

							if($intGroupAccepted == 0)
							{
								$out .= "&nbsp;";

								if(IS_SUPER_ADMIN)
								{
									$out .= "<a href='".wp_nonce_url($list_url."&btnAddressAccept", 'address_accept_'.$intAddressID.'_'.$intGroupID, '_wpnonce_address_accept')."' rel='confirm'>
										<i class='fa fa-check-square fa-lg grey' title='".__("The address has not been accepted to this group yet.", $obj_address->lang_key)." ".__("Do you want to manually accept it?", $obj_address->lang_key)."'></i>
									</a>";

									if(get_post_meta($intGroupID, 'group_reminder_subject') != '' && get_post_meta($intGroupID, 'group_reminder_text') != '')
									{
										if(function_exists('is_plugin_active') && is_plugin_active("mf_group/index.php") && isset($obj_group) && $obj_group->is_allowed2send_reminder(array('address_id' => $intAddressID, 'group_id' => $intGroupID)))
										{
											$out .= "<a href='".wp_nonce_url($list_url."&btnAddressResend", 'address_resend_'.$intAddressID.'_'.$intGroupID, '_wpnonce_address_resend')."' rel='confirm'>
												<i class='fa fa-recycle fa-lg' title='".__("The address has not been accepted to this group yet.", $obj_address->lang_key)." ".__("Do you want to send it again?", $obj_address->lang_key)."'></i>
											</a>";
										}
									}
								}

								else
								{
									$out .= "<i class='fa fa-info-circle fa-lg' title='".__("The address has not been accepted to this group yet.", $obj_address->lang_key)."'></i>";
								}
							}
						}

						else
						{
							$out .= "<a href='".wp_nonce_url($list_url."&btnAddressRemove", 'address_remove_'.$intAddressID.'_'.$intGroupID, '_wpnonce_address_remove')."' rel='confirm' title='".__("The address has been unsubscribed", $obj_address->lang_key)."'>
								<span class='fa-stack fa-lg'>
									<i class='fa fa-envelope fa-stack-1x'></i>
									<i class='fa fa-ban fa-stack-2x red'></i>
								</span>
							</a>";
						}
					}

					else
					{
						$out .= "<a href='".wp_nonce_url($list_url."&btnAddressAdd", 'address_add_'.$intAddressID.'_'.$intGroupID, '_wpnonce_address_add')."'>
							<i class='fa fa-plus-square fa-lg green'></i>
						</a>";
					}
				}
			break;

			case 'addressError':
				if($item['addressError'] > 0)
				{
					$out .= "<i class='fa fa-times red' title='".$item['addressError']." ".__("Errors", $obj_address->lang_key)."'></i>";
				}
			break;

			case 'addressContact':
				$strAddressEmail = $item['addressEmail'];
				$strAddressTelNo = $item['addressTelNo'];
				$strAddressCellNo = $item['addressCellNo'];
				$strAddressWorkNo = $item['addressWorkNo'];

				if($strAddressEmail != '')
				{
					$out .= "<a href='mailto:".$strAddressEmail."'>".$strAddressEmail."</a><br>";
				}

				$str_numbers = "";

				if($strAddressCellNo != '' && strpos($str_numbers, $strAddressCellNo) === false)
				{
					$str_numbers .= ($str_numbers != '' ? " | " : "")."<a href='".format_phone_no($strAddressCellNo)."'>".$strAddressCellNo."</a>";
				}

				if($strAddressTelNo != '' && strpos($str_numbers, $strAddressTelNo) === false)
				{
					$str_numbers .= ($str_numbers != '' ? " | " : "")."<a href='".format_phone_no($strAddressTelNo)."'>".$strAddressTelNo."</a>";
				}

				if($strAddressWorkNo != '' && strpos($str_numbers, $strAddressWorkNo) === false)
				{
					$str_numbers .= ($str_numbers != '' ? " | " : "")."<a href='".format_phone_no($strAddressWorkNo)."'>".$strAddressWorkNo."</a>";
				}

				$out .= $str_numbers;
			break;

			default:
				if(isset($item[$column_name]))
				{
					$out .= $item[$column_name];
				}
			break;
		}

		return $out;
	}
}

class mf_address_import extends mf_import
{
	function get_defaults()
	{
		global $obj_address;

		$this->prefix = get_address_table_prefix();
		$this->table = "address";

		$this->columns = array(
			'addressBirthDate' => __("Social Security Number", $obj_address->lang_key),
			'addressFirstName' => __("First Name", $obj_address->lang_key),
			'addressSurName' => __("Last Name", $obj_address->lang_key),
			'addressCo' => __("C/O", $obj_address->lang_key),
			'addressAddress' => __("Address", $obj_address->lang_key),
			'addressZipCode' => __("Zip Code", $obj_address->lang_key),
			'addressCity' => __("City", $obj_address->lang_key),
			'addressCountry' => __("Country", $obj_address->lang_key),
			'addressTelNo' => __("Phone Number", $obj_address->lang_key),
			'addressWorkNo' => __("Work Number", $obj_address->lang_key),
			'addressCellNo' => __("Mobile Number", $obj_address->lang_key),
			'addressEmail' => __("E-mail", $obj_address->lang_key),
			'addressExtra' => get_option_or_default('setting_address_extra', __("Extra", $obj_address->lang_key)),
		);

		$this->unique_columns = array(
			'addressBirthDate',
			'addressEmail',
		);

		$this->validate_columns = array(
			'addressTelNo' => 'telno',
			'addressWorkNo' => 'telno',
			'addressCellNo' => 'telno',
			'addressEmail' => 'email',
		);

		if(get_option('setting_address_display_member_id', 'yes') == 'yes')
		{
			$this->columns['addressMemberID'] = __("Member ID", $obj_address->lang_key);

			$this->unique_columns[] = 'addressMemberID';
		}
	}

	function if_more_than_one($id)
	{
		global $wpdb, $obj_address;

		$wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".$wpdb->prefix."address2group WHERE addressID = '%d' LIMIT 0, 1", $id));

		if($wpdb->num_rows == 0)
		{
			if(!isset($obj_address))
			{
				$obj_address = new mf_address();
			}

			if($obj_address->trash(array('address_id' => $id)))
			{
				$this->rows_deleted++;
			}
		}
	}

	function inserted_new($id)
	{
		$obj_group = new mf_group();
		$obj_group->add_address(array('address_id' => $id, 'group_id' => get_option('setting_group_import')));
	}
}

class mf_address_export extends mf_export
{
	function get_defaults()
	{
		$this->plugin = "mf_address";
		$this->name = "address";
	}

	function get_columns_for_select()
	{
		global $obj_address;

		$arr_data = array(
			'addressMemberID' => __("Member ID", $obj_address->lang_key),
			'addressBirthDate' => __("Social Security Number", $obj_address->lang_key),
			'addressFirstName' => __("First Name", $obj_address->lang_key),
			'addressSurName' => __("Last Name", $obj_address->lang_key),
			'addressCo' => __("C/O", $obj_address->lang_key),
			'addressAddress' => __("Address", $obj_address->lang_key),
			'addressZipCode' => __("Zip Code", $obj_address->lang_key),
			'addressCity' => __("City", $obj_address->lang_key),
			'addressCountry' => __("Country", $obj_address->lang_key),
			'addressTelNo' => __("Phone Number", $obj_address->lang_key),
			'addressWorkNo' => __("Work Number", $obj_address->lang_key),
			'addressCellNo' => __("Mobile Number", $obj_address->lang_key),
			'addressEmail' => __("E-mail", $obj_address->lang_key),
			'addressExtra' => get_option_or_default('setting_address_extra', __("Extra", $obj_address->lang_key)),
		);

		return $arr_data;
	}

	function fetch_request_xtra()
	{
		$this->arr_columns = check_var('arrColumns');
	}

	function get_export_data()
	{
		global $wpdb, $obj_address;

		if(!is_array($this->arr_columns) || count($this->arr_columns) == 0 || in_array('addressCountry', $this->arr_columns))
		{
			if(!isset($obj_address))
			{
				$obj_address = new mf_address();
			}

			$arr_countries = $obj_address->get_countries_for_select();
		}

		$result = $wpdb->get_results($wpdb->prepare("SELECT ".(count($this->arr_columns) > 0 ? implode(", ", $this->arr_columns) : "*")." FROM ".get_address_table_prefix()."address WHERE addressDeleted = '%d' GROUP BY addressID ORDER BY addressPublic ASC, addressSurName ASC, addressFirstName ASC", 0), ARRAY_A);

		if($wpdb->num_rows > 0)
		{
			$arr_columns = $this->get_columns_for_select();

			$data_temp = array();

			foreach($arr_columns as $key => $value)
			{
				if(!is_array($this->arr_columns) || count($this->arr_columns) == 0 || in_array($key, $this->arr_columns))
				{
					$data_temp[] = $arr_columns[$key];
				}
			}

			$this->data[] = $data_temp;

			foreach($result as $r)
			{
				$data_temp = array();

				$has_data = false;

				foreach($arr_columns as $key => $value)
				{
					if(!is_array($this->arr_columns) || count($this->arr_columns) == 0 || in_array($key, $this->arr_columns))
					{
						switch($key)
						{
							case 'addressMemberID':
							case 'addressZipCode':
								if($r[$key] > 0)
								{
									$data_temp[] = $r[$key];

									$has_data = true;
								}

								else
								{
									$data_temp[] = "";
								}
							break;

							case 'addressCountry':
								if($r[$key] > 0 && isset($arr_countries[$r[$key]]))
								{
									$data_temp[] = $arr_countries[$r[$key]];

									$has_data = true;
								}

								else
								{
									$data_temp[] = "";
								}
							break;

							default:
								if($r[$key] != '')
								{
									$has_data = true;
								}

								$data_temp[] = $r[$key];
							break;
						}
					}
				}

				if($has_data == true)
				{
					$this->data[] = $data_temp;
				}
			}
		}
	}

	function get_form_xtra()
	{
		global $obj_address;

		$out = show_select(array('data' => $this->get_columns_for_select(), 'name' => 'arrColumns[]', 'text' => __("Columns", $obj_address->lang_key), 'value' => $this->arr_columns));

		return $out;
	}
}