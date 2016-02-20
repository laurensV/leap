<?php
	foreach($plugins as $name => $enabled){
		if(!$enabled){
			$link = "<a href='".BASE_URL."/admin/plugins/enable/". $name ."'>enable</a>";
		} else {
			$link = "<a href='".BASE_URL."/admin/plugins/disable/". $name ."'>disable</a>";
		}
		echo $name . " ". $link ."<br>";
	}
?>