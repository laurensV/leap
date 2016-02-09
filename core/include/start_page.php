<html>
<head>
<title><?php global $config; echo $config['application']['site_name'];?></title>
<?php
if (isset($css_files)){
	foreach ($css_files as $css_file) {
		echo '<link rel="stylesheet" type="text/css" href="' . $css_file .'">';
	}
}
if (isset($scripts)){
	foreach ($scripts as $script) {
		echo '<script src="'.$script.'"></script>';
	}
}
?>
</head>
<body>