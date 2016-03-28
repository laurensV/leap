<?php
namespace Leap\Hooks\Less {
    function hook_parseStylesheet(&$style, $base_path)
    {
        if (substr($style, -5) == ".less") {
            if ($style[0] == "/" || $style[0] == "\\") {
                $style = ROOT . substr($style, 1);
            }
            chdir($base_path);
            $less_file = array($style => '/');
            $options   = array('cache_dir' => ROOT . 'files/css', 'compress' => true);
            $style     = BASE_URL . 'files/css/' . \Less_Cache::Get($less_file, $options);
        }
    }
}
