<?php

if(!defined('ABSPATH'))
{
	header('Content-Type: application/json');

	$folder = str_replace("/wp-content/plugins/mf_address/include/api", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

$json_output = array();

$type = check_var('type', 'char');

if(get_current_user_id() > 0)
{
	if($type == "table_search")
	{
		$tbl_group = new mf_address_table();

		$tbl_group->select_data(array(
			'select' => "addressFirstName, addressSurName, addressEmail, addressCellNo",
			'limit' => 0, 'amount' => 10
		));

		foreach($tbl_group->data as $r)
		{
			$strAddressFirstName = $r['addressFirstName'];
			$strAddressSurName = $r['addressSurName'];
			$strAddressEmail = $r['addressEmail'];
			$strAddressCellNo = $r['addressCellNo'];

			if($strAddressFirstName != '' && $strAddressSurName != '')
			{
				$strAddressName = $strAddressFirstName." ".$strAddressSurName;
			}

			else if($strAddressEmail != '')
			{
				$strAddressName = $strAddressEmail;
			}

			else if($strAddressCellNo != '')
			{
				$strAddressName = $strAddressCellNo;
			}

			else
			{
				$strAddressName = "(".__("Unknown", 'lang_address').")";
			}

			if(!in_array($strAddressName, $json_output))
			{
				$json_output[] = $strAddressName;
			}
		}
	}
}

echo json_encode($json_output);