<?php

$obj_import = new mf_address_import();

echo "<div class='wrap'>
	<h2>".__("Import", 'lang_address')."</h2>"
	.get_notification()
	.$obj_import->do_display()
."</div>";