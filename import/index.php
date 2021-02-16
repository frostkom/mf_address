<?php

$obj_address = new mf_address();
$obj_import = new mf_address_import();

echo "<div class='wrap'>
	<h2>".__("Import", $obj_address->lang_key)."</h2>"
	.get_notification()
	.$obj_import->do_display()
."</div>";