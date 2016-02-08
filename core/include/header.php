<html>
<head>
<title><?php echo SITE_NAME?></title>
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