<?php

$intAddressID = check_var('intAddressID');
$intAddressMemberID = check_var('intAddressMemberID');
$strAddressBirthDate = check_var('strAddressBirthDate');
$strAddressFirstName = check_var('strAddressFirstName');
$strAddressSurName = check_var('strAddressSurName');
$strAddressAddress = check_var('strAddressAddress');
$strAddressCo = check_var('strAddressCo');
$intAddressZipCode = check_var('intAddressZipCode');
$strAddressCity = check_var('strAddressCity');
$strAddressTelNo = check_var('strAddressTelNo');
$strAddressCellNo = check_var('strAddressCellNo');
$strAddressWorkNo = check_var('strAddressWorkNo');
$strAddressEmail = check_var('strAddressEmail');

if(isset($_POST['btnAddressUpdate']) && wp_verify_nonce($_POST['_wpnonce'], 'address_update_'.$intAddressID))
{
	if($intAddressMemberID != '' || $strAddressBirthDate != '' || $strAddressFirstName != '' || $strAddressSurName != '' || $intAddressZipCode != '' || $strAddressAddress != '' || $strAddressCo != '' || $strAddressTelNo != '' || $strAddressCellNo != '' || $strAddressWorkNo != '' || $strAddressEmail != '')
	{
		if($strAddressEmail != '' && !is_domain_valid($strAddressEmail))
		{
			$error_text = __("The e-mail address doesn't seam to be valid because the response is that the domain doesn't have e-mails connected to it", 'lang_address');
		}

		else
		{
			if($intAddressID > 0)
			{
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."address SET addressMemberID = '%d', addressBirthDate = %s, addressFirstName = %s, addressSurName = %s, addressZipCode = %s, addressCity = %s, addressAddress = %s, addressCo = %s, addressTelNo = %s, addressCellNo = %s, addressWorkNo = %s, addressEmail = %s WHERE addressID = '%d' AND (addressPublic = '1' OR addressPublic = '0' AND userID = '%d')", $intAddressMemberID, $strAddressBirthDate, $strAddressFirstName, $strAddressSurName, $intAddressZipCode, $strAddressCity, $strAddressAddress, $strAddressCo, $strAddressTelNo, $strAddressCellNo, $strAddressWorkNo, $strAddressEmail, $intAddressID, get_current_user_id()));

				$type = "updated";
			}

			else
			{
				$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."address SET addressPublic = '0', addressMemberID = '%d', addressBirthDate = %s, addressFirstName = %s, addressSurName = %s, addressZipCode = %s, addressCity = %s, addressAddress = %s, addressCo = %s, addressTelNo = %s, addressCellNo = %s, addressWorkNo = %s, addressEmail = %s, addressCreated = NOW(), userID = '%d'", $intAddressMemberID, $strAddressBirthDate, $strAddressFirstName, $strAddressSurName, $intAddressZipCode, $strAddressCity, $strAddressAddress, $strAddressCo, $strAddressTelNo, $strAddressCellNo, $strAddressWorkNo, $strAddressEmail, get_current_user_id()));

				$intAddressID = $wpdb->insert_id;

				$type = "created";
			}

			if($intAddressID > 0)
			{
				mf_redirect("/wp-admin/admin.php?page=mf_address/list/index.php&".$type);
			}

			else
			{
				$error_text = __("The information was not submitted, contact an admin if this persists", 'lang_address');
			}
		}
	}
}

echo "<div class='wrap'>
	<h2>".__("Address Book", 'lang_address')."</h2>"
	.get_notification()
	."<div id='poststuff' class='postbox'>
		<h3 class='hndle'>".__("Add", 'lang_address')."</h3>
		<div class='inside'>
			<form action='#' method='post' id='mf_address' class='mf_form mf_settings'>";

				if($intAddressID > 0 && !isset($_POST['btnAddressUpdate']))
				{
					$result = $wpdb->get_results($wpdb->prepare("SELECT addressMemberID, addressBirthDate, addressFirstName, addressSurName, addressAddress, addressCo, addressZipCode, addressCity, addressTelNo, addressCellNo, addressWorkNo, addressEmail, addressDeleted FROM ".$wpdb->base_prefix."address WHERE addressID = '%d'", $intAddressID));

					foreach($result as $r)
					{
						$intAddressMemberID = $r->addressMemberID;
						$strAddressBirthDate = $r->addressBirthDate;
						$strAddressFirstName = $r->addressFirstName;
						$strAddressSurName = $r->addressSurName;
						$strAddressAddress = $r->addressAddress;
						$strAddressCo = $r->addressCo;
						$intAddressZipCode = $r->addressZipCode;
						$strAddressCity = $r->addressCity;
						$strAddressTelNo = $r->addressTelNo;
						$strAddressCellNo = $r->addressCellNo;
						$strAddressWorkNo = $r->addressWorkNo;
						$strAddressEmail = $r->addressEmail;
						$intAddressDeleted = $r->addressDeleted;

						if($intAddressDeleted == 1)
						{
							$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."address SET addressDeleted = '0', addressDeletedID = '', addressDeletedDate = '' WHERE addressPublic = '0' AND addressID = '%d' AND userID = '%d'", $intAddressID, get_current_user_id()));
						}
					}
				}

				echo "<div class='flex_flow'>";

					if(get_option('setting_show_member_id') != 'no')
					{
						echo show_textfield(array('name' => "intAddressMemberID", 'text' => __("MemberID", 'lang_address'), 'value' => $intAddressMemberID));
					}

					echo show_textfield(array('name' => "strAddressBirthDate", 'text' => __("Social Security Number", 'lang_address'), 'value' => $strAddressBirthDate))
				."</div>"
				."<div class='flex_flow'>"
					.show_textfield(array('name' => "strAddressFirstName", 'text' => __("First Name", 'lang_address'), 'value' => $strAddressFirstName))
					.show_textfield(array('name' => "strAddressSurName", 'text' => __("Last Name", 'lang_address'), 'value' => $strAddressSurName))
				."</div>"
				."<div class='flex_flow'>"
					.show_textfield(array('name' => "strAddressAddress", 'text' => __("Address", 'lang_address'), 'value' => $strAddressAddress))
					.show_textfield(array('name' => "strAddressCo", 'text' => __("C/O", 'lang_address'), 'value' => $strAddressCo))
					.show_textfield(array('name' => "intAddressZipCode", 'text' => __("Zip Code", 'lang_address'), 'value' => $intAddressZipCode, 'type' => 'number'))
					.show_textfield(array('name' => "strAddressCity", 'text' => __("City", 'lang_address'), 'value' => $strAddressCity))
				."</div>"
				."<div class='flex_flow'>"
					.show_textfield(array('name' => "strAddressTelNo", 'text' => __("Phone Number", 'lang_address'), 'value' => $strAddressTelNo))
					.show_textfield(array('name' => "strAddressCellNo", 'text' => __("Mobile Number", 'lang_address'), 'value' => $strAddressCellNo))
					.show_textfield(array('name' => "strAddressWorkNo", 'text' => __("Work Number", 'lang_address'), 'value' => $strAddressWorkNo))
				."</div>"
				.show_textfield(array('name' => "strAddressEmail", 'text' => __("E-mail", 'lang_address'), 'value' => $strAddressEmail))
				.show_button(array('name' => "btnAddressUpdate", 'text' => $intAddressID > 0 ? __("Update", 'lang_address') : __("Add", 'lang_address')))
				.input_hidden(array('name' => "intAddressID", 'value' => $intAddressID))
				.wp_nonce_field('address_update_'.$intAddressID, '_wpnonce', true, false)
			."</form>
		</div>
	</div>
</div>";