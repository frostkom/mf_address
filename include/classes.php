<?php

class mf_address
{
	function __construct($id = 0)
	{
		if($id > 0)
		{
			$this->id = $id;
		}

		else
		{
			$this->id = check_var('intAddressID');
		}

		$this->has_group_plugin = is_plugin_active("mf_group/index.php");
	}

	function fetch_request()
	{
		$this->group_id = check_var('intGroupID');
	}

	function save_data()
	{
		global $wpdb, $error_text, $done_text;

		if($this->has_group_plugin)
		{
			$obj_group = new mf_group();
		}

		$out = "";

		if(isset($_REQUEST['btnAddressDelete']) && $this->id > 0 && wp_verify_nonce($_REQUEST['_wpnonce'], 'address_delete_'.$this->id))
		{
			$this->trash();

			$done_text = __("The address was deleted", 'lang_address');
		}

		else if(isset($_REQUEST['btnAddressRecover']) && $this->id > 0 && wp_verify_nonce($_REQUEST['_wpnonce'], 'address_recover_'.$this->id))
		{
			$this->recover();

			$done_text = __("I recovered the address for you", 'lang_address');
		}

		else if(isset($_GET['btnAddressAdd']) && $this->group_id > 0 && $this->id > 0 && wp_verify_nonce($_REQUEST['_wpnonce'], 'address_add_'.$this->id.'_'.$this->group_id))
		{
			$wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".$wpdb->prefix."address2group WHERE addressID = '%d' AND groupID = '%d' LIMIT 0, 1", $this->id, $this->group_id));

			if($wpdb->num_rows == 0)
			{
				if($this->has_group_plugin)
				{
					$obj_group->add_address(array('address_id' => $this->id, 'group_id' => $this->group_id));
				}

				$done_text = __("The address was added to the group", 'lang_address');
			}

			else
			{
				$error_text = __("The address already exists in the group", 'lang_address');
			}
		}

		else if(isset($_GET['btnAddressRemove']) && $this->group_id > 0 && $this->id > 0 && wp_verify_nonce($_REQUEST['_wpnonce'], 'address_remove_'.$this->id.'_'.$this->group_id))
		{
			$wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".$wpdb->prefix."address2group WHERE addressID = '%d' AND groupID = '%d' LIMIT 0, 1", $this->id, $this->group_id));

			if($wpdb->num_rows > 0)
			{
				if($this->has_group_plugin)
				{
					$obj_group->remove_address($this->id, $this->group_id);
				}

				$done_text = __("The address was removed from the group", 'lang_address');
			}

			else
			{
				$error_text = __("The address could not be removed since it didn't exist in the group", 'lang_address');
			}
		}

		else if(isset($_GET['btnAddressResend']) && $this->group_id > 0 && $this->id > 0 && wp_verify_nonce($_REQUEST['_wpnonce'], 'address_resend_'.$this->id.'_'.$this->group_id))
		{
			if($this->has_group_plugin)
			{
				$obj_group->send_acceptance_message(array('address_id' => $this->id, 'group_id' => $this->group_id));

				$done_text = __("The message was sent", 'lang_address');
			}

			else
			{
				$error_text = __("The group plugin does not seam to be in use", 'lang_address');
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

	function get_countries_for_select($data = array())
	{
		if(!isset($data['add_choose_here'])){	$data['add_choose_here'] = true;}
		if(!isset($data['exclude'])){			$data['exclude'] = array();}

		$arr_data = array();

		if($data['add_choose_here'] == true)
		{
			$arr_data[''] = "-- ".__("Choose here", 'lang_users')." --";
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
			112	=> __("Korea, Democratic People's Republic of", 'lang_address'),
			113	=> __("Korea, Republic of", 'lang_address'),
			114	=> __("Kuwait", 'lang_address'),
			115	=> __("Kyrgyzstan", 'lang_address'),
			116	=> __("Lao People's Democratic Republic", 'lang_address'),
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

	function recover($id = 0)
	{
		global $wpdb;

		if($id > 0)
		{
			$this->id = $id;
		}

		$wpdb->query($wpdb->prepare("UPDATE ".get_address_table_prefix()."address SET addressDeleted = '0', addressDeletedID = '%d', addressDeletedDate = NOW() WHERE addressID = '%d'", get_current_user_id(), $this->id));
	}

	function trash($id = 0)
	{
		global $wpdb;

		if($id > 0)
		{
			$this->id = $id;
		}

		$wpdb->query($wpdb->prepare("UPDATE ".get_address_table_prefix()."address SET addressDeleted = '1', addressDeletedID = '%d', addressDeletedDate = NOW() WHERE addressID = '%d'".(IS_ADMIN ? "" : " AND addressPublic = '0' AND userID = '".get_current_user_id()."'"), get_current_user_id(), $this->id));
	}

	function delete($id = 0)
	{
		global $wpdb;

		if($id > 0)
		{
			$this->id = $id;
		}

		$wpdb->query($wpdb->prepare("DELETE FROM ".get_address_table_prefix()."address WHERE addressID = '%d'", $this->id));
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
	require_once(ABSPATH.'wp-content/plugins/mf_base/include/classes.php');
}

class mf_address_table extends mf_list_table
{
	function set_default()
	{
		global $wpdb, $is_part_of_group, $obj_group;

		$this->arr_settings['query_from'] = get_address_table_prefix()."address";
		$this->post_type = "";

		$this->arr_settings['query_select_id'] = "addressID";
		$this->arr_settings['query_all_id'] = "0";
		$this->arr_settings['query_trash_id'] = "1";
		$this->orderby_default = "addressSurName";

		$this->arr_settings['has_autocomplete'] = true;
		$this->arr_settings['plugin_name'] = 'mf_address';

		if(!IS_ADMIN)
		{
			$this->query_where .= ($this->query_where != '' ? " AND " : "")."(addressPublic = '1' OR addressPublic = '0' AND userID = '".get_current_user_id()."')";
		}

		if($this->search != '')
		{
			$this->query_where .= ($this->query_where != '' ? " AND " : "")."(addressBirthDate LIKE '%".$this->search."%' OR addressFirstName LIKE '%".$this->search."%' OR addressSurName LIKE '%".$this->search."%' OR CONCAT(addressFirstName, ' ', addressSurName) LIKE '%".$this->search."%' OR addressAddress LIKE '%".$this->search."%' OR addressZipCode LIKE '%".$this->search."%' OR addressCity LIKE '%".$this->search."%' OR addressTelNo LIKE '%".$this->search."%' OR addressWorkNo LIKE '%".$this->search."%' OR addressCellNo LIKE '%".$this->search."%' OR addressEmail LIKE '%".$this->search."%')";
		}

		if($is_part_of_group)
		{
			$this->query_join .= " INNER JOIN ".$wpdb->prefix."address2group USING (addressID)";
			$this->query_where .= ($this->query_where != '' ? " AND " : "")."groupID = '".$obj_group->id."'";
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
				'1' => __("Trash", 'lang_address')
			),
		));

		$arr_columns = array(
			//'cb' => '<input type="checkbox">',
		);

		$arr_columns['addressSurName'] = __("Name", 'lang_address');
		$arr_columns['addressAddress'] = __("Address", 'lang_address');

		if(function_exists('is_plugin_active') && is_plugin_active("mf_group/index.php") && isset($obj_group))
		{
			if(isset($obj_group->id) && $obj_group->id > 0)
			{
				$group_url = "?page=mf_address/list/index.php&no_ses&is_part_of_group=%d";

				$arr_columns['is_part_of_group'] = "<span class='nowrap'><a href='".sprintf($group_url, '0')."'><i class='fa fa-plus-square'></i></a>&nbsp;/&nbsp;<a href='".sprintf($group_url, '1')."'><i class='fa fa-minus-square'></i></a></span>";
			}

			$arr_columns['groups'] = "";
		}

		$arr_columns['addressError'] = "";

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
		global $wpdb, $obj_group;

		$out = "";

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

						$actions['delete'] = "<a href='".wp_nonce_url("?page=mf_address/list/index.php&btnAddressDelete&intAddressID=".$intAddressID, 'address_delete_'.$intAddressID)."'>".__("Delete", 'lang_address')."</a>";
					}
				}

				else
				{
					$actions['recover'] = "<a href='".wp_nonce_url("?page=mf_address/list/index.php&btnAddressRecover&intAddressID=".$intAddressID, 'address_recover_'.$intAddressID)."'>".__("Recover", 'lang_address')."</a>"; //".$post_edit_url."&recover
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
					$obj_address = new mf_address();
					$arr_countries = $obj_address->get_countries_for_select();

					if(isset($arr_countries[$intAddressCountry]))
					{
						$out .= " (".$arr_countries[$intAddressCountry].")";
					}
				}
			break;

			case 'is_part_of_group':
				if($obj_group->id > 0)
				{
					$result_check = $wpdb->get_results($wpdb->prepare("SELECT groupID, groupAccepted, groupUnsubscribed FROM ".$wpdb->prefix."address2group WHERE addressID = '%d' AND groupID = '%d' LIMIT 0, 1", $intAddressID, $obj_group->id));

					$intGroupID_check = $intGroupAccepted = $intGroupUnsubscribed = 0;

					foreach($result_check as $r)
					{
						$intGroupID_check = $r->groupID;
						$intGroupAccepted = $r->groupAccepted;
						$intGroupUnsubscribed = $r->groupUnsubscribed;
					}

					if($obj_group->id == $intGroupID_check)
					{
						if($intGroupUnsubscribed == 0)
						{
							$out .= "<a href='".wp_nonce_url("?page=mf_address/list/index.php&btnAddressRemove&intAddressID=".$intAddressID."&intGroupID=".$obj_group->id, 'address_remove_'.$intAddressID.'_'.$obj_group->id)."' rel='confirm'>
								<i class='fa fa-lg fa-minus-square red'></i>
							</a>";

							if($intGroupAccepted == 0)
							{
								$out .= "&nbsp;";

								if(IS_SUPER_ADMIN)
								{
									$out .= "<a href='".wp_nonce_url("?page=mf_address/list/index.php&btnAddressResend&intAddressID=".$intAddressID."&intGroupID=".$obj_group->id, 'address_resend_'.$intAddressID.'_'.$obj_group->id)."' rel='confirm'>
										<i class='fa fa-lg fa-info-circle' title='".__("The address has not been accepted to this group yet.", 'lang_address')." ".__("Do you want to send it again?", 'lang_address')."'></i>
									</a>";
								}

								else
								{
									$out .= "<i class='fa fa-lg fa-info-circle' title='".__("The address has not been accepted to this group yet.", 'lang_address')."'></i>";
								}
							}
						}

						else
						{
							$out .= "<a href='".wp_nonce_url("?page=mf_address/list/index.php&btnAddressRemove&intAddressID=".$intAddressID."&intGroupID=".$obj_group->id, 'address_remove_'.$intAddressID.'_'.$obj_group->id)."' rel='confirm'>
								<span class='fa-stack fa-lg'>
									<i class='fa fa-envelope fa-stack-1x'></i>
									<i class='fa fa-ban fa-stack-2x red'></i>
								</span>
							</a>";
						}
					}

					else
					{
						$out .= "<a href='".wp_nonce_url("?page=mf_address/list/index.php&btnAddressAdd&intAddressID=".$intAddressID."&intGroupID=".$obj_group->id, 'address_add_'.$intAddressID.'_'.$obj_group->id)."'>
							<i class='fa fa-lg fa-plus-square green'></i>
						</a>";
					}
				}
			break;

			case 'groups':
				$obj_group_temp = new mf_group();

				$str_groups = "";

				$resultGroups = $wpdb->get_results($wpdb->prepare("SELECT groupID FROM ".$wpdb->prefix."address2group WHERE addressID = '%d'", $intAddressID));

				foreach($resultGroups as $r)
				{
					$str_groups .= ($str_groups != '' ? ", " : "").$obj_group_temp->get_name($r->groupID);
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
			'addressCountry' => __("Country", 'lang_address'),
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

		$wpdb->get_results($wpdb->prepare("SELECT addressID FROM ".$wpdb->prefix."address2group WHERE addressID = '%d' LIMIT 0, 1", $id));

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