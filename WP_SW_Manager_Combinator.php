<?php

namespace Mozilla;

/**
 * Aggregates content generators for a service worker.
 */
class WP_SW_Manager_Combinator {
    private $url;

    private $writers = array();

    public function __construct($url) {
        $this->url = $url;
    }

    /**
     * Registers a callback to be called when generating the service worker
     * to write a portion of the service worker functionality.
     *
     * @api
     * @param callable $content_generator A callable object in charge of **write,
     * not return** a portion of a service worker.
     */
    public function add_content($content_generator) {
        $this->writers[] = $content_generator;
    }

    public function write_content() {
        foreach ($this->writers as $content_generator) {
            echo ';';
            call_user_func($content_generator);
        }
    }

    public function get_url() {
        return $this->url;
    }

    public function has_content() {
        return count($this->writers) != 0;
    }
}
?>
