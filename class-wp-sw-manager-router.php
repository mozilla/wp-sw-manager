<?php

class WP_SW_Manager_Router {
    const TRIGGER = '_wpswmanager';

    private static $instance;

    public static function get_router() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private $routes;

    private function __construct() {
        $this->routes = array();
        add_action('parse_request', array($this, 'parse_request'));
        add_filter('query_vars', array($this, 'query_vars'));
    }

    public function add_route($desired_url, $callback) {
        $arguments = array_slice(func_get_args(), 2);
        $route = $this->route($desired_url);
        $this->routes[$route] = array($callback, $arguments);
        return $this->route_url($route);
    }

    public function route_url($route_or_desired_url) {
        $route = $this->route($route_or_desired_url);
        return home_url('/', 'relative') . '?' . self::TRIGGER . '=' . urlencode($route);
    }

    public function parse_request($query) {
        $query_vars = $query->query_vars;
        $route_trigger = $this->identify_trigger($query_vars);
        if ($route_trigger) {
            list($handler, $args) = $this->routes[$route_trigger];
            call_user_func_array($handler, $args);
        }
    }

    public function query_vars($query_vars) {
        $query_vars[] = self::TRIGGER;
        return $query_vars;
    }

    private function route($desired_url) {
        $components = parse_url($desired_url);
        $route = $components['path'];
        return $route;
    }

    private function identify_trigger($query_args) {
        $handler = NULL;
        if (array_key_exists(self::TRIGGER, $query_args)) {
            $handler = $query_args[self::TRIGGER];
        }
        return $handler;
    }
}

?>