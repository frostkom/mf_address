<?php

$obj_address = new mf_address(array('type' => 'list'));
$obj_address->fetch_request();
echo $obj_address->save_data();

echo "<div class='wrap'>
	<h2>"
		.__("Address Book", 'lang_address')
		."<a href='".admin_url("admin.php?page=mf_address/create/index.php")."' class='add-new-h2'>".__("Add New", 'lang_address')."</a>"
	."</h2>"
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
		'select' => $wpdb->prefix."address.addressID, addressPublic, addressBirthDate, addressFirstName, addressSurName, addressMemberID, addressDeleted, addressDeletedDate, addressDeletedID, addressSurName, addressAddress, addressCo, addressZipCode, addressCity, addressCountry, addressEmail, addressTelNo, addressCellNo, addressWorkNo, addressError, addressExtra".$query_select,
		//'debug' => true,
	));

	$tbl_group->do_display();

echo "</div>";