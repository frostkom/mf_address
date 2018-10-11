<?php

$obj_address = new mf_address(array('type' => 'create'));
$obj_address->fetch_request();
echo $obj_address->save_data();
$obj_address->get_from_db();

echo "<div class='wrap'>
	<h2>".__("Address Book", 'lang_address')."</h2>"
	.get_notification()
	."<div id='poststuff'>
		<form action='#' method='post' id='mf_address' class='mf_form mf_settings'>
			<div id='post-body' class='columns-2'>
				<div id='post-body-content'>
					<div class='postbox'>
						<h3 class='hndle'><span>".__("Information", 'lang_address')."</span></h3>
						<div class='inside'>
							<div class='flex_flow'>";

								if(get_option('setting_address_display_member_id') != 'no')
								{
									echo show_textfield(array('name' => "intAddressMemberID", 'text' => __("Member ID", 'lang_address'), 'value' => $obj_address->member_id));
								}

								echo show_textfield(array('name' => "strAddressBirthDate", 'text' => __("Social Security Number", 'lang_address'), 'value' => $obj_address->birthdate))
							."</div>"
							."<div class='flex_flow'>"
								.show_textfield(array('name' => "strAddressFirstName", 'text' => __("First Name", 'lang_address'), 'value' => $obj_address->first_name))
								.show_textfield(array('name' => "strAddressSurName", 'text' => __("Last Name", 'lang_address'), 'value' => $obj_address->sur_name))
							."</div>"
							."<div class='flex_flow'>"
								.show_textfield(array('name' => "strAddressTelNo", 'text' => __("Phone Number", 'lang_address'), 'value' => $obj_address->telno))
								.show_textfield(array('name' => "strAddressCellNo", 'text' => __("Mobile Number", 'lang_address'), 'value' => $obj_address->cellno))
								.show_textfield(array('name' => "strAddressWorkNo", 'text' => __("Work Number", 'lang_address'), 'value' => $obj_address->workno))
							."</div>"
							.show_textfield(array('name' => "strAddressEmail", 'text' => __("E-mail", 'lang_address'), 'value' => $obj_address->email))
						."</div>
					</div>
				</div>
				<div id='postbox-container-1'>
					<div class='postbox'>
						<div class='inside'>"
							.show_button(array('name' => "btnAddressUpdate", 'text' => $obj_address->id > 0 ? __("Update", 'lang_address') : __("Add", 'lang_address')))
							.input_hidden(array('name' => "intAddressID", 'value' => $obj_address->id))
							.wp_nonce_field('address_update_'.$obj_address->id, '_wpnonce_address_update', true, false);

							if($obj_address->id > 0)
							{
								$dteAddressCreated = $wpdb->get_var($wpdb->prepare("SELECT addressCreated FROM ".get_address_table_prefix()."address WHERE addressID = '%d'", $obj_address->id));

								if($dteAddressCreated != '')
								{
									echo "<em>".sprintf(__("Created %s", 'lang_address'), format_date($dteAddressCreated))."</em>";
								}
							}

						echo "</div>
					</div>
					<div class='postbox'>
						<h3 class='hndle'><span>".__("Address", 'lang_address')."</span></h3>
						<div class='inside'>"
							.show_textfield(array('name' => "strAddressAddress", 'text' => __("Address", 'lang_address'), 'value' => $obj_address->address))
							.show_textfield(array('name' => "strAddressCo", 'text' => __("C/O", 'lang_address'), 'value' => $obj_address->co))
							."<div class='flex_flow'>"
								.show_textfield(array('name' => "intAddressZipCode", 'text' => __("Zip Code", 'lang_address'), 'value' => $obj_address->zipcode, 'type' => 'number'))
								.show_textfield(array('name' => "strAddressCity", 'text' => __("City", 'lang_address'), 'value' => $obj_address->city))
							."</div>"
							.show_select(array('data' => $obj_address->get_countries_for_select(), 'name' => "intAddressCountry", 'text' => __("Country", 'lang_address'), 'value' => $obj_address->country))
						."</div>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>";