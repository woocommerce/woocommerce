# WooCommerce Packages

Currently we have a small set of public-facing packages that can be dowloaded from [npm](https://www.npmjs.com/org/woocommerce) and used in external applications.

-   `@woocommerce/components`: A library of components that can be used to create pages in the WooCommerce dashboard and reports pages.
-   `@woocommerce/csv-export`: A set of functions to convert data into CSV values, and enable a browser download of the CSV data.
-   `@woocommerce/currency`: A class to display and work with currency values.
-   `@woocommerce/date`: A collection of utilities to display and work with date values.
-   `@woocommerce/navigation`: A collection of navigation-related functions for handling query parameter objects, serializing query parameters, updating query parameters, and triggering path changes.
-   `@woocommerce/tracks`: User event tracking utility functions for Automattic based projects.

## Working with existing packages

-   You can make changes to packages files as normal, and running `pnpm start` will compile and watch both app files and packages.
-   :warning: Make sure any dependencies you add to a package are also added to that package's `package.json`, not just the woocommerce-admin package.json
-   :warning: Make sure you're not importing from any woocommerce-admin files outside of the package (you can import from other packages, just use the `import from @woocommerce/package` syntax).
-   Add your change to the CHANGELOG for that package under the next version number, creating one if necessary (we use semantic versioning for packages, [see these guidelines](https://github.com/WordPress/gutenberg/blob/master/CONTRIBUTING.md#maintaining-changelogs)).
-   Don't change the version in `package.json`.
-   Label your PR with the `Packages` label.
-   Once merged, you can wait for the next package release roundup, or you can publish a release now (see below, "Publishing packages").

---

## Creating a new package

Most of this is pulled [from the Gutenberg workflow](https://github.com/WordPress/gutenberg/blob/master/CONTRIBUTING.md#creating-new-package).

To create a new package, add a new folder to `/packages`, containing…

1. `package.json` based on the template:
    ```json
    {
    	"name": "@woocommerce/package-name",
    	"version": "1.0.0-beta.0",
    	"description": "Package description.",
    	"author": "Automattic",
    	"license": "GPL-2.0-or-later",
    	"keywords": [ "wordpress", "woocommerce" ],
    	"homepage": "https://github.com/woocommerce/woocommerce/tree/main/packages/[_YOUR_PACKAGE_]/README.md",
    	"repository": {
    		"type": "git",
    		"url": "https://github.com/woocommerce/woocommerce.git"
    	},
    	"bugs": {
    		"url": "https://github.com/woocommerce/woocommerce/issues"
    	},
    	"main": "build/index.js",
    	"module": "build-module/index.js",
    	"react-native": "src/index",
    	"publishConfig": {
    		"access": "public"
    	}
    }
    ```
2. `.npmrc` file which disables creating `package-lock.json` file for the package:
    ```
    package-lock=false
    ```
3. `README.md` file containing at least:
    - Package name
    - Package description
    - Installation details
    - Usage example
4. A `src` directory for the source of your module, which will be built by default using the `pnpm run build:packages` command. Note that you'll want an `index.js` file that exports the package contents, see other packages for examples.

5. Add the new package name to `packages/dependency-extraction-webpack-plugin/assets/packages.js` so that users of that plugin will also be able to use the new package without enqueuing it.

---

## Publishing packages

-   Run `pnpm run publish-packages:check` to run pnpm publish with the `--dry-run` option
-   Create a PR with a CHANGELOG for each updated package (or try to add to the CHANGELOG with any PR editing `packages/`)
-   Run `pnpm run publish-packages:prod` to publish the package
-   _OR_ Run `pnpm run publish-packages:dev` to publish "next" releases (installed as `pnpm i @woocommerce/package@next`). Only use `:dev` if you have a reason to.
-   Both commands will run `build:packages` before the publishing  task, just to catch any last updates.

### Publishing a single package

Sometimes, its helpful to release a singular package. This can be done directly through pnpm. Be sure versions and builds are correct.

-   Bump the version in the package's package.json as well as its CHANGELOG file.
-   `pnpm install && pnpm run build:packages` to build packages.
-   `cd packages/<package-name>`
-   `pnpm publish`
