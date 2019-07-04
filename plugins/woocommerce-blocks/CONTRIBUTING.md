# Contributing

Thanks for your interest in contributing to WooCommerce Blocks! Below are some developer docs for working with the project.

To get started run `npm install` and `composer install` in the plugin directory to install all required dependencies.

## Building assets

We have a set of scripts in npm to handle repeated developer tasks.

### `$ npm run build` & `$ npm start`

These scripts compile the code using `webpack`. 

- Run `$ npm run build` to build all assets for production.
- Run `$ npm start` to build assets and watch for changes (useful for development). 

You can also run `$ npx webpack` to run the development build and not keep watching.

### `$ npm run lint`

This script runs 3 sub-commands: `lint:php`, `lint:css`, `lint:js`. Use these to run linters across the codebase (linters check for valid syntax).

- `lint:php` runs phpcs via composer, which uses the [phpcs.xml](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/master/phpcs.xml) ruleset.
- `lint:css` runs stylelint over all the scss code in `assets/css`, using the rules in [.stylelintrc.json.](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/master/.stylelintrc.json)
- `lint:js` runs eslint over all the JavaScript, using the rules in [.eslintrc.js.](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/master/.eslintrc.js)

### `$ npm run test`

The test scripts use [wp-scripts](https://github.com/WordPress/gutenberg/tree/master/packages/scripts) to run jest for component and unit testing.

- `test:update` updates the snapshot tests for components, used if you change a component that has tests attached.
- Use `test:watch` to keep watch of files and automatically re-run tests.

### `$ npm run prepack`

This script is run automatically when `$ npm pack` or `$ npm publish` is run. It installs packages, runs the linters, runs the tests, and then builds assets from source once more.

## Tagging new releases on GitHub

If you have commit access, tagging a new version on GitHub can be done by running the following script:

```shell
$ npm run deploy
```

This will trigger a build and then run the release script (found in `/bin/github-deploy.sh`). This tags a new version and creates the GitHub release from your current branch.

__Important:__ Before running the deploy script ensure you have committed all changes to GitHub and that you have the correct branch checked out that you wish to release.

If you want to add additional details or a binary file to the release after deployment, [you can edit the release here](https://github.com/woocommerce/woocommerce-gutenberg-products-block/releases).

## Pushing new releases to WordPress.org

If you have SVN commit access to the WordPress.org plugin repository you can run the following script to prepare a new version:

```shell
$ npm run release
```

This will ask for a tagged version number, check it out from GitHub, check out the SVN repository, and prepare all files. It will give you a command when it's finished to do the actual commit; you have a chance to test/check the files before pushing to WordPress.org.

__Important:__ Before running the release script ensure you have already pushed a new release to GitHub.

## Publishing `@woocommerce/block-library`

We publish the blocks to npm at [@woocommerce/block-library,](https://www.npmjs.com/package/@woocommerce/block-library) and then import them into WooCommerce core via [a grunt script.](https://github.com/woocommerce/woocommerce/blob/741bd5ba6d193e21893ef3af3d4f3f030a79c099/Gruntfile.js#L347) 

To release a new version, there are 3 basic steps. Prepare and test the release, publish the version, then import into WooCommerce core.

### 1. Prepare and test the release

- Manually change the versions in `package.json` and `woocommerce-gutenberg-products-block.php` (once in the plugin header, and `WGPB_VERSION`). [See an example PR with these changes.](https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/478/commits/725c43fe0362044c953728cb3391095a43e66bb5)
- Run `npm pack` to prep a `.tgz` file.
- Optionally test the package by uploading this onto a test site.

### 2. Publish this version

- Run `npm publish --access public`, which will push the version up to npm.
- Check [@woocommerce/block-library](https://www.npmjs.com/package/@woocommerce/block-library) for your update

### 3. Pull into WooCommerce core

- Manually update the @woocommerce/block-library version in `package.json`
- In the woocommerce folder, run `npm install` to download the version you just specified
- Run the copy command, `npx grunt blocks`, to copy the build files from node_modules into their destinations in WC core
- Check that the changes imported look correct
- Make a PR on WooCommerce to import the new version

## How to test `@woocommerce/block-library` without publishing to npm

If you need to test how something works once built into WooCommerce core, you can `pack` a .tgz file and tell WooCommerce to use this local .tgz file instead of the version on npmjs.com.

- Run `npm pack` to create the .tgz file
- Move the file into the `woocommerce` plugin directory, optionally renaming it to something unique
- Update `/woocommerce/package.json`:

```json
"dependencies": {
  "@woocommerce/block-library": "file://./woocommerce-block-library-2.2.0-dev.tgz"
},
```

- Now you can run `npm install` in woocommerce to install from this local file
- Run `npx grunt blocks` to build just the blocks code, or `npx grunt` to run the full build process
- Test whatever you were testing 🎉

If you're testing something multiple times, note that the **.tgz is cached,** so if you're doing this more than once, the tgz name needs to be different to break that cache.
