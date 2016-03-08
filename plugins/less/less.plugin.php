<?php
namespace hooks\less {
    require LIBRARIES . '/less.php/Less.php';

    function parseStylesheet(&$style, $base_path)
    {
        if (substr($style, -5) == ".less") {
            if ($style[0] == "/" || $style[0] == "\\") {
                $style = ROOT . $style;
            }
            chdir($base_path);
            $less_file = array($style => '/');
            $options   = array('cache_dir' => ROOT . '/files/css', 'compress' => true);
            $style     = BASE_URL . '/files/css/' . \Less_Cache::Get($less_file, $options);
        }
    }
}
