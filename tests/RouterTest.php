<?php
namespace Leap\Test;

use Leap\Route;
use Leap\Router;

/**
 * Class RouterTest
 */
class RouterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Leap\Router
     */
    private $router;

    /**
     *
     */
    protected function setUp()
    {
        if (!defined('ROOT')) {
            require dirname(__FILE__) . '/../core/include/helpers.php';
        }
        $this->router = new Router();
    }

    /**
     *
     */
    protected function tearDown()
    {
        $this->router = NULL;
    }

    /**
     * @param $uri
     * @param $route
     * @param $expectedValues
     */
    private function routeMatching($uri, $expectedValues, $method = 'GET')
    {
        $parsedRoute = $this->router->routeUri($uri, $method);

        $this->expectedValuesAssertions($expectedValues, $parsedRoute);
    }

    private function addRoute($route)
    {
        $this->router->add($route['pattern'], null, $route['options']);
    }

    private function addGroup($prefix, $route)
    {
        $this->router->addGroup($prefix, function ($r) use ($route) {
            $r->add($route['pattern'], null, $route['options']);
        });
    }

    private function addTwoGroups($prefix1, $prefix2, $route)
    {
        $this->router->addGroup($prefix1, function ($r) use ($route, $prefix2) {
            $r->addGroup($prefix2, function ($r) use ($route) {
                $r->add($route['pattern'], null, $route['options']);
            });
        });
    }

    /**
     * @param $expectedValues
     * @param $parsedRoute
     */
    private function expectedValuesAssertions($expectedValues, $parsedRoute)
    {
        foreach ($expectedValues as $type => $expectedValue) {
            switch ($type) {
                case 'status':
                case 'matchedPatterns':
                    $this->assertSame($expectedValue, $parsedRoute->$type);
                    break;
                case 'body':
                    $body = call_user_func($parsedRoute->callback, $parsedRoute->parameters);
                    $this->assertSame($expectedValue, $body);
                    break;
            }
        }
    }

    /* Default route */
    public function testDefaultRoute()
    {
        $route = [
            "pattern" => "/",
            "options" => [
                "callback" => function () {
                    return 'OK';
                }
            ]
        ];
        $this->addRoute($route);

        /* Matching tests */
        $expectedValues = [
            "status"          => Route::FOUND,
            "matchedPatterns" => [$route['pattern']],
            'body'            => 'OK'
        ];
        $this->routeMatching('/', $expectedValues);

        /* Non-matching tests */
        $expectedValues = [
            "status" => Route::NOT_FOUND
        ];
        $this->routeMatching('/path', $expectedValues);
    }

    /* Simple path */
    public function testSimplePath()
    {
        $route = [
            "pattern" => "/path",
            "options" => [
                "callback" => function () {
                    return 'OK';
                }
            ]
        ];
        $this->addRoute($route);

        /* Matching tests */
        $expectedValues = [
            "status"          => Route::FOUND,
            "matchedPatterns" => [$route['pattern']],
            'body'            => 'OK'
        ];
        $this->routeMatching('/path', $expectedValues);

        /* Non-matching tests */
        $expectedValues = [
            "status" => Route::NOT_FOUND
        ];
        $this->routeMatching('/path2', $expectedValues);
    }

    /* GET method */
    public function testMethod()
    {
        $route = [
            "pattern" => "GET /path",
            "options" => [
                "callback" => function () {
                    return 'OK';
                }
            ]
        ];
        $this->addRoute($route);

        /* Matching tests */
        $expectedValues = [
            "status"          => Route::FOUND,
            "matchedPatterns" => ["/path"],
            'body'            => 'OK'
        ];
        $this->routeMatching('/path', $expectedValues, 'GET');

        /* Non-matching tests */
        $expectedValues = [
            "status" => Route::METHOD_NOT_ALLOWED
        ];
        $this->routeMatching('/path', $expectedValues, 'POST');
    }

    /* GET or POST method */
    public function testMultipleMethods()
    {
        $route = [
            "pattern" => "GET|POST /path",
            "options" => [
                "callback" => function () {
                    return 'OK';
                }
            ]
        ];
        $this->addRoute($route);

        /* Matching tests */
        $expectedValues = [
            "status"          => Route::FOUND,
            "matchedPatterns" => ['/path'],
            'body'            => 'OK'
        ];
        $this->routeMatching('/path', $expectedValues, 'POST');
        $this->routeMatching('/path', $expectedValues, 'GET');

        /* Non-matching tests */
        $expectedValues = [
            "status" => Route::METHOD_NOT_ALLOWED
        ];
        $this->routeMatching('/path', $expectedValues, 'PUT');
    }

    /* Simple Regex matching */
    public function testSimpleRegex()
    {
        $route = [
            "pattern" => "/id/[0-9]+",
            "options" => [
                "callback" => function () {
                    return 'OK';
                }
            ]
        ];
        $this->addRoute($route);

        /* Matching tests */
        $expectedValues = [
            "status"          => Route::FOUND,
            "matchedPatterns" => [$route['pattern']],
            'body'            => 'OK'
        ];
        $this->routeMatching('/id/123', $expectedValues);

        /* Non-matching tests */
        $expectedValues = [
            "status" => Route::NOT_FOUND
        ];
        $this->routeMatching('/id/123w', $expectedValues);
    }

    /* Complex Regex matching */
    public function testComplexRegex()
    {
        $route = [
            "pattern" => "/i[cd]{1}(s)a+/\w/[1-5]{3}/a(b+)c?d+/",
            "options" => [
                "callback" => function () {
                    return 'OK';
                }
            ]
        ];
        $this->addRoute($route);

        /* Matching tests */
        $expectedValues = [
            "status"          => Route::FOUND,
            "matchedPatterns" => [$route['pattern']],
            'body'            => 'OK'
        ];
        $this->routeMatching('/idsaaaa/u/123/acxd', $expectedValues);
        $this->routeMatching('/ica/a/345/acxddd', $expectedValues);

        /* Non-matching tests */
        $expectedValues = [
            "status" => Route::NOT_FOUND
        ];
        $this->routeMatching('/idsaaaa/u/123/a', $expectedValues);
        $this->routeMatching('/idsaaaa/u/123/acd', $expectedValues);
        $this->routeMatching('/idsaaaa/u/12/acd', $expectedValues);
        $this->routeMatching('/idsaaaa/#/123/acxd', $expectedValues);
        $this->routeMatching('/iesaaaa/u/123/acxd', $expectedValues);
        $this->routeMatching('/ids/u/123/acxd', $expectedValues);

    }

    /* Named Parameter */
    public function testNamedParameter()
    {
        $route = [
            "pattern" => "/{ok}",
            "options" => [
                "callback" => function ($parameters) {
                    return $parameters['ok'];
                }
            ]
        ];
        $this->addRoute($route);

        /* Matching tests */
        $expectedValues = [
            "status"          => Route::FOUND,
            "matchedPatterns" => [$route['pattern']],
            'body'            => 'ok'
        ];
        $this->routeMatching('/ok', $expectedValues);

        /* Non-matching tests */
        $expectedValues = [
            "status" => Route::NOT_FOUND
        ];
        $this->routeMatching('/', $expectedValues);
    }

    /* Parameter with Regex */
    public function testRegexParameter()
    {
        $route = [
            "pattern" => "/{ok:[0-9]{3}}",
            "options" => [
                "callback" => function ($parameters) {
                    return $parameters['ok'];
                }
            ]
        ];
        $this->addRoute($route);

        /* Matching tests */
        $expectedValues = [
            "status"          => Route::FOUND,
            "matchedPatterns" => [$route['pattern']],
            'body'            => '123'
        ];
        $this->routeMatching('/123', $expectedValues);

        /* Non-matching tests */
        $expectedValues = [
            "status" => Route::NOT_FOUND
        ];
        $this->routeMatching('/1234', $expectedValues);
    }

    /* Optional Parameters */
    public function testOptionalParameters()
    {
        $route = [
            "pattern" => "/date/{year}(/{month}(/{day}))",
            "options" => [
                "callback" => function ($parameters) {
                    return "{$parameters['year']},{$parameters['month']},{$parameters['day']}";
                }
            ]
        ];
        $this->addRoute($route);

        /* Matching tests */
        $expectedValues = [
            "status"          => Route::FOUND,
            "matchedPatterns" => [$route['pattern']],
            'body'            => '1993,10,'
        ];
        $this->routeMatching('/date/1993/10', $expectedValues);

        /* Non-matching tests */
        $expectedValues = [
            "status" => Route::NOT_FOUND
        ];
        $this->routeMatching('/date/', $expectedValues);
    }

    /* Optional Parameters with Regex */
    public function testRegexOptionalParameters()
    {
        $route = [
            "pattern" => "/date(/{year}(/{month}(/{day:[0-9]{2}})))",
            "options" => [
                "callback" => function ($parameters) {
                    return "{$parameters['year']},{$parameters['month']},{$parameters['day']}";
                }
            ]
        ];
        $this->addRoute($route);

        /* Matching tests */
        $expectedValues = [
            "status"          => Route::FOUND,
            "matchedPatterns" => [$route['pattern']],
            'body'            => '1993,10,07'
        ];
        $this->routeMatching('/date/1993/10/07', $expectedValues);
        $expectedValues = [
            "status"          => Route::FOUND,
            "matchedPatterns" => [$route['pattern']],
            'body'            => '1993,10,'
        ];
        $this->routeMatching('/date/1993/10/', $expectedValues);

        /* Non-matching tests */
        $expectedValues = [
            "status" => Route::NOT_FOUND
        ];
        $this->routeMatching('/date/1993/10/7', $expectedValues);
    }

    /* Wildcards */
    public function testWildcards()
    {
        $route = [
            "pattern" => "/wildcard/*/one/?/any(**)",
            "options" => [
                "callback" => function () {
                    return "OK";
                }
            ]
        ];
        $this->addRoute($route);

        /* Matching tests */
        $expectedValues = [
            "status"          => Route::FOUND,
            "matchedPatterns" => [$route['pattern']],
            'body'            => 'OK'
        ];
        $this->routeMatching('/wildcard/test1#/one/1/anythingincluding/slashes', $expectedValues);
        $this->routeMatching('/wildcard/test2#/one/2/any', $expectedValues);

        /* Non-matching tests */
        $expectedValues = [
            "status" => Route::NOT_FOUND
        ];
        $this->routeMatching('/wildcard/test1#/one/12/anythingincluding/slashes', $expectedValues);
        $this->routeMatching('/wildcard/test2#/noslashesinstar/one/12/anythingincluding/slashes', $expectedValues);
        $this->routeMatching('/wildcard/one/2/any', $expectedValues);
    }

    /* Group prefix */
    public function testGroup()
    {
        $route = [
            "pattern" => "/path",
            "options" => [
                "callback" => function () {
                    return "OK";
                }
            ]
        ];
        $this->addGroup('/group', $route);

        /* Matching tests */
        $expectedValues = [
            "status"          => Route::FOUND,
            "matchedPatterns" => ['/group/path'],
            'body'            => 'OK'
        ];
        $this->routeMatching('/group/path', $expectedValues);

        /* Non-matching tests */
        $expectedValues = [
            "status" => Route::NOT_FOUND
        ];
        $this->routeMatching('/group', $expectedValues);
        $this->routeMatching('/path', $expectedValues);
    }

    /* Group with Method */
    public function testGroupMethod()
    {
        $route = [
            "pattern" => "/path",
            "options" => [
                "callback" => function () {
                    return "OK";
                }
            ]
        ];
        $this->addGroup('POST /group', $route);

        /* Matching tests */
        $expectedValues = [
            "status"          => Route::FOUND,
            "matchedPatterns" => ['/group/path'],
            'body'            => 'OK'
        ];
        $this->routeMatching('/group/path', $expectedValues, 'POST');

        /* Non-matching tests */
        $expectedValues = [
            "status" => Route::METHOD_NOT_ALLOWED
        ];
        $this->routeMatching('/group/path', $expectedValues, 'GET');
    }

    /* Group with Method Overriden */
    public function testGroupMethodOverriden()
    {
        $route = [
            "pattern" => "GET /path",
            "options" => [
                "callback" => function () {
                    return "OK";
                }
            ]
        ];
        $this->addGroup('POST /group', $route);

        /* Matching tests */
        $expectedValues = [
            "status"          => Route::FOUND,
            "matchedPatterns" => ['/group/path'],
            'body'            => 'OK'
        ];
        $this->routeMatching('/group/path', $expectedValues, 'GET');

        /* Non-matching tests */
        $expectedValues = [
            "status" => Route::METHOD_NOT_ALLOWED
        ];
        $this->routeMatching('/group/path', $expectedValues, 'POST');
    }

    /* Nested Group */
    public function testNestedGroups()
    {
        $route = [
            "pattern" => "/path",
            "options" => [
                "callback" => function () {
                    return "OK";
                }
            ]
        ];
        $this->addTwoGroups('/group1', '/group2', $route);

        /* Matching tests */
        $expectedValues = [
            "status"          => Route::FOUND,
            "matchedPatterns" => ['/group1/group2/path'],
            'body'            => 'OK'
        ];
        $this->routeMatching('/group1/group2/path', $expectedValues);

        /* Non-matching tests */
        $expectedValues = [
            "status" => Route::NOT_FOUND
        ];
        $this->routeMatching('/group1/group2', $expectedValues);
        $this->routeMatching('/group1', $expectedValues);
        $this->routeMatching('/group1/path', $expectedValues);
        $this->routeMatching('/group2/path', $expectedValues);
    }
}