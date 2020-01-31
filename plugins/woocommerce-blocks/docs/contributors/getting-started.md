# Getting Started

Before you can begin contributing to the Blocks plugin there are several steps and tools required to setup your local development environment.

## Cloning the Git Repository

Before you can start modifying files you'll want to clone this repository locally, either via the command line, or using a Git client such as [GitHub Desktop](https://desktop.github.com/).

To do so from the command line, ensure you have [`git`](https://git-scm.com) installed on your machine, and run the clone command:

```
$ git clone https://github.com/woocommerce/woocommerce-gutenberg-products-block.git
```

## Installing dependencies

To install dependencies, you will need the following tools installed on your machine:

-   [`npm` and `node.js`](https://nodejs.org)
-   [`composer`](https://getcomposer.org)

See [`package.json` `engines`](package.json) for details of required versions.

Once you have `node` and `composer` setup, install the dependencies from the command line:

-   Change directory to your repo folder, e.g. `$ cd woocommerce-gutenberg-products-block`.
-   Install javascript and php dependencies - `$ npm install && composer install`.

## Building the plugin files

NPM is used to trigger builds. Building is required for the plugin to functional.

-   Run `$ npm run build` to build all assets for production.
-   Run `$ npm start` to run the development build and watch for changes.

These scripts compile the code using `webpack` which is one of the installed dependencies from earlier.

You can also run `$ npx webpack` to run the development build and not keep watching for changes.

### Legacy builds

This plugin supports two type of builds:

-   legacy builds (assets have `-legacy` suffix on their file names)
-   main builds (without the `-legacy` prefix)

The legacy builds are loaded in a site environment where the WordPress version doesn't meet minimum requirements for a component used in a set build.

You can read more about legacy builds in the [this doc](./assets/js/legacy/README.md).

## Create a plugin package in ZIP format

Run `$ npm run package-plugin` to trigger install and build, and then create a zip file which you can use to install WooCommerce Blocks in WordPress admin.

## Linting

Run `$ npm run lint` to check code against our linting rules.

This script runs 3 sub-commands: `lint:php`, `lint:css`, `lint:js`. Use these to run linters across the codebase (linters check for valid syntax).

-   `lint:php` runs phpcs via composer, which uses the [phpcs.xml](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/master/phpcs.xml) rule set.
-   `lint:css` runs stylelint over all the scss code in `assets/css`, using the rules in [.stylelintrc.json.](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/master/.stylelintrc.json)
-   `lint:js` runs eslint over all the JavaScript, using the rules in [.eslintrc.js.](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/master/.eslintrc.js)

Note; linters are also ran before commits via Git. If there are any violations, you will not be able to commit your changes until they are fixed, unless you add the `--no-verify` flag to your commit command.

## Running the Blocks plugin

To run the Blocks plugin you'll need a WordPress development environment - e.g. [`VVV`](https://varyingvagrantvagrants.org) or [`docker`](https://www.docker.com).

-   Ensure the repo folder is in the `wp-content/plugins` folder of your WordPress environment.
-   Activate the `WooCommerce Blocks` plugin (should be dev version, e.g. `2.6.0-dev`).
-   Edit a page or post in block editor - you should see WooCommerce blocks in the block inserter!

## Developer Tools (Visual Studio Code)

We recommend configuring your editor to automatically check for syntax and lint errors. This will help you save time as you develop by automatically fixing minor formatting issues.

Here are some directions for setting up Visual Studio Code (most tools are also available for other editors).

### EditorConfig

[EditorConfig](https://editorconfig.org/) defines a standard configuration for setting up your editor, for example using tabs instead of spaces. You should install the [EditorConfig for VS Code extension](https://marketplace.visualstudio.com/items?itemName=editorconfig.editorconfig) and it will automatically configure your editor to match the rules defined in the Blocks plugin repository `.editorconfig` file.

### ESLint

[ESLint](https://eslint.org/) statically analyzes the code to find problems. The lint rules are integrated in the continuous integration process and must pass to be able to commit. You should install the [ESLint Extension](https://marketplace.visualstudio.com/items?itemName=dbaeumer.vscode-eslint) for Visual Studio Code (see [eslint docs](https://eslint.org/docs/user-guide/integrations) for more editor integrations).

With the extension installed, ESLint will use the `.eslintrc.js` file in the root of the Blocks plugin repository for formatting rules. It will highlight issues as you code.

### Prettier

[Prettier](https://prettier.io/) is a tool that allows you to define an opinionated format, and automate fixing the code to match that format. Prettier and ESlint are similar, Prettier is more about formatting and style, while ESlint is for detecting coding errors.

To use Prettier, you should install the [Prettier - Code formatter](https://marketplace.visualstudio.com/items?itemName=esbenp.prettier-vscode) extension in Visual Studio Code. You can then configure it to be the default formatter and to automatically fix issues on save, by adding the following to your settings.

```
"[javascript]": {
  "editor.defaultFormatter": "esbenp.prettier-vscode",
  "editor.formatOnSave": true
},
```

This will use the `.prettierrc.js` file in the root folder of the Blocks plugin repository and the version of Prettier that is installed in the root `node_modules` folder.
