<?php

class mf_address
{
	var $id = 0;
	var $type;
	var $post_type = 'mf_address';
	var $meta_prefix;
	var $query_where = "";
	var $group_id;
	var $ids;
	var $is_public;
	var $result_duplicate;
	var $public;
	var $member_id;
	var $birthdate;
	var $first_name;
	var $sur_name;
	var $address;
	var $co;
	var $zipcode;
	var $city;
	var $country;
	var $telno;
	var $cellno;
	var $workno;
	var $email;
	var $extra;
	var $arr_columns = array('addressMemberID', 'addressBirthDate', 'addressFirstName', 'addressSurName', 'addressCo', 'addressAddress', 'addressZipCode', 'addressCity', 'addressCountry', 'addressTelNo', 'addressWorkNo', 'addressCellNo', 'addressEmail');
	var $arr_unique_columns = array('addressBirthDate', 'addressEmail');

	function __construct($data = [])
	{
		if(isset($data['id']) && $data['id'] > 0)
		{
			$this->id = $data['id'];
		}

		else if(function_exists('check_var')) //MF Base might not be loaded yet
		{
			$this->id = check_var('intAddressID');
		}

		$this->type = (isset($data['type']) ? $data['type'] : '');

		$this->meta_prefix = $this->post_type.'_';

		$setting_address_extra_field = get_option('setting_address_extra_field');

		if(is_array($setting_address_extra_field) && count($setting_address_extra_field) > 0)
		{
			$this->arr_columns[] = 'addressExtra';
		}

		if(get_option('setting_address_display_member_id', 'yes') == 'yes')
		{
			$this->arr_unique_columns[] = 'addressMemberID';
		}
	}

	function get_columns_for_select($data = [])
	{
		if(!isset($data['add_choose_here'])){	$data['add_choose_here'] = false;}

		$arr_data = [];

		if($data['add_choose_here'] == true)
		{
			$arr_data[''] = "-- ".__("Choose Here", 'lang_address')." --";
		}

		if(get_option('setting_address_display_member_id', 'yes') == 'yes')
		{
			$arr_data['addressMemberID'] = __("Member ID", 'lang_address');
		}

		$arr_data['addressBirthDate'] = __("Social Security Number", 'lang_address');
		$arr_data['addressFirstName'] = __("First Name", 'lang_address');
		$arr_data['addressSurName'] = __("Last Name", 'lang_address');
		$arr_data['addressCo'] = __("C/O", 'lang_address');
		$arr_data['addressAddress'] = __("Address", 'lang_address');
		$arr_data['addressZipCode'] = __("Zip Code", 'lang_address');
		$arr_data['addressCity'] = __("City", 'lang_address');
		$arr_data['addressCountry'] = __("Country", 'lang_address');
		$arr_data['addressTelNo'] = __("Phone Number", 'lang_address');
		$arr_data['addressWorkNo'] = __("Work Number", 'lang_address');
		$arr_data['addressCellNo'] = __("Mobile Number", 'lang_address');
		$arr_data['addressEmail'] = __("E-mail", 'lang_address');

		$setting_address_extra_field = get_option('setting_address_extra_field');

		if(is_array($setting_address_extra_field) && count($setting_address_extra_field) > 0)
		{
			$arr_data['addressExtra'] = get_option_or_default('setting_address_extra', __("Extra", 'lang_address'));
		}

		return $arr_data;
	}

	function get_name($data)
	{
		global $wpdb;

		$out = __("unknown", 'lang_address');

		$result = $wpdb->get_results($wpdb->prepare("SELECT addressFirstName, addressSurName, addressEmail FROM ".$wpdb->prefix."address WHERE addressID = '%d'", $data['address_id']));

		if($wpdb->num_rows > 0)
		{
			foreach($result as $r)
			{
				$strAddressFirstName = $r->addressFirstName;
				$strAddressSurName = $r->addressSurName;
				$emlAddressEmail = $r->addressEmail;

				if($strAddressFirstName != '' || $strAddressSurName != '')
				{
					$out = $strAddressFirstName;

					if($strAddressSurName != '')
					{
						$out .= " ".$strAddressSurName;
					}
				}

				else
				{
					$out = $emlAddressEmail;
				}
			}
		}

		return $out;
	}

	function save_sync_date($data)
	{
		global $wpdb;

		$query_set = "";

		if(get_option('setting_address_api_url') != '')
		{
			$query_set .= ", addressSyncedDate = NOW()";
		}

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."address SET addressDeleted = '0', addressDeletedDate = NULL, addressDeletedID = NULL".$query_set." WHERE addressID = '%d'", $data['address_id']));

		return ($wpdb->rows_affected > 0);
	}

	function sync_api($data = [])
	{
		global $wpdb;

		if(!isset($data['limit_start'])){	$data['limit_start'] = 0;}
		if(!isset($data['limit_amount'])){	$data['limit_amount'] = 100;}

		$setting_address_api_url = get_option('setting_address_api_url');

		if($setting_address_api_url != '')
		{
			$option_address_api_full_used = get_option('option_address_api_full_used');

			if($option_address_api_full_used < date("Y-m-d H:i:s", strtotime("-1 week")))
			{
				$option_address_api_used = date("Y-m-d H:i:s", strtotime("-10 year"));

				$is_full_run = true;

				if($data['limit_start'] == 0)
				{
					$data['limit_start'] = get_option_or_default('option_address_api_full_next', 0);
				}
			}

			else
			{
				$option_address_api_used = get_option_or_default('option_address_api_used', date("Y-m-d H:i:s", strtotime("-1 year")));

				$is_full_run = false;

				if($data['limit_start'] == 0)
				{
					$data['limit_start'] = get_option_or_default('option_address_api_next', 0);
				}
			}

			$url = str_replace("[datetime]", urlencode($option_address_api_used), $setting_address_api_url);

			$url .= "&limit_start=".$data['limit_start']."&limit_amount=".$data['limit_amount'];

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
							// Insert or update
							############################
							if(isset($json['data']))
							{
								$count_incoming = count($json['data']);

								if($count_incoming > 0)
								{
									$count_updated = $count_updated_error = $count_inserted = $count_inserted_error = 0;

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

										$result = $wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".$wpdb->prefix."address WHERE addressBirthDate = %s AND addressDeleted = '0'", $strAddressBirthDate));
										$search_query = $wpdb->last_query;
										$rows = $wpdb->num_rows;

										if($rows > 0)
										{
											foreach($result as $r)
											{
												$intAddressID = $r->addressID;

												$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."address SET addressFirstName = %s, addressSurName = %s, addressZipCode = %s, addressCity = %s, addressCountry = '%d', addressAddress = %s, addressCo = %s, addressTelNo = %s, addressCellNo = %s, addressWorkNo = %s, addressEmail = %s, addressExtra = %s WHERE addressID = '%d'", $strAddressFirstName, $strAddressSurName, $intAddressZipCode, $strAddressCity, $intAddressCountry, $strAddressAddress, $strAddressCo, $strAddressTelNo, $strAddressCellNo, $strAddressWorkNo, $strAddressEmail, $strAddressExtra, $intAddressID));

												if($wpdb->rows_affected > 0)
												{
													$count_updated++;
												}

												else
												{
													$count_updated_error++;
												}
											}

											if($rows > 1)
											{
												do_log("<a href='".admin_url("admin.php?page=mf_address/list/index.php&s=".$strAddressBirthDate)."'>".sprintf("There were %d addresses with the same Social Security Number (%s) and they were all updated", $rows, $search_query)."</a>");
											}
										}

										else
										{
											$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."address SET addressBirthDate = %s, addressFirstName = %s, addressSurName = %s, addressZipCode = %s, addressCity = %s, addressCountry = '%d', addressAddress = %s, addressCo = %s, addressTelNo = %s, addressCellNo = %s, addressWorkNo = %s, addressEmail = %s, addressExtra = %s, addressCreated = NOW()", $strAddressBirthDate, $strAddressFirstName, $strAddressSurName, $intAddressZipCode, $strAddressCity, $intAddressCountry, $strAddressAddress, $strAddressCo, $strAddressTelNo, $strAddressCellNo, $strAddressWorkNo, $strAddressEmail, $strAddressExtra));

											$intAddressID = $wpdb->insert_id;

											if(!isset($obj_group))
											{
												$obj_group = new mf_group();
											}

											$obj_group->add_address(array('address_id' => $intAddressID, 'group_id' => get_option('setting_group_import')));

											if($intAddressID > 0)
											{
												$count_inserted++;
											}

											else
											{
												$count_inserted_error++;
											}
										}

										if($intAddressID > 0)
										{
											$this->save_sync_date(array('address_id' => $intAddressID));
										}
									}

									if(get_option('setting_address_debug') == 'yes')
									{
										do_log("Address API - Insert: ".$count_incoming." incoming, ".$count_updated." updated, ".$count_updated_error." NOT updated, ".$count_inserted." inserted, ".$count_inserted_error." NOT inserted");
									}

									if(isset($json['next']) && $json['next'] > 0)
									{
										if($is_full_run)
										{
											update_option('option_address_api_full_next', $json['next'], false);
										}

										else
										{
											update_option('option_address_api_next', $json['next'], false);
										}

										$this->sync_api(array('limit_start' => $json['next']));
									}

									else
									{
										if($is_full_run)
										{
											update_option('option_address_api_full_used', date("Y-m-d H:i:s"), false);
											update_option('option_address_api_full_next', 0, false);
										}

										else
										{
											update_option('option_address_api_used', date("Y-m-d H:i:s"), false);
											update_option('option_address_api_next', 0, false);
										}
									}
								}
							}
							############################

							// Remove exited
							############################
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
											$result = $wpdb->get_results($wpdb->prepare("SELECT addressID, addressFirstName, addressSurName FROM ".$wpdb->prefix."address WHERE addressBirthDate = %s AND addressDeleted = '0'", $strAddressBirthDate));

											if($wpdb->num_rows > 0)
											{
												foreach($result as $r)
												{
													$intAddressID = $r->addressID;
													$strAddressFirstName = $r->addressFirstName;
													$strAddressSurName = $r->addressSurName;

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

														if(get_option('setting_address_debug') == 'yes')
														{
															do_log("Address API - Trashed Address ".$intAddressID." (".$strAddressFirstName." ".$strAddressSurName.") in cron_base()");
														}
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
										do_log("Address API - Ended: ".$count_ended." ended, ".$count_removed." removed, ".$count_removed_error." NOT removed, ".$count_not_exit." NOT exit, ".$count_not_found." NOT found");
									}

									update_option('option_address_api_used', date("Y-m-d H:i:s"), false);
								}
							}
							############################

							// Remove old non-synced
							############################
							$result = $wpdb->get_results("SELECT addressID FROM ".$wpdb->prefix."address WHERE addressBirthDate != '' AND addressDeleted = '0' AND (addressSyncedDate IS null OR addressSyncedDate < DATE_SUB(NOW(), INTERVAL 1 MONTH))");

							$count_non_synced = $wpdb->num_rows;

							if($count_non_synced > 0)
							{
								$count_removed = $count_removed_error = 0;

								foreach($result as $r)
								{
									$intAddressID = $r->addressID;

									if($this->trash(array('address_id' => $intAddressID, 'force_admin' => true)))
									{
										$count_removed++;

										if(get_option('setting_address_debug') == 'yes')
										{
											//do_log("Address API - Trashed Address ".$intAddressID." (".$this->get_name(array('address_id' => $intAddressID)).") in cron_base()");
										}
									}

									else
									{
										$count_removed_error++;
									}
								}

								if(get_option('setting_address_debug') == 'yes')
								{
									do_log("Address API - Old: ".$count_non_synced." non-synced, ".$count_removed." removed, ".$count_removed_error." NOT removed");
								}
							}
							############################
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

	function cron_base()
	{
		global $wpdb, $obj_group;

		$obj_cron = new mf_cron();
		$obj_cron->start(__CLASS__);

		if($obj_cron->is_running == false)
		{
			mf_uninstall_plugin(array(
				'options' => array('setting_address_site_wide'),
			));

			delete_base(array(
				'table_prefix' => $wpdb->prefix,
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

			// Sync API
			#####################
			$this->sync_api();
			#####################

			// Look for duplicates
			#####################
			$result = $wpdb->get_results("SELECT addressID, addressPublic, addressMemberID, addressBirthDate, COUNT(addressBirthDate) AS addressAmount FROM ".$wpdb->prefix."address WHERE addressBirthDate != '' AND addressDeleted = '0' GROUP BY addressBirthDate ORDER BY addressAmount DESC LIMIT 0, 10");
			$rows = $wpdb->num_rows;

			if($rows > 0)
			{
				$merged_amount = $rows_amount = 0;

				foreach($result as $r)
				{
					$intAddressID = $r->addressID;
					$intAddressPublic = $r->addressPublic;
					$strAddressBirthDate = $r->addressBirthDate;
					$intAddressAmount = $r->addressAmount;

					if($intAddressAmount > 1)
					{
						$arr_item = array(
							'addressID' => $intAddressID,
							'addressBirthDate' => $strAddressBirthDate,
						);

						$arr_duplicates = $this->has_duplicate(array('item' => $arr_item));

						if(count($arr_duplicates) > 0)
						{
							$arr_address_ids = [];

							foreach($arr_duplicates as $key => $arr_value)
							{
								$arr_address_ids[] = $key;
							}

							if($intAddressPublic)
							{
								$arr_ids = array_merge($arr_address_ids, array($intAddressID));
							}

							else
							{
								$arr_ids = array_merge(array($intAddressID), $arr_address_ids);
							}

							if($this->do_merge(array('ids' => $arr_ids)))
							{
								$merged_amount++;
							}

							else
							{
								do_log("<a href='".admin_url("admin.php?page=mf_address/list/index.php&s=".$strAddressBirthDate)."'>".sprintf("There were %d addresses with the same Social Security Number (%s) but they could not be merged", $intAddressAmount, shorten_text(array('string' => $strAddressBirthDate, 'limit' => 10)))."</a>");
							}
						}

						$rows_amount++;
					}

					else
					{
						break;
					}
				}

				if($rows_amount > 0)
				{
					do_log("Merged ".$merged_amount." / ".$rows_amount." (".date("Y-m-d H:i:s").")", 'notification');
				}
			}
			#####################

			// Delete old uploads
			#######################
			list($upload_path, $upload_url) = get_uploads_folder($this->post_type, true, false);

			if($upload_path != '')
			{
				get_file_info(array('path' => $upload_path, 'callback' => 'delete_files_callback', 'time_limit' => WEEK_IN_SECONDS));
				get_file_info(array('path' => $upload_path, 'folder_callback' => 'delete_empty_folder_callback'));
			}
			#######################

			// Convert setting_address_extra_profile into setting_address_extra_field
			#######################
			if(get_option('setting_address_extra_field') == "")
			{
				$setting_address_extra_profile = get_option('setting_address_extra_profile', 'yes');

				if($setting_address_extra_profile == 'yes')
				{
					update_option('setting_address_extra_field', array('profile'), false);
				}
			}
			#######################
		}

		$obj_cron->end();
	}

	function init()
	{
		load_plugin_textdomain('lang_address', false, str_replace("/include", "", dirname(plugin_basename(__FILE__)))."/lang/");

		// Post types
		#######################
		register_post_type($this->post_type, array(
			'labels' => array(
				'name' => __("Address Book", 'lang_address'),
				'singular_name' => __("Address Book", 'lang_address'),
				'menu_name' => __("Address Book", 'lang_address'),
				'all_items' => __('List', 'lang_address'),
				'edit_item' => __('Edit', 'lang_address'),
				'view_item' => __('View', 'lang_address'),
				'add_new_item' => __('Add New', 'lang_address'),
			),
			'public' => false,
		));
		#######################
	}

	function settings_address()
	{
		$options_area = __FUNCTION__;

		add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = [];
		$arr_settings['setting_address_extra_field'] = __("Display Extra in", 'lang_address');

		$setting_address_extra_field = get_option('setting_address_extra_field');

		if(is_array($setting_address_extra_field) && count($setting_address_extra_field) > 0)
		{
			$arr_settings['setting_address_extra'] = __("Name for Extra Address Field", 'lang_address');
		}

		$arr_settings['setting_address_display_member_id'] = __("Display Member ID", 'lang_address');
		$arr_settings['setting_address_api_url'] = __("API URL", 'lang_address');

		if(get_option('setting_address_api_url') != '')
		{
			$arr_settings['setting_address_debug'] = __("Debug", 'lang_address');
		}

		show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
	}

	function settings_address_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Address Book", 'lang_address'));
	}

	function setting_address_extra_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		echo show_textfield(array('name' => $setting_key, 'value' => $option));
	}

	function setting_address_extra_field_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		$arr_data = array(
			'address' => __("Address", 'lang_address'),
			'profile' => __("Profile", 'lang_address'),
		);

		echo show_select(array('data' => $arr_data, 'name' => $setting_key."[]", 'value' => $option));
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

		$description = "";

		$option_address_api_full_used = get_option('option_address_api_full_used');
		$option_address_api_used = get_option('option_address_api_used');

		if($option_address_api_used > DEFAULT_DATE)
		{
			$description = sprintf(__("Only Updated %s (%d)", 'lang_address'), format_date($option_address_api_used), get_option('option_address_api_next'));

			if($option_address_api_full_used > DEFAULT_DATE)
			{
				$description .= ", ".sprintf(__("Full %s (%d)", 'lang_address'), format_date($option_address_api_full_used), get_option('option_address_api_full_next'));
			}
		}

		echo show_textfield(array('type' => 'url', 'name' => $setting_key, 'value' => $option, 'description' => $description));
	}

	function setting_address_debug_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option_or_default($setting_key, 'no');

		list($option, $description) = setting_time_limit(array('key' => $setting_key, 'value' => $option, 'return' => 'array'));

		echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option, 'description' => $description));
	}

	function admin_menu()
	{
		$menu_root = 'mf_address/';
		$menu_start = $menu_root."list/index.php";
		$menu_capability = 'edit_posts';

		$menu_title = __("Address Book", 'lang_address');
		add_menu_page("", $menu_title, $menu_capability, $menu_start, '', 'dashicons-email-alt', 99);

		$menu_title = __("List", 'lang_address');
		add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_start);

		$menu_title = __("Add New", 'lang_address');
		add_submenu_page($menu_start, $menu_title, " - ".$menu_title, $menu_capability, $menu_root."create/index.php");

		if(IS_EDITOR)
		{
			$menu_capability = 'edit_pages';

			$menu_title = __("Import", 'lang_address');
			add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_root."import/index.php");

			if(IS_SUPER_ADMIN)
			{
				$menu_title = __("Export", 'lang_address');
				add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, $menu_root."export/index.php");
			}

			$menu_title = __("Settings", 'lang_address');
			add_submenu_page($menu_start, $menu_title, $menu_title, $menu_capability, admin_url("options-general.php?page=settings_mf_base#settings_address"));
		}
	}

	function edit_user_profile($user)
	{
		global $wpdb;

		if(IS_ADMINISTRATOR && in_array('profile', get_option_or_default('setting_address_extra_field', [])))
		{
			$result = $wpdb->get_results("SELECT addressExtra FROM ".$wpdb->prefix."address WHERE addressExtra != '' GROUP BY addressExtra");

			if($wpdb->num_rows > 0)
			{
				$meta_address_permission = get_user_meta($user->ID, 'meta_address_permission', true);
				$meta_address_permission = explode(",", $meta_address_permission);

				$arr_data = [];

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
						<th><label for='meta_address_permission'>".__("Address Permissions to Users", 'lang_address').":</label></th>
						<td>".show_select(array('data' => $arr_data, 'name' => 'meta_address_permission[]', 'value' => $meta_address_permission))."</td>
					</tr>
				</table>";
			}
		}
	}

	function profile_update($user_id)
	{
		if(IS_ADMINISTRATOR && in_array('profile', get_option_or_default('setting_address_extra_field', [])))
		{
			$meta_address_permission = (isset($_POST['meta_address_permission']) ? $_POST['meta_address_permission'] : '');

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

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."address SET userID = '%d' WHERE userID = '%d'", get_current_user_id(), $user_id));
	}

	function remove_table_filter($data)
	{
		if(!isset($data['prefix'])){	$data['prefix'] = '';}

		$user_id = get_current_user_id();
		$meta_key = 'meta_table_filter_'.$data['prefix'].$data['key'];

		delete_user_meta($user_id, $meta_key);
	}

	function restrict_manage_posts($post_type)
	{
		global $wpdb, $obj_group;

		if($post_type == $wpdb->prefix."address" && is_plugin_active("mf_group/index.php"))
		{
			if(get_option('setting_address_api_url') != '')
			{
				$strFilterIsSynced = get_or_set_table_filter(array('key' => 'strFilterIsSynced', 'save' => true));

				$arr_data = get_yes_no_for_select(array('choose_here_text' => __("Synchronized Through API", 'lang_address')));

				$rows_synced = $wpdb->get_var($wpdb->prepare("SELECT COUNT(addressID) FROM ".$wpdb->prefix."address WHERE addressSyncedDate >= %s", DEFAULT_DATE));
				$rows_not_synced = $wpdb->get_var($wpdb->prepare("SELECT COUNT(addressID) FROM ".$wpdb->prefix."address WHERE addressSyncedDate < %s", DEFAULT_DATE));

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

			if(!isset($obj_group))
			{
				$obj_group = new mf_group();
			}

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

					if($obj_group->amount_in_group(array('id' => $intGroupID)) > 0)
					{
						echo show_select(array('data' => get_yes_no_for_select(array('choose_here_text' => __("Part of Group", 'lang_address'))), 'name' => 'strFilterIsMember', 'value' => $strFilterIsMember));
					}

					else
					{
						$this->remove_table_filter(array('key' => 'strFilterIsMember'));
					}

					if($strFilterIsMember != 'no' && $obj_group->amount_in_group(array('id' => $intGroupID, 'accepted' => 0)) > 0)
					{
						echo show_select(array('data' => get_yes_no_for_select(array('choose_here_text' => __("Accepted", 'lang_address'))), 'name' => 'strFilterAccepted', 'value' => $strFilterAccepted));
					}

					else
					{
						$this->remove_table_filter(array('key' => 'strFilterAccepted'));
					}

					if($strFilterIsMember != 'no' && $obj_group->amount_in_group(array('id' => $intGroupID, 'unsubscribed' => 1)) > 0)
					{
						echo show_select(array('data' => get_yes_no_for_select(array('choose_here_text' => __("Unsubscribed", 'lang_address'))), 'name' => 'strFilterUnsubscribed', 'value' => $strFilterUnsubscribed));
					}

					else
					{
						$this->remove_table_filter(array('key' => 'strFilterUnsubscribed'));
					}
				}

				else
				{
					$this->remove_table_filter(array('key' => 'intGroupID'));
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
		$group_label = __("Address Book", 'lang_address');

		$export_items = [];

		$result = $wpdb->get_results($wpdb->prepare("SELECT addressID, addressFirstName, addressSurName FROM ".$wpdb->prefix."address WHERE addressEmail = %s AND addressDeleted = '0' LIMIT ".(($page - 1) * $number).", ".$number, $email_address));

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
			'exporter_friendly_name' => __("Address Book", 'lang_address'),
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

		$result = $wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".$wpdb->prefix."address WHERE addressEmail = %s AND addressDeleted = '0'", $email_address)); // LIMIT ".(($page - 1) * $number).", ".$number

		foreach($result as $r)
		{
			//$this->trash(array('address_id' => $r->addressID));
			do_log("Trash Address ".$r->addressID." (".$wpdb->prepare("UPDATE ".$wpdb->prefix."address SET addressDeleted = '1', addressDeletedID = '%d', addressDeletedDate = NOW() WHERE addressID = '%d'".(IS_ADMINISTRATOR ? "" : " AND addressPublic = '0' AND userID = '".get_current_user_id()."'"), get_current_user_id(), $this->id).")");

			$items_removed = true;
		}

		return array(
			'items_removed' => $items_removed,
			'items_retained' => false, // always false in this example
			'messages' => [], // no messages in this example
			'done' => (count($result) < $number),
		);
	}

	function wp_privacy_personal_data_erasers($erasers)
	{
		$erasers[$this->meta_prefix] = array(
			'eraser_friendly_name' => __("Address Book", 'lang_address'),
			'callback' => array($this, 'erase_personal_data'),
		);

		return $erasers;
	}

	function filter_profile_fields($arr_fields)
	{
		global $wpdb;

		if(IS_ADMINISTRATOR && in_array('profile', get_option_or_default('setting_address_extra_field', [])))
		{
			$result = $wpdb->get_results("SELECT addressExtra FROM ".$wpdb->prefix."address WHERE addressExtra != '' GROUP BY addressExtra");

			if($wpdb->num_rows > 0)
			{
				$arr_data = [];

				foreach($result as $r)
				{
					$strTableValue = $r->addressExtra;

					if($strTableValue != '')
					{
						$arr_data[$strTableValue] = $strTableValue;
					}
				}

				$arr_fields[] = array('type' => 'select', 'options' => $arr_data, 'name' => 'meta_address_permission', 'multiple' => true, 'text' => __("Address Permissions to Users", 'lang_address'));
			}
		}

		return $arr_fields;
	}

	function has_duplicate($data)
	{
		global $wpdb;

		$arr_out = [];

		$intAddressID = $data['item']['addressID'];
		$intAddressMemberID = (isset($data['item']['addressMemberID']) ? $data['item']['addressMemberID'] : 0);
		$strAddressBirthDate = (isset($data['item']['addressBirthDate']) ? $data['item']['addressBirthDate'] : '');
		$strAddressEmail = (isset($data['item']['addressEmail']) ? $data['item']['addressEmail'] : '');

		$result = $wpdb->get_results($wpdb->prepare("SELECT addressID, addressMemberID, addressBirthDate, addressEmail FROM ".$wpdb->prefix."address WHERE ((addressMemberID > '0' AND addressMemberID = '%d') OR (addressBirthDate != '' AND addressBirthDate = %s) OR (addressEmail != '' AND addressEmail = %s)) AND addressDeleted = '0' AND addressID != '%d'", $intAddressMemberID, $strAddressBirthDate, $strAddressEmail, $intAddressID));

		foreach($result as $r)
		{
			$arr_out[$r->addressID] = array(
				'member_id' => ($intAddressMemberID > 0 && $intAddressMemberID == $r->addressMemberID),
				'birthdate' => ($strAddressBirthDate != '' && $strAddressBirthDate == $r->addressBirthDate),
				'email' => ($strAddressEmail != '' && $strAddressEmail == $r->addressEmail),
			);
		}

		return $arr_out;
	}

	function do_merge($data)
	{
		global $wpdb, $error_text, $done_text;

		$has_merged = false;
		$merged_amount = 0;

		if(count($data['ids']) > 1)
		{
			$id_prev = 0;

			foreach($data['ids'] as $id)
			{
				if($id_prev > 0)
				{
					$base_query = "SELECT addressID, addressPublic, ".implode(", ", $this->arr_unique_columns).", ".implode(", ", $this->arr_columns)." FROM ".$wpdb->prefix."address WHERE addressID = '%d'";

					$result_prev = $wpdb->get_results($wpdb->prepare($base_query, $id_prev), ARRAY_A);
					$result = $wpdb->get_results($wpdb->prepare($base_query, $id), ARRAY_A);

					if($wpdb->num_rows > 0 && $result[0]['addressPublic'] == 1)
					{
						$unique_column = '';

						foreach($this->arr_unique_columns as $str_unique_column)
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

							foreach($this->arr_columns as $str_column)
							{
								if($result_prev[0][$str_column] != '' && strtolower($result_prev[0][$str_column]) != strtolower($result[0][$str_column]))
								{
									$query_set .= ($query_set != '' ? ", " : "").$str_column." = '".esc_sql($result_prev[0][$str_column])."'";
								}
							}

							if($query_set != '')
							{
								$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."address SET ".$query_set." WHERE addressID = '%d'", $id));

								do_log(get_user_info(array('id' => get_current_user_id()))." Merged/Updated ".$id_prev." -> ".$id." (".$wpdb->last_query.")", 'notification');
							}

							do_action('merge_address', $id_prev, $id);

							if($this->trash(array('address_id' => $id_prev, 'force_admin' => true)))
							{
								//do_log("Trashed Address ".$id_prev." (".$this->get_name(array('address_id' => $id_prev)).") in do_merge()");

								$merged_amount++;
							}
						}

						else
						{
							$error_text = __("I could not merge the addresses for you because no unique column matched", 'lang_address');

							/*if(IS_SUPER_ADMIN)
							{
								$error_text .= " (".var_export($result_prev[0], true)." -> ".var_export($result[0], true).")";
							}*/

							break;
						}
					}

					else
					{
						$error_text = __("I could not merge the addresses for you because only public addresses are possible to merge", 'lang_address');

						break;
					}
				}

				$id_prev = $id;
			}

			if($merged_amount > 0)
			{
				$done_text = __("The addresses have been merged successfully", 'lang_address');

				$has_merged = true;
			}

			else if($error_text == '')
			{
				$error_text = __("I did not get any errors but no addresses were merged", 'lang_address');
			}
		}

		else
		{
			$error_text = __("You have to choose at least two addresses to merge", 'lang_address');
		}

		return $has_merged;
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

				if(IS_ADMINISTRATOR)
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
			global $obj_group;

			if(!isset($obj_group))
			{
				$obj_group = new mf_group();
			}
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
							$error_text = __("The e-mail address does not seam to be valid because the response is that the domain does not have e-mails connected to it", 'lang_address');
						}

						else
						{
							$query_set = "";

							if(IS_ADMINISTRATOR)
							{
								$query_set .= ", addressExtra = '".esc_sql($this->extra)."'";
							}

							if($this->id > 0)
							{
								$query_where = "";

								if(IS_ADMINISTRATOR)
								{
									$query_set .= ", addressPublic = '".esc_sql($this->public)."'";
								}

								else
								{
									$query_where .= " AND (addressPublic = '1' OR addressPublic = '0' AND userID = '".get_current_user_id()."')";
								}

								$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."address SET addressMemberID = '%d', addressBirthDate = %s, addressFirstName = %s, addressSurName = %s, addressZipCode = %s, addressCity = %s, addressCountry = '%d', addressAddress = %s, addressCo = %s, addressTelNo = %s, addressCellNo = %s, addressWorkNo = %s, addressEmail = %s".$query_set." WHERE addressID = '%d'".$query_where, $this->member_id, $this->birthdate, $this->first_name, $this->sur_name, $this->zipcode, $this->city, $this->country, $this->address, $this->co, $this->telno, $this->cellno, $this->workno, $this->email, $this->id));

								if($wpdb->rows_affected > 0)
								{
									$type = 'updated';
								}

								else
								{
									$error_text = __("I could not update the address for you. Either you do not have the permission to update this address or you did not change any of the fields before saving", 'lang_address');
								}
							}

							else
							{
								$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."address SET addressPublic = '%d', addressMemberID = '%d', addressBirthDate = %s, addressFirstName = %s, addressSurName = %s, addressZipCode = %s, addressCity = %s, addressCountry = '%d', addressAddress = %s, addressCo = %s, addressTelNo = %s, addressCellNo = %s, addressWorkNo = %s, addressEmail = %s, addressCreated = NOW(), userID = '%d'".$query_set, $this->public, $this->member_id, $this->birthdate, $this->first_name, $this->sur_name, $this->zipcode, $this->city, $this->country, $this->address, $this->co, $this->telno, $this->cellno, $this->workno, $this->email, get_current_user_id()));

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
								$error_text = __("The information was not submitted, contact an admin if this persists", 'lang_address');

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
						$done_text = __("The address was deleted", 'lang_address');

						//do_log("Trashed Address ".$this->id." (".$this->get_name(array('address_id' => $this->id)).") in btnAddressDelete");
					}

					else
					{
						$error_text = __("I could not delete the address", 'lang_address');
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

					$done_text = __("I recovered the address for you", 'lang_address');
				}

				else if(isset($_GET['btnAddressAdd']))
				{
					if($this->group_id > 0 && $this->id > 0 && wp_verify_nonce($_REQUEST['_wpnonce_address_add'], 'address_add_'.$this->id.'_'.$this->group_id) && is_plugin_active("mf_group/index.php"))
					{
						if($obj_group->has_address(array('address_id' => $this->id, 'group_id' => $this->group_id)) == false)
						{
							$obj_group->add_address(array('address_id' => $this->id, 'group_id' => $this->group_id));

							$done_text = __("The address was added to the group", 'lang_address');
						}

						else
						{
							$error_text = __("The address already exists in the group", 'lang_address');
						}
					}

					else
					{
						$error_text = __("I could not verify that your action was allowed", 'lang_address');

						if(IS_SUPER_ADMIN)
						{
							$error_text .= " (G: ".$this->group_id.", A: ".$this->id.")";
						}
					}
				}

				else if(isset($_GET['btnAddressRemove']) && $this->group_id > 0 && $this->id > 0 && wp_verify_nonce($_REQUEST['_wpnonce_address_remove'], 'address_remove_'.$this->id.'_'.$this->group_id) && is_plugin_active("mf_group/index.php"))
				{
					if($obj_group->has_address(array('address_id' => $this->id, 'group_id' => $this->group_id)) == true)
					{
						$obj_group->remove_address(array('address_id' => $this->id, 'group_id' => $this->group_id));

						$done_text = __("The address was removed from the group", 'lang_address');
					}

					else
					{
						$error_text = __("The address could not be removed since it did not exist in the group", 'lang_address');
					}
				}

				else if(isset($_GET['btnAddressAccept']) && $this->group_id > 0 && $this->id > 0 && wp_verify_nonce($_REQUEST['_wpnonce_address_accept'], 'address_accept_'.$this->id.'_'.$this->group_id) && is_plugin_active("mf_group/index.php"))
				{
					if($obj_group->accept_address(array('address_id' => $this->id, 'group_id' => $this->group_id)))
					{
						$done_text = __("The address has been manually accepted", 'lang_address');
					}

					else
					{
						$error_text = __("I could not manually accept the address for you", 'lang_address');
					}
				}

				else if(isset($_GET['btnAddressResend']) && $this->group_id > 0 && $this->id > 0 && wp_verify_nonce($_REQUEST['_wpnonce_address_resend'], 'address_resend_'.$this->id.'_'.$this->group_id) && is_plugin_active("mf_group/index.php"))
				{
					if($obj_group->send_acceptance_message(array('type' => 'reminder', 'address_id' => $this->id, 'group_id' => $this->group_id)))
					{
						$done_text = __("The message was sent", 'lang_address');
					}

					else
					{
						$error_text = __("I could not send the message for you", 'lang_address');
					}
				}

				else if(isset($_GET['created']))
				{
					$done_text = __("The address was created", 'lang_address');
				}

				else if(isset($_GET['updated']))
				{
					$done_text = __("The address was updated", 'lang_address');
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
					$result = $wpdb->get_results($wpdb->prepare("SELECT addressPublic, addressMemberID, addressBirthDate, addressFirstName, addressSurName, addressAddress, addressCo, addressZipCode, addressCity, addressCountry, addressTelNo, addressCellNo, addressWorkNo, addressEmail, addressExtra, addressDeleted FROM ".$wpdb->prefix."address WHERE addressID = '%d'", $this->id));

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

						if(IS_ADMINISTRATOR)
						{
							$this->extra = $r->addressExtra;
						}

						$intAddressDeleted = $r->addressDeleted;

						if($intAddressDeleted == 1)
						{
							$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."address SET addressDeleted = '0', addressDeletedID = '', addressDeletedDate = '' WHERE addressPublic = '0' AND addressID = '%d' AND userID = '%d'", $this->id, get_current_user_id()));
						}
					}
				}
			break;
		}
	}

	function get_countries_for_select($data = [])
	{
		if(!isset($data['add_choose_here'])){	$data['add_choose_here'] = true;}
		if(!isset($data['choose_here_text'])){	$data['choose_here_text'] = __("Choose Here", 'lang_address');}
		if(!isset($data['exclude'])){			$data['exclude'] = [];}

		$arr_data = [];

		if($data['add_choose_here'] == true)
		{
			$arr_data[''] = "-- ".$data['choose_here_text']." --";
		}

		$arr_countries = array(
			1 	=> __("Afghanistan", 'lang_address'),
			2 	=> __("Albania", 'lang_address'),
			3 	=> __("Algeria", 'lang_address'),
			4	=> __("American Samoa", 'lang_address'),
			5	=> __("Andorra", 'lang_address'),
			6	=> __("Angola", 'lang_address'),
			7	=> __("Anguilla", 'lang_address'),
			8	=> __("Antarctica", 'lang_address'),
			9	=> __("Antigua and Barbuda", 'lang_address'),
			10	=> __("Argentina", 'lang_address'),
			11	=> __("Armenia", 'lang_address'),
			12	=> __("Aruba", 'lang_address'),
			13	=> __("Australia", 'lang_address'),
			14	=> __("Austria", 'lang_address'),
			15	=> __("Azerbaijan", 'lang_address'),
			16	=> __("Bahamas", 'lang_address'),
			17	=> __("Bahrain", 'lang_address'),
			18	=> __("Bangladesh", 'lang_address'),
			19	=> __("Barbados", 'lang_address'),
			20	=> __("Belarus", 'lang_address'),
			21	=> __("Belgium", 'lang_address'),
			22	=> __("Belize", 'lang_address'),
			23	=> __("Benin", 'lang_address'),
			24	=> __("Bermuda", 'lang_address'),
			25	=> __("Bhutan", 'lang_address'),
			26	=> __("Bolivia", 'lang_address'),
			27	=> __("Bosnia and Herzegowina", 'lang_address'),
			28	=> __("Botswana", 'lang_address'),
			29	=> __("Bouvet Island", 'lang_address'),
			30	=> __("Brazil", 'lang_address'),
			31	=> __("British Indian Ocean Territory", 'lang_address'),
			32	=> __("Brunei Darussalam", 'lang_address'),
			33	=> __("Bulgaria", 'lang_address'),
			34	=> __("Burkina Faso", 'lang_address'),
			35	=> __("Burundi", 'lang_address'),
			36	=> __("Cambodia", 'lang_address'),
			37	=> __("Cameroon", 'lang_address'),
			38	=> __("Canada", 'lang_address'),
			39	=> __("Cape Verde", 'lang_address'),
			40	=> __("Cayman Islands", 'lang_address'),
			41	=> __("Central African Republic", 'lang_address'),
			42	=> __("Chad", 'lang_address'),
			43	=> __("Chile", 'lang_address'),
			44	=> __("China", 'lang_address'),
			45	=> __("Christmas Island", 'lang_address'),
			46	=> __("Cocos (Keeling) Islands", 'lang_address'),
			47	=> __("Colombia", 'lang_address'),
			48	=> __("Comoros", 'lang_address'),
			49	=> __("Congo", 'lang_address'),
			50	=> __("Cook Islands", 'lang_address'),
			51	=> __("Costa Rica", 'lang_address'),
			52	=> __("Cote D'Ivoire", 'lang_address'),
			53	=> __("Croatia", 'lang_address'),
			54	=> __("Cuba", 'lang_address'),
			55	=> __("Cyprus", 'lang_address'),
			56	=> __("Czech Republic", 'lang_address'),
			57	=> __("Denmark", 'lang_address'),
			58	=> __("Djibouti", 'lang_address'),
			59	=> __("Dominica", 'lang_address'),
			60	=> __("Dominican Republic", 'lang_address'),
			61	=> __("East Timor", 'lang_address'),
			62	=> __("Ecuador", 'lang_address'),
			63	=> __("Egypt", 'lang_address'),
			64	=> __("El Salvador", 'lang_address'),
			65	=> __("Equatorial Guinea", 'lang_address'),
			66	=> __("Eritrea", 'lang_address'),
			67	=> __("Estonia", 'lang_address'),
			68	=> __("Ethiopia", 'lang_address'),
			69	=> __("Falkland Islands (Malvinas)", 'lang_address'),
			70	=> __("Faroe Islands", 'lang_address'),
			71	=> __("Fiji", 'lang_address'),
			72	=> __("Finland", 'lang_address'),
			73	=> __("France", 'lang_address'),
			74	=> __("France, Metropolitan", 'lang_address'),
			75	=> __("French Guiana", 'lang_address'),
			76	=> __("French Polynesia", 'lang_address'),
			77	=> __("French Southern Territories", 'lang_address'),
			78	=> __("Gabon", 'lang_address'),
			79	=> __("Gambia", 'lang_address'),
			80	=> __("Georgia", 'lang_address'),
			81	=> __("Germany", 'lang_address'),
			82	=> __("Ghana", 'lang_address'),
			83	=> __("Gibraltar", 'lang_address'),
			84	=> __("Greece", 'lang_address'),
			85	=> __("Greenland", 'lang_address'),
			86	=> __("Grenada", 'lang_address'),
			87	=> __("Guadeloupe", 'lang_address'),
			88	=> __("Guam", 'lang_address'),
			89	=> __("Guatemala", 'lang_address'),
			90	=> __("Guinea", 'lang_address'),
			91	=> __("Guinea-bissau", 'lang_address'),
			92	=> __("Guyana", 'lang_address'),
			93	=> __("Haiti", 'lang_address'),
			94	=> __("Heard and Mc Donald Islands", 'lang_address'),
			95	=> __("Honduras", 'lang_address'),
			96	=> __("Hong Kong", 'lang_address'),
			97	=> __("Hungary", 'lang_address'),
			98	=> __("Iceland", 'lang_address'),
			99	=> __("India", 'lang_address'),
			100	=> __("Indonesia", 'lang_address'),
			101	=> __("Iran (Islamic Republic of)", 'lang_address'),
			102	=> __("Iraq", 'lang_address'),
			103	=> __("Ireland", 'lang_address'),
			104	=> __("Israel", 'lang_address'),
			105	=> __("Italy", 'lang_address'),
			106	=> __("Jamaica", 'lang_address'),
			107	=> __("Japan", 'lang_address'),
			108	=> __("Jordan", 'lang_address'),
			109	=> __("Kazakhstan", 'lang_address'),
			110	=> __("Kenya", 'lang_address'),
			111	=> __("Kiribati", 'lang_address'),
			112	=> __("Korea, Democratic Peoples Republic of", 'lang_address'),
			113	=> __("Korea, Republic of", 'lang_address'),
			114	=> __("Kuwait", 'lang_address'),
			115	=> __("Kyrgyzstan", 'lang_address'),
			116	=> __("Lao Peoples Democratic Republic", 'lang_address'),
			117	=> __("Latvia", 'lang_address'),
			118	=> __("Lebanon", 'lang_address'),
			119	=> __("Lesotho", 'lang_address'),
			120	=> __("Liberia", 'lang_address'),
			121	=> __("Libyan Arab Jamahiriya", 'lang_address'),
			122	=> __("Liechtenstein", 'lang_address'),
			123	=> __("Lithuania", 'lang_address'),
			124	=> __("Luxembourg", 'lang_address'),
			125	=> __("Macau", 'lang_address'),
			126	=> __("Macedonia, The Former Yugoslav Republic of", 'lang_address'),
			127	=> __("Madagascar", 'lang_address'),
			128	=> __("Malawi", 'lang_address'),
			129	=> __("Malaysia", 'lang_address'),
			130	=> __("Maldives", 'lang_address'),
			131	=> __("Mali", 'lang_address'),
			132	=> __("Malta", 'lang_address'),
			133	=> __("Marshall Islands", 'lang_address'),
			134	=> __("Martinique", 'lang_address'),
			135	=> __("Mauritania", 'lang_address'),
			136	=> __("Mauritius", 'lang_address'),
			137	=> __("Mayotte", 'lang_address'),
			138	=> __("Mexico", 'lang_address'),
			139	=> __("Micronesia, Federated States of", 'lang_address'),
			140	=> __("Moldova, Republic of", 'lang_address'),
			141	=> __("Monaco", 'lang_address'),
			142	=> __("Mongolia", 'lang_address'),
			143	=> __("Montserrat", 'lang_address'),
			144	=> __("Morocco", 'lang_address'),
			145	=> __("Mozambique", 'lang_address'),
			146	=> __("Myanmar", 'lang_address'),
			147	=> __("Namibia", 'lang_address'),
			148	=> __("Nauru", 'lang_address'),
			149	=> __("Nepal", 'lang_address'),
			150	=> __("Netherlands", 'lang_address'),
			151	=> __("Netherlands Antilles", 'lang_address'),
			152	=> __("New Caledonia", 'lang_address'),
			153	=> __("New Zealand", 'lang_address'),
			154	=> __("Nicaragua", 'lang_address'),
			155	=> __("Niger", 'lang_address'),
			156	=> __("Nigeria", 'lang_address'),
			157	=> __("Niue", 'lang_address'),
			158	=> __("Norfolk Island", 'lang_address'),
			159	=> __("Northern Mariana Islands", 'lang_address'),
			160	=> __("Norway", 'lang_address'),
			161	=> __("Oman", 'lang_address'),
			162	=> __("Pakistan", 'lang_address'),
			163	=> __("Palau", 'lang_address'),
			164	=> __("Panama", 'lang_address'),
			165	=> __("Papua New Guinea", 'lang_address'),
			166	=> __("Paraguay", 'lang_address'),
			167	=> __("Peru", 'lang_address'),
			168	=> __("Philippines", 'lang_address'),
			169	=> __("Pitcairn", 'lang_address'),
			170	=> __("Poland", 'lang_address'),
			171	=> __("Portugal", 'lang_address'),
			172	=> __("Puerto Rico", 'lang_address'),
			173	=> __("Qatar", 'lang_address'),
			174	=> __("Reunion", 'lang_address'),
			175	=> __("Romania", 'lang_address'),
			176	=> __("Russian Federation", 'lang_address'),
			177	=> __("Rwanda", 'lang_address'),
			178	=> __("Saint Kitts and Nevis", 'lang_address'),
			179	=> __("Saint Lucia", 'lang_address'),
			180	=> __("Saint Vincent and the Grenadines", 'lang_address'),
			181	=> __("Samoa", 'lang_address'),
			182	=> __("San Marino", 'lang_address'),
			183	=> __("Sao Tome and Principe", 'lang_address'),
			184	=> __("Saudi Arabia", 'lang_address'),
			185	=> __("Senegal", 'lang_address'),
			186	=> __("Seychelles", 'lang_address'),
			187	=> __("Sierra Leone", 'lang_address'),
			188	=> __("Singapore", 'lang_address'),
			189	=> __("Slovakia (Slovak Republic)", 'lang_address'),
			190	=> __("Slovenia", 'lang_address'),
			191	=> __("Solomon Islands", 'lang_address'),
			192	=> __("Somalia", 'lang_address'),
			193	=> __("South Africa", 'lang_address'),
			194	=> __("South Georgia and the South Sandwich Islands", 'lang_address'),
			195	=> __("Spain", 'lang_address'),
			196	=> __("Sri Lanka", 'lang_address'),
			197	=> __("St. Helena", 'lang_address'),
			198	=> __("St. Pierre and Miquelon", 'lang_address'),
			199	=> __("Sudan", 'lang_address'),
			200	=> __("Suriname", 'lang_address'),
			201	=> __("Svalbard", 'lang_address'),
			202	=> __("Swaziland", 'lang_address'),
			203	=> __("Sweden", 'lang_address'),
			204	=> __("Switzerland", 'lang_address'),
			205	=> __("Syrian Arab Republic", 'lang_address'),
			206	=> __("Taiwan", 'lang_address'),
			207	=> __("Tajikistan", 'lang_address'),
			208	=> __("Tanzania, United Republic of", 'lang_address'),
			209	=> __("Thailand", 'lang_address'),
			210	=> __("Togo", 'lang_address'),
			211	=> __("Tokelau", 'lang_address'),
			212	=> __("Tonga", 'lang_address'),
			213	=> __("Trinidad and Tobago", 'lang_address'),
			214	=> __("Tunisia", 'lang_address'),
			215	=> __("Turkey", 'lang_address'),
			216	=> __("Turkmenistan", 'lang_address'),
			217	=> __("Turks and Caicos Islands", 'lang_address'),
			218	=> __("Tuvalu", 'lang_address'),
			219	=> __("Uganda", 'lang_address'),
			220	=> __("Ukraine", 'lang_address'),
			221	=> __("United Arab Emirates", 'lang_address'),
			222	=> __("United Kingdom", 'lang_address'),
			223	=> __("United States", 'lang_address'),
			224	=> __("United States Minor Outlying Islands", 'lang_address'),
			225	=> __("Uruguay", 'lang_address'),
			226	=> __("Uzbekistan", 'lang_address'),
			227	=> __("Vanuatu", 'lang_address'),
			228	=> __("Vatican City State (Holy See)", 'lang_address'),
			229	=> __("Venezuela", 'lang_address'),
			230	=> __("Vietnam", 'lang_address'),
			231	=> __("Virgin Islands (British)", 'lang_address'),
			232	=> __("Virgin Islands (U.S.)", 'lang_address'),
			233	=> __("Wallis and Futuna Islands", 'lang_address'),
			234	=> __("Western Sahara", 'lang_address'),
			235	=> __("Yemen", 'lang_address'),
			236	=> __("Yugoslavia", 'lang_address'),
			237	=> __("Zaire", 'lang_address'),
			238	=> __("Zambia", 'lang_address'),
			239	=> __("Zimbabwe", 'lang_address'),
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

		switch($data['type'])
		{
			case 'sms':
				$result = $wpdb->get_results("SELECT addressCellNo FROM ".$wpdb->prefix."address WHERE addressCellNo != '' AND (addressFirstName LIKE '%".$data['string']."%' OR addressSurName LIKE '%".$data['string']."%' OR CONCAT(addressFirstName, ' ', addressSurName) LIKE '%".$data['string']."%' OR REPLACE(REPLACE(REPLACE(addressCellNo, '/', ''), '-', ''), ' ', '') LIKE '%".$data['string']."%') GROUP BY addressCellNo ORDER BY addressSurName ASC, addressFirstName ASC");
			break;

			default:
				$result = [];
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

		return $wpdb->get_var($wpdb->prepare("SELECT addressEmail FROM ".$wpdb->prefix."address WHERE addressID = '%d'", $this->id));
	}

	function get_address_id($data)
	{
		global $wpdb;

		$this->id = $wpdb->get_var($wpdb->prepare("SELECT addressID FROM ".$wpdb->prefix."address WHERE addressEmail = %s", $data['email']));
	}

	function insert($data)
	{
		global $wpdb;

		if(!isset($data['public'])){	$data['public'] = false;}

		if($data['email'] != '')
		{
			$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."address SET addressPublic = '%d', addressEmail = %s, addressCreated = NOW(), userID = '%d'", $data['public'], $data['email'], get_current_user_id()));

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

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."address SET addressDeleted = '0', addressDeletedID = '%d', addressDeletedDate = NOW() WHERE addressID = '%d'", get_current_user_id(), $this->id));

		return ($wpdb->rows_affected > 0);
	}

	function trash($data = [])
	{
		global $wpdb;

		if(!isset($data['address_id'])){	$data['address_id'] = 0;}
		if(!isset($data['force_admin'])){	$data['force_admin'] = false;}

		if($data['address_id'] > 0)
		{
			$this->id = $data['address_id'];
		}

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."address SET addressDeleted = '1', addressDeletedID = '%d', addressDeletedDate = NOW() WHERE addressID = '%d'".(IS_ADMINISTRATOR || $data['force_admin'] ? "" : " AND addressPublic = '0' AND userID = '".get_current_user_id()."'"), get_current_user_id(), $this->id));

		return ($wpdb->rows_affected > 0);
	}

	function delete($id = 0)
	{
		global $wpdb;

		if($id > 0)
		{
			$this->id = $id;
		}

		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."address WHERE addressID = '%d'", $this->id));

		return ($rows_affected > 0);
	}

	function update_errors($data = [])
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
			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."address SET addressError = 0 WHERE addressID = '%d'", $this->id));
		}
	}
}

if(!class_exists('mf_list_table'))
{
	require_once(ABSPATH."wp-content/plugins/mf_base/include/classes.php");
}

class mf_address_table extends mf_list_table
{
	var $query_where = "";

	function set_default()
	{
		global $wpdb, $obj_address;

		if(!isset($obj_address))
		{
			$obj_address = new mf_address();
		}

		$this->arr_settings['query_from'] = $wpdb->prefix."address";
		$this->post_type = '';

		$this->arr_settings['query_select_id'] = $wpdb->prefix."address.addressID";
		$this->arr_settings['query_all_id'] = "0";
		$this->arr_settings['query_trash_id'] = "1";
		$this->orderby_default = "addressSurName";

		//$this->arr_settings['has_autocomplete'] = true;
	}

	function init_fetch()
	{
		global $wpdb;

		if(!IS_ADMINISTRATOR)
		{
			$this->query_where .= ($this->query_where != '' ? " AND " : "")."(addressPublic = '1' OR addressPublic = '0' AND userID = '".get_current_user_id()."')";
		}

		if($this->search != '')
		{
			@list($first_name, $sur_name) = explode(" ", $this->search);

			$this->query_where .= ($this->query_where != '' ? " AND " : "")."("
				."addressBirthDate LIKE '".$this->filter_search_before_like($this->search)."'"
				." OR CONCAT(addressFirstName, ' ', addressSurName) LIKE '".$this->filter_search_before_like($this->search)."'"
				." OR addressFirstName LIKE '%".$first_name."%' AND addressSurName LIKE '%".$sur_name."%'"
				." OR addressAddress LIKE '".$this->filter_search_before_like($this->search)."'"
				." OR addressZipCode LIKE '".$this->filter_search_before_like($this->search)."'"
				." OR addressCity LIKE '".$this->filter_search_before_like($this->search)."'"
				." OR addressTelNo LIKE '".$this->filter_search_before_like($this->search)."'"
				." OR addressWorkNo LIKE '".$this->filter_search_before_like($this->search)."'"
				." OR addressCellNo LIKE '".$this->filter_search_before_like($this->search)."'"
				." OR addressEmail LIKE '".$this->filter_search_before_like($this->search)."'"
				." OR SOUNDEX(CONCAT(addressFirstName, ' ', addressSurName)) = SOUNDEX('".$this->search."')"
				." OR SOUNDEX(addressFirstName) = SOUNDEX('".$first_name."') AND SOUNDEX(addressSurName) = SOUNDEX('".$sur_name."')"
				." OR SOUNDEX(addressAddress) = SOUNDEX('".$this->search."')"
				." OR SOUNDEX(addressCity) = SOUNDEX('".$this->search."')"
			.")";
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
				$this->query_join .= " LEFT JOIN ".$wpdb->prefix."address2group ON ".$wpdb->prefix."address.addressID = ".$wpdb->prefix."address2group.addressID AND groupID = '".$intGroupID."'";
			}

			switch($strFilterIsMember)
			{
				case 'yes':
					$this->query_where .= ($this->query_where != '' ? " AND " : "")."groupID = '".$intGroupID."'";
				break;

				case 'no':
					$this->query_where .= ($this->query_where != '' ? " AND " : "")."groupID IS null";
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
				'0' => __("All", 'lang_address'),
				'1' => __("Trash", 'lang_address'),
			),
		));

		$arr_columns = $arr_sortable_columns = [];
		$arr_columns['cb'] = '<input type="checkbox">';
		$arr_columns['addressSurName'] = __("Name", 'lang_address');
		$arr_columns['addressAddress'] = __("Address", 'lang_address');
		$arr_columns['addressIcons'] = __("Information", 'lang_address');

		if($intGroupID > 0)
		{
			$arr_columns['is_part_of_group'] = "<span class='nowrap'><i class='fa fa-plus-square'></i> / <i class='fa fa-minus-square'></i></span>";
		}

		$arr_columns['addressError'] = "";
		$arr_columns['addressContact'] = __("Contact", 'lang_address');

		$arr_sortable_columns[] = 'addressSurName';

		$setting_address_extra_field = get_option('setting_address_extra_field');

		if(is_array($setting_address_extra_field) && count($setting_address_extra_field) > 0)
		{
			$arr_columns['addressExtra'] = get_option_or_default('setting_address_extra', __("Extra", 'lang_address'));
			$arr_sortable_columns[] = 'addressExtra';
		}

		$this->set_columns($arr_columns);

		$this->set_sortable_columns($arr_sortable_columns);
	}

	function column_cb($item)
	{
		return "<input type='checkbox' name='".$this->arr_settings['query_from']."[]' value='".$item['addressID']."'>";
	}

	function get_bulk_actions()
	{
		$arr_actions = [];

		if(isset($this->columns['cb']))
		{
			if(IS_ADMINISTRATOR)
			{
				$intGroupID = get_or_set_table_filter(array('key' => 'intGroupID'));

				if($intGroupID > 0)
				{
					$strFilterIsMember = get_or_set_table_filter(array('key' => 'strFilterIsMember'));

					if($strFilterIsMember == 'no')
					{
						$arr_actions['add2group'] = __("Add to Group", 'lang_address');
					}
				}

				$arr_actions['merge'] = __("Merge", 'lang_address');
			}

			if(!isset($_GET['addressDeleted']) || $_GET['addressDeleted'] != 1)
			{
				$arr_actions['trash'] = __("Delete", 'lang_address');
			}

			else
			{
				$arr_actions['restore'] = __("Restore", 'lang_address');
				$arr_actions['delete'] = __("Permanently Delete", 'lang_address');
			}
		}

		return $arr_actions;
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

				case 'add2group':
					$this->bulk_add2group();
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

				//do_log("Trashed Address ".$id." (".$obj_address->get_name(array('address_id' => $id)).") in bulk_trash()");
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

	function bulk_add2group()
	{
		global $obj_group, $error_text, $done_text;

		$arr_ids = check_var($this->arr_settings['query_from'], 'array');

		if(!isset($obj_group))
		{
			$obj_group = new mf_group();
		}

		$intGroupID = get_or_set_table_filter(array('key' => 'intGroupID'));

		foreach($arr_ids as $id)
		{
			$obj_group->add_address(array('address_id' => $id, 'group_id' => $intGroupID));
		}

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
					$strAddressName = "(".__("unknown", 'lang_address').")";
				}

				$post_edit_url = admin_url("admin.php?page=mf_address/create/index.php&intAddressID=".$intAddressID);
				$list_url = admin_url("admin.php?page=mf_address/list/index.php&intAddressID=".$intAddressID);

				$arr_actions = [];

				if($intAddressDeleted == 0)
				{
					if($intAddressPublic == 0 || IS_ADMINISTRATOR)
					{
						$arr_actions['edit'] = "<a href='".$post_edit_url."'>".__("Edit", 'lang_address')."</a>";

						$arr_actions['delete'] = "<a href='".wp_nonce_url($list_url."&btnAddressDelete", 'address_delete_'.$intAddressID, '_wpnonce_address_delete')."'>".__("Delete", 'lang_address')."</a>";
					}
				}

				else
				{
					$arr_actions['recover'] = "<a href='".wp_nonce_url($list_url."&btnAddressRestore", 'address_restore_'.$intAddressID, '_wpnonce_address_restore')."' title='".sprintf(__("Removed %s by %s", 'lang_address'), format_date($dteAddressDeletedDate), get_user_info(array('id' => $intAddressDeletedID)))."'>".__("Restore", 'lang_address')."</a>";
				}

				if($intAddressMemberID > 0)
				{
					$arr_actions['member_id'] = $intAddressMemberID;
				}

				if($strAddressBirthDate != '')
				{
					$arr_actions['birth_date'] = $strAddressBirthDate;
				}

				$out .= "<a href='".$post_edit_url."'>"
					.$strAddressName
				."</a>"
				.$this->row_actions($arr_actions);
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

				if(IS_ADMINISTRATOR)
				{
					$out .= ($out != '' ? "&nbsp;" : "")."<i class='fa ".($item['addressPublic'] == 1 ? "fa-check green" : "fa-times red")." fa-lg' title='".($item['addressPublic'] == 1 ? __("Public", 'lang_address') : __("Not Public", 'lang_address'))."'></i>";

					$arr_duplicates = $obj_address->has_duplicate(array('item' => $item));

					if($intAddressDeleted == 0 && count($arr_duplicates) > 0)
					{
						$list_url = admin_url("admin.php?page=mf_address/list/index.php&intAddressID=".$intAddressID);

						$str_ids = $str_reason = "";

						foreach($arr_duplicates as $key => $arr_value)
						{
							$str_ids .= ($str_ids != '' ? "," : "").$key;

							if($arr_value['member_id'])
							{
								$str_reason .= ($str_reason != '' ? "," : "").__("Member ID", 'lang_address');
							}

							if($arr_value['birthdate'])
							{
								$str_reason .= ($str_reason != '' ? "," : "").__("Social Security Number", 'lang_address');
							}

							if($arr_value['email'])
							{
								$str_reason .= ($str_reason != '' ? "," : "").__("E-mail", 'lang_address');
							}
						}

						$out .= ($out != '' ? "&nbsp;" : "")."<a href='".wp_nonce_url($list_url."&btnAddressMerge&intAddressID=".$intAddressID."&is_public=".($item['addressPublic'] == 1)."&ids=".$str_ids."&paged=".check_var('paged'), 'address_merge_'.$intAddressID, '_wpnonce_address_merge')."'>"
							."<i class='far fa-clone red fa-lg' title='".sprintf(__("Merge with %d other because %s is equal", 'lang_address'), count($arr_duplicates), $str_reason)."'></i>"
						."</a>";
					}
				}

				if(get_option('setting_address_api_url') != '')
				{
					if(isset($item['addressSyncedDate']) && $item['addressSyncedDate'] > DEFAULT_DATE)
					{
						$out .= ($out != '' ? "&nbsp;" : "")."<i class='fas fa-network-wired' title='".sprintf(__("Synchronized %s", 'lang_address'), format_date($item['addressSyncedDate']))."'></i>";
					}
				}

				if(!function_exists('is_plugin_active') || function_exists('is_plugin_active') && is_plugin_active("mf_group/index.php") && isset($obj_group))
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
							if($obj_group->is_synced($intGroupID))
							{
								$out .= "<i class='fa fa-minus-square fa-lg grey' title='".__("The group is synced so you can not add or remove manually", 'lang_address')."'></i>";
							}

							else
							{
								$arr_stop_list_recipients = $obj_group->get_stop_list_recipients();

								if(!in_array($intGroupID, $obj_group->arr_stop_list_groups) && in_array($intAddressID, $arr_stop_list_recipients))
								{
									$out .= "<i class='fa fa-exclamation-triangle yellow' title='".__("This address is part of a stop list and will not be sent to", 'lang_address')."'></i> ";

									$out .= "<i class='set_tr_color' rel='red'></i>";
								}

								$out .= "<a href='".wp_nonce_url($list_url."&btnAddressRemove", 'address_remove_'.$intAddressID.'_'.$intGroupID, '_wpnonce_address_remove')."' rel='confirm' title='".__("Would you like to remove the address from the group?", 'lang_address')."'>
									<i class='fa fa-minus-square fa-lg red'></i>
								</a>";
							}

							if($intGroupAccepted == 0)
							{
								$out .= "&nbsp;";

								if(IS_SUPER_ADMIN)
								{
									$out .= "<a href='".wp_nonce_url($list_url."&btnAddressAccept", 'address_accept_'.$intAddressID.'_'.$intGroupID, '_wpnonce_address_accept')."' rel='confirm'>
										<i class='fa fa-check-square fa-lg grey' title='".__("The address has not been accepted to this group yet.", 'lang_address')." ".__("Do you want to manually accept it?", 'lang_address')."'></i>
									</a>";

									if(get_post_meta($intGroupID, 'group_reminder_subject') != '' && get_post_meta($intGroupID, 'group_reminder_text') != '')
									{
										if(!function_exists('is_plugin_active') || function_exists('is_plugin_active') && is_plugin_active("mf_group/index.php") && isset($obj_group))
										{
											$dteGroupAcceptanceLimit = date("Y-m-d H:i:s", strtotime("-6 hour"));
											$dteGroupAcceptanceSent = $obj_group->get_acceptance_sent(array('address_id' => $intAddressID, 'group_id' => $intGroupID));

											if($dteGroupAcceptanceSent < $dteGroupAcceptanceLimit)
											{
												$out .= "<a href='".wp_nonce_url($list_url."&btnAddressResend", 'address_resend_'.$intAddressID.'_'.$intGroupID, '_wpnonce_address_resend')."' rel='confirm'>
													<i class='fa fa-recycle fa-lg' title='".__("Do you want to send the message again?", 'lang_address')."'></i>
												</a>";
											}

											else
											{
												$hours_left = time_between_dates(array('start' => $dteGroupAcceptanceLimit, 'end' => $dteGroupAcceptanceSent, 'type' => 'round', 'return' => 'hours'));

												if($hours_left > 1)
												{
													$icon_title = sprintf(__("%d hours left until you can send the message again", 'lang_address'), $hours_left);
												}

												else if($hours_left == 1)
												{
													$icon_title = __("One hour left until you can send the message again", 'lang_address');
												}

												else
												{
													$icon_title = __("You can send the message again within the hour", 'lang_address');
												}

												$out .= "<i class='fa fa-recycle fa-lg grey' title='".$icon_title."'></i>";
											}
										}
									}
								}

								else
								{
									$out .= "<i class='fa fa-info-circle fa-lg' title='".__("The address has not been accepted to this group yet.", 'lang_address')."'></i>";
								}
							}
						}

						else
						{
							$out .= "<a href='".wp_nonce_url($list_url."&btnAddressRemove", 'address_remove_'.$intAddressID.'_'.$intGroupID, '_wpnonce_address_remove')."' rel='confirm' title='".__("The address has been unsubscribed. Would you like to remove the address from the group?", 'lang_address')."'>
								<span class='fa-stack fa-lg'>
									<i class='fa fa-envelope fa-stack-1x'></i>
									<i class='fa fa-ban fa-stack-2x red'></i>
								</span>
							</a>";
						}
					}

					else
					{
						if($obj_group->is_synced($intGroupID))
						{
							$out .= "<i class='fa fa-plus-square fa-lg grey' title='".__("The group is synced so you can not add or remove manually", 'lang_address')."'></i>";
						}

						else
						{
							$out .= "<a href='".wp_nonce_url($list_url."&btnAddressAdd", 'address_add_'.$intAddressID.'_'.$intGroupID, '_wpnonce_address_add')."' title='".__("Would you like to add the address to the group?", 'lang_address')."'>
								<i class='fa fa-plus-square fa-lg green'></i>
							</a>";

							/*if(IS_SUPER_ADMIN)
							{
								$out .= " (".var_export($item, true).")";
							}*/
						}
					}
				}
			break;

			case 'addressError':
				if($item['addressError'] > 0)
				{
					$out .= "<i class='fa fa-times red' title='".$item['addressError']." ".__("Errors", 'lang_address')."'></i>";
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
		global $wpdb;

		$this->table = "address";

		$this->prefix = $wpdb->prefix;

		$this->columns = array(
			'addressBirthDate' => __("Social Security Number", 'lang_address'),
			'addressFirstName' => __("First Name", 'lang_address'),
			'addressSurName' => __("Last Name", 'lang_address'),
			'addressCo' => __("C/O", 'lang_address'),
			'addressAddress' => __("Address", 'lang_address'),
			'addressZipCode' => __("Zip Code", 'lang_address'),
			'addressCity' => __("City", 'lang_address'),
			'addressCountry' => __("Country", 'lang_address'),
			'addressTelNo' => __("Phone Number", 'lang_address'),
			'addressWorkNo' => __("Work Number", 'lang_address'),
			'addressCellNo' => __("Mobile Number", 'lang_address'),
			'addressEmail' => __("E-mail", 'lang_address'),
		);

		$setting_address_extra_field = get_option('setting_address_extra_field');

		if(is_array($setting_address_extra_field) && count($setting_address_extra_field) > 0)
		{
			$this->columns['addressExtra'] = get_option_or_default('setting_address_extra', __("Extra", 'lang_address'));
		}

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
			$this->columns['addressMemberID'] = __("Member ID", 'lang_address');

			$this->unique_columns[] = 'addressMemberID';
		}
	}

	function filter_value($strRowField, $value)
	{
		switch($strRowField)
		{
			case 'addressBirthDate':
				$value = str_replace("-", "", $value);
			break;
		}

		return $value;
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

				//do_log("Trashed Address ".$id." (".$obj_address->get_name(array('address_id' => $id)).") in if_more_than_one()");
			}
		}
	}

	function inserted_new($id)
	{
		if(is_plugin_active("mf_group/index.php"))
		{
			global $obj_group;

			if(!isset($obj_group))
			{
				$obj_group = new mf_group();
			}

			$obj_group->add_address(array('address_id' => $id, 'group_id' => get_option('setting_group_import')));
		}
	}
}

class mf_address_export extends mf_export
{
	function get_defaults()
	{
		$this->plugin = "mf_address";
		$this->name = "address";
	}

	function fetch_request_xtra()
	{
		$this->arr_columns = check_var('arrColumns');
	}

	function get_form_xtra()
	{
		global $obj_address;

		if(!isset($obj_address))
		{
			$obj_address = new mf_address();
		}

		$out = show_select(array('data' => $obj_address->get_columns_for_select(), 'name' => 'arrColumns[]', 'text' => __("Columns", 'lang_address'), 'value' => $this->arr_columns));

		return $out;
	}

	function get_export_data()
	{
		global $wpdb, $obj_address;

		if(!isset($obj_address))
		{
			$obj_address = new mf_address();
		}

		if(!is_array($this->arr_columns) || count($this->arr_columns) == 0 || in_array('addressCountry', $this->arr_columns))
		{
			$arr_countries = $obj_address->get_countries_for_select();
		}

		$result = $wpdb->get_results("SELECT ".(count($this->arr_columns) > 0 ? implode(", ", $this->arr_columns) : "*")." FROM ".$wpdb->prefix."address WHERE addressDeleted = '0' GROUP BY addressID ORDER BY addressPublic ASC, addressSurName ASC, addressFirstName ASC", ARRAY_A);

		if($wpdb->num_rows > 0)
		{
			$arr_columns = $obj_address->get_columns_for_select();

			$data_temp = [];

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
				$data_temp = [];

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
}