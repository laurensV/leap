<html>
<head>
<title><?php global $config; echo $config['application']['site_name'];?></title>
<?php
foreach ($css_files as $css_file) {
	echo '<link rel="stylesheet" type="text/css" href="' . $css_file .'">';
}
foreach ($scripts as $script) {
	echo '<script src="'.$script.'"></script>';
}
?>
</head>
<body>