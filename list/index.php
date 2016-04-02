<?php

//$intAddressID = check_var('intAddressID');
$intGroupID = check_var('intGroupID');

$obj_group = new mf_group();
$obj_address = new mf_address();
$obj_address->fetch_request();
echo $obj_address->save_data();

if(isset($_GET['no_ses'])){	$is_part_of_group = check_var('is_part_of_group', 'int', true, '', false, 'get');}
else{						$is_part_of_group = check_var('is_part_of_group', 'int');}

if($obj_group->id > 0){		$_SESSION['intGroupID'] = $obj_group->id;}
else{						unset($_SESSION['intGroupID']);}

if($is_part_of_group){		$_SESSION['is_part_of_group'] = $is_part_of_group;}
else{						unset($_SESSION['is_part_of_group']);}

echo "<div class='wrap'>
	<h2>"
		.__("Address Book", 'lang_address');

		if($obj_group->id > 0)
		{
			echo " ".__("for", 'lang_address')." ".get_group_name($obj_group->id)." <a href='?page=mf_group/list/index.php'><i class='fa fa-lg fa-close red'></i></a>";
		}

		else
		{
			echo "<a href='?page=mf_address/create/index.php' class='add-new-h2'>".__("Add New", 'lang_address')."</a>";
		}

	echo "</h2>"
	.get_notification();

	$tbl_group = new mf_address_table();

	$tbl_group->select_data(array(
		//'select' => "*",
	));

	$tbl_group->do_display();

echo "</div>";