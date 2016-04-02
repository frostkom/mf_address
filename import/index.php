<?php

$obj_import = new mf_address_import();

echo "<div class='wrap'>
	<h2>".__("Address Book", 'lang_address')."</h2>"
	.$obj_import->do_display()
."</div>";