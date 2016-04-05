# wp-sw-manager
> Service Worker infrastructure for WordPress plugins.

## Motivation

Service Workers enable web applications to send push notifications, work offline or perform background tasks periodically. Currently the standard only allows **one service worker per scope** making it hard for plugin developers to combine different focused and isolated functionality.

The WP_SW_Manager library provides a collaborative way to generate service workers. It is as simple as registering a callback for writing your service worker functionality:

```php
include_once(plugins_url(__FILE__) . /vendor/mozilla/wp-sw-manager);

WP_SW_Manager::get_manager()->sw()->add_content(write_sw);

function write_sw() {
    echo 'console.log("Here is my plugin!")';
}
```

## Installation

Add this entry to your `composer.json` file:

```js
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/mozilla/wp-sw-manager"
    }
],
"require": {
    "mozilla/wp-sw-manager": "dev-master"
}
```

## Usage

First, include the library and get the manager:

```php
include_once(plugins_url(__FILE__) . /vendor/mozilla/wp-sw-manager);
$swmgr = WP_SW_Manager::get_manager();
```

### Adding your functionality

A service worker is identified with the scope at which it will be registered. Select the proper service worker with:

```php
$swmgr->sw('/scope/path');
```

If you omit the scope parameter, it will default to [`home_url('/', 'relative')`](https://developer.wordpress.org/reference/functions/home_url/).

To add your content to the service worker you use:

```php
$swmgr->sw()->add_content(write_sw);

function write_sw() {
    echo 'console.log("Here is my plugin!")';
}
```

You can pass an array instead to deal with class or instance methods:

```php
$swmgr->sw()->add_content(array($this, 'write_sw'));

public function write_sw() {
    echo 'console.log("Here is my plugin!")';
}
```

If you have a file with the contents you want to add, you can include it when generating the code:

```php
$swmgr->sw()->add_content(array($this, 'write_sw'));

public function write_sw() {
    $message = 'Welcome to my plugin!';
    include('path/to/my-sw-functionality.js')
}
```

```js
// path/to/my-sw-functionality.js
console.log('<?php echo $message; ?>');
```

Add your content generators always at load time, **before WordPress `init` action**.

### Writing service workers

When writing your own service worker functionality, the last form from above is preferred to improve maintainability.

It is strongly recommended you enclose your functionality inside an [IIFE](http://benalman.com/news/2010/11/immediately-invoked-function-expression/) and try to not pollute the global namespace. A good template could be:

```js
(function(self) {
    // here goes my functionality
})(self);
```

#### Storage

Since version 0.3.0 the library embeds [localForage JavaScript library](https://github.com/mozilla/localForage) which enables easy access to IndexedDB through service workers. To avoid name collisions you should create your own namespace with:

```js
// contents for your service worker
(function(self, localforage) {
  var store = localforage.createInstance({ name: '__my_wp_plugin' });
})(self, localforage);
```

### Accessing service worker registration

It is possible you want to add some client code dependent on the service worker registration. To do this [register your script](https://developer.wordpress.org/reference/functions/wp_register_script/) indicating a dependency with `WP_SW_Manager::SW_REGISTRAR_SCRIPT`.

```php
wp_register_script('my-plugin-script', '/path/to/my/plugin/script.js', array(WP_SW_Manager::SW_REGISTRAR_SCRIPT));
```

Your script will be added **after** the code to register the service workers.

To access the [registration promise](https://developer.mozilla.org/en-US/docs/Web/API/ServiceWorkerContainer/register), use the `$swRegistration` object. This object contains service worker registrations per unique key. Although this key is currently the scope of the service worker, this could change in the future so you should not rely on this assumption. Instead, you should retrieve the unique key in PHP and pass to the client JavaScript with a [localized script](https://developer.wordpress.org/reference/functions/wp_localize_script/).

```php
wp_register_script('my-plugin-script', '/path/to/my/plugin/script.js', array(WP_SW_Manager::SW_REGISTRAR_SCRIPT));
wp_localize_script('my-plugin-script', 'ServiceWorker', array('key' => WP_SW_Manager::get_js_id()));
wp_enqueue_script('my-plugin-script');
```

And in the client code:

```js
$swRegistrations[ServiceWorker.key]
.then(registration => console.log('Success:', registration))
.catch(error => console.error('Error:', error));
```
