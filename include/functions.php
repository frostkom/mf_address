<?php

function get_address_table_prefix()
{
	global $wpdb;

	$setting_address_site_wide = get_site_option('setting_address_site_wide', 'yes');

	return $setting_address_site_wide == 'yes' ? $wpdb->base_prefix : $wpdb->prefix;
}