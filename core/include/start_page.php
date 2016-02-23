<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<title><?php echo $site_title; ?></title>
<?php
if (isset($this->stylesheets)) {
    foreach ($this->stylesheets as $css_file) {
        echo '<link rel="stylesheet" type="text/css" href="' . $css_file . '">';
    }
}
if (isset($this->scripts)) {
    foreach ($this->scripts as $script) {
        echo '<script src="' . $script . '"></script>';
    }
}
?>
</head>
<body>