<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2017 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Leap;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Pimple\Container as PimpleContainer;
use Slim\Exception\ContainerValueNotFoundException;
use Slim\Exception\ContainerException as SlimContainerException;

/**
 * Slim's default DI container is Pimple.
 *
 * Slim\App expects a container that implements Psr\Container\ContainerInterface
 * with these service keys configured and ready for use:
 *
 *  - settings: an array or instance of \ArrayAccess
 *  - environment: an instance of \Slim\Interfaces\Http\EnvironmentInterface
 *  - request: an instance of \Psr\Http\Message\ServerRequestInterface
 *  - response: an instance of \Psr\Http\Message\ResponseInterface
 *  - router: an instance of \Slim\Interfaces\RouterInterface
 *  - foundHandler: an instance of \Slim\Interfaces\InvocationStrategyInterface
 *  - errorHandler: a callable with the signature: function($request, $response, $exception)
 *  - notFoundHandler: a callable with the signature: function($request, $response)
 *  - notAllowedHandler: a callable with the signature: function($request, $response, $allowedHttpMethods)
 *  - callableResolver: an instance of \Slim\Interfaces\CallableResolverInterface
 *
 */
class Container extends PimpleContainer implements ContainerInterface
{

    /**
     * Create new container
     *
     * @param array $values The parameters or objects.
     */
    public function __construct()
    {

    }


    /********************************************************************************
     * Methods to satisfy Psr\Container\ContainerInterface
     *******************************************************************************/

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @throws ContainerValueNotFoundException  No entry was found for this identifier.
     * @throws ContainerException               Error while retrieving the entry.
     *
     * @return mixed Entry.
     */
    public function get($id)
    {
        if (!$this->offsetExists($id)) {
            return null;
        }
        try {
            return $this->offsetGet($id);
        } catch (\InvalidArgumentException $exception) {
            throw $exception;
        }
    }

    /**
     * Tests whether an exception needs to be recast for compliance with Container-Interop.  This will be if the
     * exception was thrown by Pimple.
     *
     * @param \InvalidArgumentException $exception
     *
     * @return bool
     */
    private function exceptionThrownByContainer(\InvalidArgumentException $exception)
    {
        $trace = $exception->getTrace()[0];

        return $trace['class'] === PimpleContainer::class && $trace['function'] === 'offsetGet';
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return boolean
     */
    public function has($id)
    {
        return $this->offsetExists($id);
    }


    /********************************************************************************
     * Magic methods for convenience
     *******************************************************************************/

    public function __get($name)
    {
        return $this->get($name);
    }

    public function __isset($name)
    {
        return $this->has($name);
    }
}
