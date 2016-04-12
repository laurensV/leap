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

function strReplaceFirst($search, $replace, $subject)
{
    $pos = strpos($subject, $search);
    if ($pos !== false) {
        $subject = substr_replace($subject, $replace, $pos, strlen($search));
    }
    return $subject;
}

$wildcards_from_url = array();
function arg($id = null, $args_raw = null)
{
    global $wildcards_from_url;
    if (isset($wildcards_from_url[$id])) {
        return $wildcards_from_url[$id];
    }

    if (!isset($args_raw)) {
        global $args_raw;
    }

    $args = explode("/", $args_raw);
    if (!isset($id)) {
        return $args;
    } else {
        $id--;
        if (isset($args[$id])) {
            return $args[$id];
        }
    }
}

function l($name, $link, $attributes = array(), $relative = false)
{
    if (!$relative) {
        $link = BASE_URL . $link;
    }
    $attributes_string = " ";
    foreach ($attributes as $attribute => $value) {
        $attributes_string .= $attribute . "='" . $value . "' ";
    }
    return "<a" . $attributes_string . "href='" . $link . "'>" . $name . "</a>";
}

function config($name, $default = null)
{
    global $config;

    return isset($config[$name]) ? $config[$name] : $default;
}

function set_message($message = null, $type = 'default')
{
    if ($message) {
        if (!isset($_SESSION['messages'])) {
            $_SESSION['messages'] = array();
        }
        if (!isset($_SESSION['messages'][$type])) {
            $_SESSION['messages'][$type] = array();
        }

        $_SESSION['messages'][$type][] = $message;
    }
    return isset($_SESSION['messages']) ? $_SESSION['messages'] : null;
}

function get_messages($type = null, $clear_queue = true)
{
    if ($messages = set_message()) {

        if ($type) {
            if ($clear_queue) {
                unset($_SESSION['messages'][$type]);
            }
            if (isset($messages[$type])) {
                return array($type => $messages[$type]);
            }
        } else {
            if ($clear_queue) {
                unset($_SESSION['messages']);
            }
            return $messages;
        }
    }
    return array();
}
