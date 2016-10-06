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
        define('ROOT', call_user_func(function () {
            $root = str_replace("\\", "/", dirname(dirname(__FILE__)));
            $root .= (substr($root, -1) == '/' ? '' : '/');
            return $root;
        }));

        $this->router = new Router();
    }
    protected function tearDown()
    {
        $this->router = NULL;
    }

    /**
     * @param $route
     * @param $page
     * @param $expectedPage
     *
     * @dataProvider providerTestRoutePage
     */
    public function testRoutePage($route, $page, $expectedPage)
    {
        $this->router->addRoute($route, ["page" => $page]);
        $parsedRoute = $this->router->routeUrl($route);
        $this->assertSame($expectedPage['value'], $parsedRoute['page']['value']);
        $this->assertSame($expectedPage['path'], $parsedRoute['page']['path']);
    }

    /**
     * @return array
     */
    public function providerTestRoutePage()
    {
        return array(
            array('test', '/site/pages/test.php', ["value" => "site/pages/test.php", "path" => "C:/wamp64/www/leap/"])
        );
    }
}