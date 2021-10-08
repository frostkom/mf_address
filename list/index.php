<?php

$obj_address = new mf_address(array('type' => 'list'));
$obj_address->fetch_request();
echo $obj_address->save_data();

echo "<div class='wrap'>
	<h2>"
		.__("Address Book", 'lang_address');

		/*if(is_plugin_active("mf_group/index.php") && $obj_group->id > 0)
		{
			echo "<span class='grey'>".$obj_group->get_name()."<a href='".admin_url("admin.php?page=mf_group/list/index.php")."'><i class='fa fa-times fa-lg red'></i></a></span>";
		}

		else
		{*/
			echo "<a href='".admin_url("admin.php?page=mf_address/create/index.php")."' class='add-new-h2'>".__("Add New", 'lang_address')."</a>";
		//}

	echo "</h2>"
	.get_notification();

	$tbl_group = new mf_address_table(array(
		'remember_search' => true,
	));

	$query_select = "";

	if(get_option('setting_address_api_url') != '')
	{
		$query_select .= ", addressSyncedDate";
	}

	$tbl_group->select_data(array(
		'select' => get_address_table_prefix()."address.addressID, addressPublic, addressBirthDate, addressFirstName, addressSurName, addressMemberID, addressDeleted, addressDeletedDate, addressDeletedID, addressSurName, addressAddress, addressCo, addressZipCode, addressCity, addressCountry, addressEmail, addressTelNo, addressCellNo, addressWorkNo, addressError".$query_select,
		//'debug' => true,
	));

	$tbl_group->do_display();

echo "</div>";