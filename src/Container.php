<?php

namespace Leap;


use Pimple\Container as PimpleContainer;
use Psr\Container\ContainerInterface;

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

    public function get($id)
    {
        if (!$this->has($id)) {
            throw new NotFoundException(sprintf('Identifier "%s" is not defined.', $id));
        }
        return $this[$id];
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
        return isset($this[$id]);
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
