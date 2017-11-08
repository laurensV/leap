<?php
/* Where am I? */
define('ROOT', call_user_func(function () {
    $root = str_replace("\\", "/", dirname(dirname(dirname(__FILE__))));
    $root .= (substr($root, -1) === '/' ? '' : '/');
    return $root;
}));

define('BASE_URL', call_user_func(function () {
    $sub_dir = str_replace("\\", "/", dirname($_SERVER['PHP_SELF']));
    $sub_dir .= (substr($sub_dir, -1) === '/' ? '' : '/');
    return $sub_dir;
}));


define('URL', call_user_func(function () {
    if(!isset($_SERVER['HTTP_HOST'])){
        return "";
    }
    $port = "";
    if(isset($_SERVER['SERVER_PORT'])) {
        $port = ":" . $_SERVER['SERVER_PORT'];
    }
    $http = "http";

    if ($port === ":80") {
        $port = "";
    }

    if (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on") {
        $http = "https";
    }
    return $http . "://" . $_SERVER['HTTP_HOST'] . $port . BASE_URL;
}));

/**
 * @param      $data
 * @param bool $exit
 */
function pre($data, $exit = false)
{
    print '<pre>';
    print_r($data);
    print '</pre>';
    if ($exit) {
        exit;
    }
}

/**
 * @param $search
 * @param $replace
 * @param $subject
 *
 * @return mixed
 */
function strReplaceFirst($search, $replace, $subject)
{
    $pos = strpos($subject, $search);
    if ($pos !== false) {
        $subject = substr_replace($subject, $replace, $pos, strlen($search));
    }
    return $subject;
}

/**
 * Function to retrieve the namespace given the plugin and type
 *
 * @param string $plugin
 * @param string $type
 *
 * @return string
 */
function getNamespace($plugin = "", $type = "")
{
    $namespace = "Leap\\";
    if (!empty($plugin)) {
        if ($plugin != "core" && $plugin != "app") {
            $namespace .= "Plugins\\";
        }
        $namespace .= ucfirst($plugin) . "\\";
        /* add type to namespace unless we are in core */
        if (!empty($type) && $plugin != "core") {
            $namespace .= ucfirst($type) . "s\\";
        }
    }

    return $namespace;
}

/** @var array $wildcards_from_url */
$wildcards_from_url = [];
/**
 * @param null $id
 * @param null $args_raw
 *
 * @return array|null
 */
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
    return null;
}

/**
 * @param       $name
 * @param       $link
 * @param array $attributes
 * @param bool  $relative
 *
 * @return string
 */
function l($name, $link, $attributes = [], $relative = false)
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

///**
// * @param      $name
// * @param null $default
// *
// * @return null
// */
//function config($name, $default = null)
//{
//    global $config;
//    return $config->get($name, $default);
//}

/**
 * @param null   $message
 * @param string $type
 *
 * @return array|null
 */
function set_message($message = null, $type = 'default')
{
    if ($message) {
        if (!isset($_SESSION['messages'])) {
            $_SESSION['messages'] = [];
        }
        if (!isset($_SESSION['messages'][$type])) {
            $_SESSION['messages'][$type] = [];
        }

        $_SESSION['messages'][$type][] = $message;
    }
    return isset($_SESSION['messages']) ? $_SESSION['messages'] : null;
}

/**
 * @param null $type
 * @param bool $clear_queue
 *
 * @return array|null
 */
function get_messages($type = null, $clear_queue = true)
{
    if ($messages = set_message()) {

        if ($type) {
            if ($clear_queue) {
                unset($_SESSION['messages'][$type]);
            }
            if (isset($messages[$type])) {
                return [$type => $messages[$type]];
            }
        } else {
            if ($clear_queue) {
                unset($_SESSION['messages']);
            }
            return $messages;
        }
    }
    return [];
}

/**
 * @param string $value
 * @param string $path
 *
 * @return string
 */
function parsePath(string $value, string $path): string
{
    if (strpos($value, ':')) {
        $parts = explode(':', $value);
        $type  = array_shift($parts);
        $value = implode(':', $parts);

        switch ($type) {
            case 'url':
                /* add base url if file value is not an URL */
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    if ($value[0] === "/") {
                        $value = BASE_URL . substr($value, 1);
                    } else {
                        $value = strReplaceFirst(ROOT, BASE_URL, $path) . $value;
                    }
                }
                break;
            case 'file':
                if ($value[0] === "/") {
                    $value = ROOT . substr($value, 1);
                } else {
                    $value = $path . $value;
                }
                break;
            default:
                break;
        }
    }
    return $value;
}