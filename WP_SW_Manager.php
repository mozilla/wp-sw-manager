<?php

namespace Mozilla;

if (!class_exists('WP_SW_Manager')) {
    /**
     * Holds the shared manager for composing the service workers.
     *
     * Service Workers enable web applications to send push notifications, work
     * offline or perform background tasks periodically. Currently the standard only
     * allows **one service worker per scope** making it hard for plugin developers
     * to combine different focused and isolated functionality.
     *
     * The WP_SW_Manager library provides a collaborative way to generate service
     * workers. It is as simple as registering a callback for writing your service
     * worker functionality:
     *
     * ```php
     * WP_SW_Manager::get_manager()->sw()->add_contents(write_sw);
     *
     * function write_sw() {
     *     echo 'console.log("Here is my plugin!")';
     * }
     * ```
     *
     * @version 0.2.0
     * @license GPL
     * @author Salvador de la Puente González <salva@unoyunodiez.com>
     */
    class WP_SW_Manager {
        /**
         * The name of the enqueued script in charge of registering the
         * service workers.
         *
         * In case you have client code depending on the registration of
         * a service worker, use this const as dependency to ensure your
         * script is added **after** the registration script:
         *
         * ```php
         * wp_register_script('my-plugin-script', 'url/to/my/script.js', array(WP_SW_Manager::SW_REGISTRAR_SCRIPT));
         * ```
         *
         * From your JavaScript code, you can access these registrations
         * via `$swRegistrations` variable.
         *
         * @see WP_SW_Manager::get_js_id()
         * @api
         * @var string
         */
        const SW_REGISTRAR_SCRIPT = 'wp-sw-manager-registrar';

        const SW_REGISTRAR_SCRIPT_URL = 'wpswmanager_sw-registrar.js';

        private static $instance;

        /**
         * Obtains the shared manager.
         *
         * @api
         * @returns WP_SW_Manager The shared manager instance.
         */
        public static function get_manager() {
            if (!self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private $dynamic_server;

        private $router;

        private $service_workers;

        private function __construct() {
            $this->dynamic_server = WP_Serve_File::getInstance();
            $this->router = WP_SW_Manager_Router::get_router();
            $this->service_workers = array();
            $this->setup_sw_registrar_script();
        }

        /**
         * Selects the combinator representing the service worker to write into.
         *
         * As only one service worker per scope is currently allowed by the
         * ServiceWorker API, the manager identify the proper service worker
         * based on the scope only.
         *
         * @api
         * @param string $scope The scope used to select the service worker.
         * If omitted, it defaults to the URL where WordPress site is installed.
         * @return WP_SW_Combinator The combinator instance to generate the
         * content of the service worker.
         */
        public function sw($scope='') {
            if (!$scope) { $scope = $this->default_scope(); }
            if (!array_key_exists($scope, $this->service_workers)) {
                $this->add_new_sw($scope);
            }
            return $this->service_workers[$scope];
        }

        /**
         * Obtains the JavaScript ID to select the registration promise in
         * JavaScript client code.
         *
         * When attaching content to a service worker combinator, the worker
         * is automatically [registered](https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerContainer/register)
         * with a client JavaScript script. If you need to access this registration,
         * you can use the `$swRegistrations` object in your client scripts. This
         * object is a map between service workers and registration promises. To index
         * the proper service worker you need a unique key that can be retrieved
         * with this function.
         *
         * @api
         * @param string $scope The scope identifying the service worker.
         * If omitted, it defaults to the URL where WordPress site is installed.
         * @return string A string representing the unique identifier for the
         * service worker registration promise.
         */
        public function sw_js_id($scope='') {
            if (!$scope) { $scope = $this->default_scope(); }
            return $scope;
        }

        private function default_scope() {
            return site_url('/', 'relative');
        }

        private function setup_sw_registrar_script() {
            $this->dynamic_server->add_file(self::SW_REGISTRAR_SCRIPT_URL,  array($this, 'sw_registrar'));
            add_action('init', array($this, 'check_registrations'), 999);
            add_action('wp_enqueue_scripts', array($this, 'enqueue_registrar'));
        }

        public function check_registrations() {
            $last_registrations = get_option('wpswmanager_registrations', array());
            $current_registrations = array_keys($this->service_workers);
            $areEqual = array_count_values($last_registrations) == array_count_values($current_registrations);
            if (!$areEqual) {
                $this->dynamic_server->invalidate_files(array(self::SW_REGISTRAR_SCRIPT_URL));
                update_option('wpswmanager_registrations', $current_registrations);
            }
        }

        public function enqueue_registrar() {
            $url = WP_Serve_File::get_relative_to_wp_root_url(self::SW_REGISTRAR_SCRIPT_URL);
            wp_enqueue_script(self::SW_REGISTRAR_SCRIPT, $url);
        }

        private function add_new_sw($scope) {
            $virtual_url = "wpswmanager/sw/sw@$scope";
            $real_url = $this->router->add_route($virtual_url, array($this, 'write_sw'), $scope);
            $service_worker = new WP_SW_Manager_Combinator($real_url);
            $this->service_workers[$scope] = $service_worker;
        }

        public function sw_registrar() {
            $contents = file_get_contents(__DIR__ . '/lib/js/sw-registrar.js');
            $contents = str_replace('$enabledSw', $this->json_for_sw_registrations(), $contents);
            return array(
                'content' => $contents,
                'contentType' => 'application/javascript'
            );
        }

        public function write_sw($scope) {
            $service_worker = $this->service_workers[$scope];
            header('Content-Type: application/javascript');
            header('Service-Worker-Allowed: ' . $scope);
            include(__DIR__ . '/lib/js/localforage.nopromises.min.js');
            include(__DIR__ . '/lib/js/sw-base.js');
            include(__DIR__ . '/lib/js/sw-start.js');
            $service_worker->write_content();
            $this->end();
        }

        private function end() {
            wp_die();
        }

        private function json_for_sw_registrations() {
            $registrations = array();
            foreach ($this->service_workers as $scope => $service_worker) {
                if ($service_worker->has_content()) {
                    $registrations[] = array(
                        'scope' => $scope,
                        'url' => $service_worker->get_url()
                    );
                }
            }
            return json_encode($registrations);
        }
    }
}
?>
