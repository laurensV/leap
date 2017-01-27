<?php
namespace Leap\Core;

/**
 * Class Route
 *
 * @package Leap\Core
 */
class Route
{
    public $base_path;
    public $action;
    public $callback;
    public $template;
    public $page;
    public $stylesheets;
    public $scripts;
    public $title;
    public $routeFound = false;

    public $mathedRoutes = [];
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
        $this->defaultValues['base_path']   = null;
        $this->defaultValues['action']      = null;
        $this->defaultValues['callback']  = ['class' => Controller::class];
        $this->defaultValues['template']    = ['path' => ROOT . 'app/templates/', 'value' => "default_template.php"];
        $this->defaultValues['page']        = [];
        $this->defaultValues['stylesheets'] = [];
        $this->defaultValues['scripts']     = [];
        $this->defaultValues['title']       = null;

        $this->defaultRouteValues();
    }

    /**
     * Set default values for the route
     *
     * @param array $properties
     */
    public function defaultRouteValues($properties = null): void
    {
        if (isset($properties) && !in_array("all", $properties)) {
            /* set array of properties to their default values */
            foreach ($properties as $property) {
                if (isset($defaultValues[$property])) {
                    $this->{$property} = $defaultValues[$property];
                }
            }
        } else {
            /* set all properties to their default values */
            foreach ($this->defaultValues as $property => $value) {
                $this->{$property} = $value;
            }
        }
    }
}
