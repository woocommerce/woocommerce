A custom autoloader for Composer
=====================================

This is a custom autoloader generator that uses a classmap to always load the latest version of a class.

The problem this autoloader is trying to solve is conflicts that arise when two or more plugins use the same package, but one of the plugins uses an older version of said package.

This is solved by keeping an in memory map of all the different classes that can be loaded, and updating the map with the path to the latest version of the package for the autoloader to find when we instantiate the class.

It diverges from the default Composer autoloader setup in the following ways:

* It creates `jetpack_autoload_classmap.php` and `jetpack_autoload_filemap.php` files in the `vendor/composer` directory.
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

Your project must use the default composer vendor directory, `vendor`.

After the next update/install, you will have a `vendor/autoload_packages.php` file.
Load the file in your plugin via main plugin file.

In the main plugin you will also need to include the files like this.
```php
require_once . plugin_dir_path( __FILE__ ) . '/vendor/autoload_packages.php';
```

Working with Development Versions of Packages
-----

The autoloader will attempt to use the package with the latest semantic version.

During development, you can force the autoloader to use development package versions by setting the `JETPACK_AUTOLOAD_DEV` constant to true. When `JETPACK_AUTOLOAD_DEV` is true, the autoloader will prefer the following versions over semantic version:
  - `9999999-dev`
  - Versions with a `dev-` prefix.


Autoloader Limitations
-----

Plugin Updates

When moving a package class file, renaming a package class file, or changing a package class namespace, make sure that the class will not be loaded after a plugin update. 

The autoloader builds the in memory classmap as soon as the autoloader is loaded. The package class file paths in the map are not updated after a plugin update. If a plugins's package class files are moved during a plugin update and a moved file is autoloaded after the update, an error will occur.

