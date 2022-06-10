# WooCommerce Development Setup with WP-ENV

Docker development setup for WooCommerce with WP-ENV.

## Prerequisites

Please install WP-ENV before getting started. You can find more about WP-ENV on [here](https://github.com/WordPress/gutenberg/tree/master/packages/env).

The following command installs WP-ENV globally.

`npm -g i @wordpress/env`

If you don't already have [pnpm](https://pnpm.io/installation) installed, you can quickly add it using NPM.

`npm install -g pnpm@^6.24.2`

## Starting WP-ENV

1. Navigate to the root of WooCommerce source code.
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

1. Run `pnpm install` to install npm modules.
2. Run `pnpm exec turbo run build --filter=woocommerce` to build core.

You're now ready to develop!

### Typescript Checking

Typescript is progressively being implemented in this repository, and you might come across some files that are `.ts` or `.tsx`. By default, a VSCode environment will run type checking on such files that are currently open. 

As of now, some parts of the codebase that were imported from the Woocommerce-Admin repository, into the `plugins/woocommerce-admin/client` directory, still fail Typescript checking. This has been scheduled on the team's backlog to be fixed.

In order to run type checking across the entire repository, you can run this command in your shell, from the root of this repository:

```sh
pnpm tsc -b tsconfig.base.json
```

For better developer experience, the folder `.vscode/tasks.json` has two VSCode tasks to run these commands automatically as well as to parse the output and highlight the errors in the `Problems` tab and in the file explorer pane. The first task runs it once, the second one runs it in the background upon saving of any modified files. This task is also automatically prompted by VSCode to be run upon opening the folder.


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
		"./plugins/woocommerce",
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

## Accessing MySQL

The MySQL port can change when you restart your container.

You can get the current MySQL port from the output of `wp-env start` command.

1. Open your choice of MySQL tool.
2. Use the following values to access the MySQL container.

| Name     | Value                 |
| -------- | --------------------- |
| Host     | 127.0.0.1             |
| Username | root                  |
| Password | password              |
| Port     | Port from the command |

## HOWTOs

##### How do I ssh into the container?

Run the following command to ssh into the container
`wp-env run wordpress /bin/bash`

You can run a command in the container with the following syntax. You can find more about on the `run` command [here](https://github.com/WordPress/gutenberg/tree/master/packages/env#wp-env-run-container-command)

Syntax:
`wp-env run :container-type :linux-command`
