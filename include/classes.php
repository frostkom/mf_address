<?php

class mf_address
{
	function mf_address($id = 0)
	{
		if($id > 0)
		{
			$this->id = $id;
		}

		else
		{
			$this->id = check_var('intAddressID');
		}
	}

	function fetch_request()
	{
		$this->group_id = check_var('intGroupID');
	}

	function save_data()
	{
		global $wpdb, $error_text, $done_text;

		$obj_group = new mf_group();

		$out = "";

		if(isset($_REQUEST['btnAddressDelete']) && $this->id > 0 && wp_verify_nonce($_REQUEST['_wpnonce'], 'address_delete'))
		{
			$this->trash();

			$done_text = __("The address was deleted", 'lang_address');
		}

		else if(isset($_GET['btnAddressAdd']) && $this->group_id > 0 && $this->id > 0 && wp_verify_nonce($_REQUEST['_wpnonce'], 'address_add'))
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".$wpdb->base_prefix."address2group WHERE addressID = '%d' AND groupID = '%d'", $this->id, $this->group_id));

			if($wpdb->num_rows == 0)
			{
				$obj_group->add_address(array('address_id' => $this->id, 'group_id' => $this->group_id));

				$done_text = __("The address was added to the group", 'lang_address');
			}
		}

		else if(isset($_GET['btnAddressRemove']) && $this->group_id > 0 && $this->id > 0 && wp_verify_nonce($_REQUEST['_wpnonce'], 'address_remove'))
		{
			$result = $wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".$wpdb->base_prefix."address2group WHERE addressID = '%d' AND groupID = '%d'", $this->id, $this->group_id));

			if($wpdb->num_rows > 0)
			{
				$obj_group->remove_address($this->id, $this->group_id);

				$done_text = __("The address was removed from the group", 'lang_address');
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

		return $out;
	}

	function insert($data)
	{
		global $wpdb;

		if(!isset($data['public'])){	$data['public'] = false;}

		if($data['email'] != '')
		{
			$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."address SET addressPublic = '%d', addressEmail = %s, addressCreated = NOW(), userID = '%d'", $data['public'], $data['email'], get_current_user_id()));

			return $wpdb->insert_id;
		}
	}

	function trash($id = 0)
	{
		global $wpdb;

		if($id > 0)
		{
			$this->id = $id;
		}

		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."address SET addressDeleted = '1', addressDeletedID = '%d', addressDeletedDate = NOW() WHERE addressID = '%d'".(IS_ADMIN ? "" : " AND addressPublic = '0' AND userID = '".get_current_user_id()."'"), get_current_user_id(), $this->id));
	}

	function delete($id = 0)
	{
		global $wpdb;

		if($id > 0)
		{
			$this->id = $id;
		}

		$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."address WHERE addressID = '%d'", $this->id));
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
			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."address SET addressError = 0 WHERE addressID = '%d'", $this->id));
		}
	}
}

if(!class_exists('mf_list_table'))
{
	require_once(ABSPATH.'wp-content/plugins/mf_base/include/classes.php');
}

class mf_address_table extends mf_list_table
{
	function set_default()
	{
		global $wpdb, $intGroupID;

		$this->arr_settings['query_from'] = $wpdb->base_prefix."address";
		$this->post_type = "";

		$this->arr_settings['query_select_id'] = "addressID";
		$this->arr_settings['query_all_id'] = "0";
		$this->arr_settings['query_trash_id'] = "1";
		$this->orderby_default = "addressSurName";

		$this->arr_settings['has_autocomplete'] = true;
		$this->arr_settings['plugin_name'] = 'mf_address';
		
		$this->query_where .= ($this->query_where != '' ? " AND " : "")."(addressPublic = '1' OR addressPublic = '0' AND userID = '".get_current_user_id()."')";

		if($this->search != '')
		{
			$this->query_where .= ($this->query_where != '' ? " AND " : "")."(addressBirthDate LIKE '%".$this->search."%' OR addressFirstName LIKE '%".$this->search."%' OR addressSurName LIKE '%".$this->search."%' OR addressAddress LIKE '%".$this->search."%')";
		}

		if(isset($_GET['no_ses'])){	$is_part_of_group = check_var('is_part_of_group', 'int', true, '', false, 'get');}
		else{						$is_part_of_group = check_var('is_part_of_group', 'int');}

		if($is_part_of_group){		$_SESSION['is_part_of_group'] = $is_part_of_group;}
		else{						unset($_SESSION['is_part_of_group']);}

		list($this->query_join, $this->query_where) = get_address_search_query($this->search);

		$this->set_views(array(
			'db_field' => 'addressDeleted',
			'types' => array(
				'0' => __("All", 'lang_address'),
				'1' => __("Trash", 'lang_address')
			),
		));

		$arr_columns = array(
			//'cb' => '<input type="checkbox">',
		);

		if(function_exists('is_plugin_active') && is_plugin_active("mf_group/index.php"))
		{
			if($intGroupID > 0)
			{
				$arr_columns['is_part_of_group'] = "<a href='?page=mf_address/list/index.php&is_part_of_group=0&no_ses'><i class='fa fa-plus-square'></i></a>&nbsp;/&nbsp;<a href='?page=mf_address/list/index.php&is_part_of_group=1&no_ses'><i class='fa fa-minus-square'></i></a>";
			}

			$arr_columns['groups'] = "";
		}

		$arr_columns['addressError'] = "";
		$arr_columns['addressSurName'] = __("Name", 'lang_address');
		$arr_columns['addressAddress'] = __("Address", 'lang_address');
		$arr_columns['addressContact'] = __("Contact", 'lang_address');
		$arr_columns['addressExtra'] = get_option_or_default('setting_address_extra', __("Extra", 'lang_address'));

		$this->set_columns($arr_columns);

		$this->set_sortable_columns(array(
			'addressSurName',
			'addressExtra'
		));
	}

	function column_default($item, $column_name)
	{
		global $wpdb, $intGroupID;

		$out = "";

		$intAddressID = $item['addressID'];

		switch($column_name)
		{
			case 'is_part_of_group':
				if($intGroupID > 0)
				{
					$result_check = $wpdb->get_results($wpdb->prepare("SELECT groupID, groupUnsubscribed FROM ".$wpdb->base_prefix."address2group WHERE addressID = '%d' AND groupID = '%d' LIMIT 0, 1", $intAddressID, $intGroupID));

					$intGroupID_check = $intGroupUnsubscribed = 0;
					
					foreach($result_check as $r)
					{
						$intGroupID_check = $r->groupID;
						$intGroupUnsubscribed = $r->groupUnsubscribed;
					}

					if($intGroupID == $intGroupID_check && $intGroupUnsubscribed == 0)
					{
						$out .= "<a href='".wp_nonce_url("?page=mf_address/list/index.php&btnAddressRemove&intAddressID=".$intAddressID."&intGroupID=".$intGroupID, 'address_remove')."' rel='confirm'>
							<i class='fa fa-lg fa-minus-square red'></i>
						</a>";
					}

					else if($intGroupID == $intGroupID_check && $intGroupUnsubscribed == 1)
					{
						$out .= "<a href='".wp_nonce_url("?page=mf_address/list/index.php&btnAddressRemove&intAddressID=".$intAddressID."&intGroupID=".$intGroupID, 'address_remove')."'>
							<span class='fa-stack fa-lg'>
								<i class='fa fa-envelope fa-stack-1x'></i>
								<i class='fa fa-ban fa-stack-2x red'></i>
							</span>
						</a>";
					}

					else
					{
						$out .= "<a href='".wp_nonce_url("?page=mf_address/list/index.php&btnAddressAdd&intAddressID=".$intAddressID."&intGroupID=".$intGroupID, 'address_add')."'>
							<i class='fa fa-lg fa-plus-square green'></i>
						</a>";
					}
				}
			break;

			case 'groups':
				$str_groups = "";

				$resultGroups = $wpdb->get_results($wpdb->prepare("SELECT groupID FROM ".$wpdb->base_prefix."address2group WHERE addressID = '%d'", $intAddressID));

				foreach($resultGroups as $r)
				{
					$str_groups .= ($str_groups != '' ? ", " : "").group_name($r->groupID);
				}

				if($str_groups != '')
				{
					$out .= "<i class='fa fa-group' title='".$str_groups."'></i>";
				}
			break;

			case 'addressError':
				if($item[$column_name] > 0)
				{
					$out .= "<i class='fa fa-close red' title='".$item[$column_name]." ".__("Errors", 'lang_address')."'></i>";
				}
			break;

			case 'addressSurName':
				$intAddressPublic = $item['addressPublic'];
				$strAddressBirthDate = $item['addressBirthDate'];
				$strAddressFirstName = $item['addressFirstName'];
				$strAddressSurName = $item['addressSurName'];
				$intAddressMemberID = $item['addressMemberID'];
				$intAddressDeleted = $item['addressDeleted'];
				$dteAddressDeletedDate = $item['addressDeletedDate'];

				/*if($intAddressDeleted == 1 && $dteAddressDeletedDate < date("Y-m-d H:i:s", strtotime("-1 month")))
				{
					global $obj_group, $obj_address;

					$obj_group->remove_address($intAddressID);
					$obj_address->delete($intAddressID);
				}*/

				if($strAddressFirstName != '' || $strAddressSurName != '')
				{
					$strAddressName = $strAddressFirstName." ".$strAddressSurName;
				}

				else
				{
					$strAddressName = "(".__("Unknown", 'lang_address').")";
				}

				$post_edit_url = "?page=mf_address/create/index.php&intAddressID=".$intAddressID;

				$actions = array();

				if($intAddressDeleted == 0)
				{
					if($intAddressPublic == 0 || IS_ADMIN)
					{
						$actions['edit'] = "<a href='".$post_edit_url."'>".__("Edit", 'lang_address')."</a>";

						$actions['delete'] = "<a href='".wp_nonce_url("?page=mf_address/list/index.php&btnAddressDelete&intAddressID=".$intAddressID, 'address_delete')."'>".__("Delete", 'lang_address')."</a>";
					}
				}

				else
				{
					$actions['recover'] = "<a href='".$post_edit_url."&recover'>".__("Recover", 'lang_address')."</a>";
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

				if($strAddressAddress != '')
				{
					$out .= $strAddressAddress."<br>";
				}

				$out .= $intAddressZipCode." ".$strAddressCity;
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

				if($strAddressCellNo != '')
				{
					$str_numbers .= ($str_numbers != '' ? " | " : "")."<a href='".format_phone_no($strAddressCellNo)."'>".$strAddressCellNo."</a>";
				}

				if($strAddressTelNo != '')
				{
					$str_numbers .= ($str_numbers != '' ? " | " : "")."<a href='".format_phone_no($strAddressTelNo)."'>".$strAddressTelNo."</a>";
				}

				if($strAddressWorkNo != '')
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
		$this->table = "address";
		$this->actions = array(
			'delete',
			'import',
		);
		$this->columns = array(
			'addressBirthDate' => __("Social Security Number", 'lang_address'),
			'addressFirstName' => __("First Name", 'lang_address'),
			'addressSurName' => __("Last Name", 'lang_address'),
			'addressCo' => __("C/O", 'lang_address'),
			'addressAddress' => __("Address", 'lang_address'),
			'addressZipCode' => __("Zip Code", 'lang_address'),
			'addressCity' => __("City", 'lang_address'),
			'addressTelNo' => __("Phone Number", 'lang_address'),
			'addressWorkNo' => __("Work Number", 'lang_address'),
			'addressCellNo' => __("Mobile Number", 'lang_address'),
			'addressEmail' => __("E-mail", 'lang_address'),
			'addressExtra' => get_option_or_default('setting_address_extra', __("Extra", 'lang_address')),
		);
		$this->unique_columns = array(
			'addressBirthDate',
		);
		$this->validate_columns = array(
			'addressTelNo' => 'telno',
			'addressWorkNo' => 'telno',
			'addressCellNo' => 'telno',
			'addressEmail' => 'email',
		);

		$option = get_option('setting_show_member_id');

		if($option != 'no')
		{
			$this->columns['addressMemberID'] = __("MemberID", 'lang_address');

			$this->unique_columns[] = 'addressMemberID';
		}
	}

	function if_more_than_one($id)
	{
		global $wpdb;

		$wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".$wpdb->base_prefix."address2group WHERE addressID = '%d'", $id));

		if($wpdb->num_rows == 0)
		{
			$obj_address = new mf_address();
			$obj_address->trash($id);

			$this->rows_deleted++;
		}
	}

	function inserted_new($id)
	{
		global $wpdb;

		$option = get_option('setting_group_import');

		$obj_group = new mf_group();

		$obj_group->add_address(array('address_id' => $id, 'group_id' => $option));
	}
}