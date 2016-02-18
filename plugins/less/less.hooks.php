<?php
function less_parse_stylesheet(&$style, $base_path)
{
    if (substr($style, -5) == ".less") {
        if ($style[0] == "/" || $style[0] == "\\") {
            $style = ROOT . $style;
        }
        chdir($base_path);
        $less_file = array($style => "/");
        $options   = array('cache_dir' => ROOT . '/site/files/css', 'compress' => true);
        $style     = BASE_URL . "/site/files/css/" . Less_Cache::Get($less_file, $options);
    }
}
