<?php

function less_parse_stylesheet(&$style) {
    if(substr($style, -5) == ".less") {
        if($style[0] == "/"){
            $style  = ROOT . $style;
        }
        chdir(ROOT . "/site/");
        $less_file = array($style => "/");
        $options = array('cache_dir' => ROOT . '/site/files/css', 'compress' => true);
        $style = "/site/files/css/" . Less_Cache::Get( $less_file, $options );
    }
}
