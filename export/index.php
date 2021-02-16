<?php

$obj_address = new mf_address();
$obj_export = new mf_address_export();

echo "<div class='wrap'>
	<h2>".__("Export", $obj_address->lang_key)."</h2>"
	.get_notification()
	.$obj_export->get_form()
."</div>";