<?php
function printr($data, $exit = true)
{
    if ($data) {
        print '<pre>';
        print_r($data);
        print '</pre>';
    }
    if ($exit) {
        exit;
    }
}
