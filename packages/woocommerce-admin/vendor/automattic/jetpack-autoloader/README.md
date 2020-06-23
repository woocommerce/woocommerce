A custom autoloader for Composer
=====================================

This is a custom autoloader generator that uses a classmap to always load the latest version of a class.

The problem this autoloader is trying to solve is conflicts that arise when two or more plugins use the same package, but one of the plugins uses an older version of said package.

This is solved by keeping an in memory map of all the different classes that can be loaded, and updating the map with the path to the latest version of the package for the autoloader to find when we instantiate the class.
This only works if we instantiate the class after all the plugins have loaded. That is why the class produces an error if the plugin calls a class but has not loaded all the plugins yet.

It diverges from the default Composer autoloader setup in the following ways:

* It creates an `autoload_classmap_package.php` file in the `vendor/composer` directory.
* This file includes the version numbers from each package that is used. 
* The autoloader will only load the latest version of the library no matter what plugin loads the library. 
* Only call the library classes after all the plugins have loaded and the `plugins_loaded` action has fired.


Usage
-----

In your project's `composer.json`, add the following lines:

```json
{
    "require-dev": {
        "automattic/jetpack-autoloader": "^1"
    }
}
```

After the next update/install, you will have a `vendor/autoload_packages.php` file.
Load the file in your plugin via main plugin file.

In the main plugin you will also need to include the files like this.
```php
require_once . plugin_dir_path( __FILE__ ) . '/vendor/autoload_packages.php';
```


Current Limitations
-----

We currently only support packages that autoload via psr-4 definition in their package.
