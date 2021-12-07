# WooCommerce Admin Docker Setup with WP-ENV

Docker development setup for WooCommerce Admin with WP-ENV.

## Prerequisites

Please install WP-ENV before getting started. You can find more about WP-ENV on [here](https://github.com/WordPress/gutenberg/tree/master/packages/env).

The following command installs WP-ENV globally.

`npm -g i @wordpress/env`


## Starting WP-ENV

1. Navigate to the root of WooCommerce Admin source code.
2. Start the docker container by running `wp-env start`

You should see the following output

```
WordPress development site started at http://localhost:8888/
WordPress test site started at http://localhost:8889/
MySQL is listening on port 55003
```

The port # might be different depending on your `.wp-env.override.json` configuration.

## Getting Started with Developing

Once you have WP-ENV container up, we need to run a few commands to start developing.

1. Run `npm install` to install npm modules.
2. Run `npm run dev`
3. Run `composer install` to install PHP dependencies.

If you don't have Composer available locally, run the following command. It runs the command in WP-ENV container.

`wp-env run composer composer install`


You might also want to run `npm start` to watch your CSS and JS changes if you are working on the frontend.

You're now ready to develop!

## Using Xdebug

Please refer to [WP-ENV official README](https://github.com/WordPress/gutenberg/tree/master/packages/env#using-xdebug) section for setting up Xdebug.

## Overriding the Default Configuration

The default configuration comes with PHP 7.4, WooCommerce 5.0, and a few WordPress config values.

You can create `.wp-env.override.json` file and override the default configuration values.

You can find more about `.wp-env.override.json` configuration [here](https://github.com/WordPress/gutenberg/tree/master/packages/env#wp-envoverridejson).

**Example: Overriding PHP version to 8.0**

Create `.wp-env.override.json` in the root directory with the following content.


```json
{
	"phpVersion": "8.0"
}
```

**Exampe: Adding a locally installed plugin**

Method 1 - Adding to the `plugins` array

Open the default `.wp-env.json` and copy `plugins` array and paste it into the `.wp-env.override.json` and add your locally installed plugin. Copying the default `plugins` is needed as WP-ENV does not merge the values of the `plugins`.

```json
{
	"plugins": [
        ".",
        "../woocommerce",
        "https://downloads.wordpress.org/plugin/wp-crontrol.1.10.0.zip"
    ]
}

```

Method 2 - Adding to the `mappings`

This method is simpler, but the plugin does not get activated on startup. You need to manually activate it yourself on the first startup.

```json
{
	"mappings": {
    	"wp-content/plugins/wp-crontrol": "../woocommerce"
    }
}
```

## Accessing Mysql

The Mysql port can change when you restart your container.

You can get the current Mysql port with `npm run wp-env-mysql-port` command.

1. Open your choice of Mysql tool.
2. Use the following values to access the Mysql container.

| Name | Value |
|--------|-----|
|  Host  | 127.0.0.1 |
| Username | root |
| Password | password |
| Port | Port from the command |

## HOWTOs

##### How do I ssh into the container?

Run the following command to ssh into the container
`wp-env run wordpress /bin/bash`

You can run a command in the container with the following syntax. You can find more about on the `run` command [here](https://github.com/WordPress/gutenberg/tree/master/packages/env#wp-env-run-container-command)

Syntax:
`wp-env run :container-type :linux-command`



