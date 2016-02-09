<html>
<head>
<title><?php echo $site_title;?></title>
<?php
if (isset($this->styles)){
	foreach ($this->styles as $css_file) {
		echo '<link rel="stylesheet" type="text/css" href="' . $css_file .'">';
	}
}
if (isset($this->scripts)){
	foreach ($this->scripts as $script) {
		echo '<script src="'.$script.'"></script>';
	}
}
?>
</head>
<body>