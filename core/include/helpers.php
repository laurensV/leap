<?php
function printr($data, $exit = TRUE) {
  if ($data) {
    print '<pre>';
    print_r($data);
    print '</pre>';
  }
  if ($exit) {
    exit;
  }
}