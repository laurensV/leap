<?php
namespace Leap\Test;

use Leap\Core\Router;

/**
 * Class RouterTest
 */
class RouterTest extends \PHPUnit_Framework_TestCase
{
    private $router;

    protected function setUp()
    {
        if(!defined('ROOT')) {
            define('ROOT', call_user_func(function () {
                $root = str_replace("\\", "/", dirname(dirname(__FILE__)));
                $root .= (substr($root, -1) == '/' ? '' : '/');
                return $root;
            }));
        }
        $this->router = new Router();
    }

    protected function tearDown()
    {
        $this->router = NULL;
    }

    /**
     * @param $uri
     * @param $route
     * @param $expectedPage
     *
     * @dataProvider providerTestRoutePage
     */
    public function testRoutePage($uri, $route, $expectedPage)
    {
        $this->router->addRoute($route['route'], $route['options']);
        $parsedRoute = $this->router->routeUrl($uri);
        $page        = null;
        $pagePath    = null;
        if (isset($parsedRoute['page']['value'])) {
            $page = $parsedRoute['page']['value'];
        }
        if (isset($parsedRoute['page']['path'])) {
            $pagePath = $parsedRoute['page']['path'];
        }
        $this->assertSame($expectedPage['value'], $page);
        $this->assertSame($expectedPage['path'], $pagePath);
    }

    /**
     * @param       $uri
     * @param       $route
     * @param       $expectedPattern
     *
     * @dataProvider providerTestRoutePattern
     */
    public function testRoutePattern($uri, $route, $expectedPattern)
    {
        $this->router->addRoute($route['route'], $route['options']);
        $parsedRoute = $this->router->routeUrl($uri);
        $pattern     = null;
        if (isset($parsedRoute['title'])) {
            $pattern = $parsedRoute['title'];
        }
        $this->assertSame($expectedPattern, $pattern);
    }

    /**
     * @return array
     */
    public function providerTestRoutePage()
    {
        if(!defined('ROOT')) {
            define('ROOT', call_user_func(function () {
                $root = str_replace("\\", "/", dirname(dirname(__FILE__)));
                $root .= (substr($root, -1) == '/' ? '' : '/');
                return $root;
            }));
        }
        return [
            /* path overwriten by leading slash */
            ['test', ["route" => "test", "options" => ["page" => "/site/pages/test.php", "path" => "randompath"]], ["value" => "site/pages/test.php", "path" => ROOT]],
            /* default path is ROOT */
            ['test', ["route" => "test", "options" => ["page" => "site/pages/test.php"]], ["value" => "site/pages/test.php", "path" => ROOT]],
            /* path set with path option */
            ['test', ["route" => "test", "options" => ["page" => "site/pages/test.php", "path" => "randompath"]], ["value" => "site/pages/test.php", "path" => "randompath"]],
            /* page is a wildcard */
            ['test', ["route" => ":wildcard", "options" => ["page" => "site/pages/:wildcard.php"]], ["value" => "site/pages/test.php", "path" => ROOT]],
        ];
    }

    /**
     * @return array
     */
    public function providerTestRoutePattern()
    {
        if(!defined('ROOT')) {
            define('ROOT', call_user_func(function () {
                $root = str_replace("\\", "/", dirname(dirname(__FILE__)));
                $root .= (substr($root, -1) == '/' ? '' : '/');
                return $root;
            }));
        }
        /* TODO: better titles */
        return [
            /* match single route */
            ['test', ["route" => "test", "options" => ["title" => "test"]], "test"],
            /* starting and trailing slash for uri  */
            ['/test/', ["route" => "test", "options" => ["title" => "test"]], "test"],
            /* starting and trailing slash for route */
            ['test', ["route" => "/test/", "options" => ["title" => "/test/"]], "/test/"],
            /* any wildcard */
            ['test', ["route" => "*", "options" => ["title" => "*"]], "*"],
            /* any wildcard, empty uri */
            ['', ["route" => "*", "options" => ["title" => "*"]], "*"],
            /* any wildcard (no match) */
            ['test/test', ["route" => "*", "options" => ["title" => "*"]], null],
            /* any wildcard  + include slashes option */
            ['test/test', ["route" => "*", "options" => ["title" => "*", "include_slash" => true]], "*"],
            /* single character */
            ['t', ["route" => "?", "options" => ["title" => "?"]], "?"],
            /* single character (no match) */
            ['te', ["route" => "?", "options" => ["title" => "?"]], null],
            /* single character, empty uri (no match) */
            ['', ["route" => "?", "options" => ["title" => "?"]], null],
            /* single character with constraint */
            ['a', ["route" => "[ab]", "options" => ["title" => "[ab]"]], "[ab]"],
            /* single character with constraint (no match)*/
            ['c', ["route" => "[ab]", "options" => ["title" => "[ab]"]], null],
            /* single character with constraint, empty uri (no match)*/
            ['', ["route" => "[ab]", "options" => ["title" => "[ab]"]], null],
            /* parameter wildcard */
            ['test', ["route" => ":param", "options" => ["title" => ":param"]], "test"],
            /* parameter wildcard, empty uri (no match) */
            ['', ["route" => ":param", "options" => ["title" => ":param"]], null],
            /* parameter wildcard (no match) */
            ['test/test', ["route" => ":param", "options" => ["title" => ":param"]], null],
            /* parameter wildcard  + include slashes option (no match) */
            ['test/test', ["route" => ":param", "options" => ["title" => ":param", "include_slash" => true]], null],
            /* combined (no match) */
            ['t/t/test/param', ["route" => "[te]/?/test/:test/*", "options" => ["title" => "[t]/?/test/:test"]], null],
            /* combined */
            ['t/t/test/param/test', ["route" => "[te]/?/test/:test/*", "options" => ["title" => "[t]/?/test/:test/*"]], "[t]/?/test/param/*"],
            /* combined (no match) */
            ['t/t/test/param/test/test', ["route" => "[te]/?/test/:test/*", "options" => ["title" => "[t]/?/test/:test/*"]], null],
        ];
    }

}