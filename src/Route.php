<?php
namespace Leap;

/**
 * Class Route
 *
 * @package Leap
 */
class Route
{
    const FOUND              = 1;
    const NOT_FOUND          = 0;
    const METHOD_NOT_ALLOWED = -1;

    public $base_path;
    public $callback;
    public $status;
    public $matchedPatterns;
    public $parameters;

    /**
     * @var array
     */
    private $defaultValues = [];

    /**
     * Router constructor.
     */
    public function __construct()
    {
        /* initialize default values once */
        $this->defaultValues['base_path']       = null;
        $this->defaultValues['callback']        = ['class' => Controller::class];
        $this->defaultValues['status']          = self::NOT_FOUND;
        $this->defaultValues['matchedPatterns'] = [];
        $this->defaultValues['parameters']      = [];

        $this->defaultRouteValues();
    }

    /**
     * Set default values for the route
     *
     * @param array $properties
     */
    public function defaultRouteValues($properties = null): void
    {
        if (!isset($properties) || $properties === true || $properties === 'all') {
            /* set all properties to their default values */
            foreach ($this->defaultValues as $property => $value) {
                $this->{$property} = $value;
            }
        } else if (is_array($properties)) {
            /* set array of properties to their default values */
            foreach ($properties as $property) {
                $this->{$property} = $this->defaultValues[$property] ?? null;
            }
        }
    }
}
