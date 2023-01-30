# 2.0.0

-   Update all js packages with minor/patch version changes. #8392

## Breaking changes

-   Update ESLint from v7 to ^8. #8475
-   Update `eslint-plugin-testing-library` from v3 to v5. #8475
    -   `no-unnecessary-act` is now enabled by default.
    -   `no-wait-for-multiple-assertions` is now enabled by default.
-   Update `@wordpress/eslint-plugin` from v8 to v11. #8475
-   Update `@typescript-eslint/parser` from v4 to v5. #8475
-   Drop support for Node v10. Required node version is now ^12.22.0 || ^14.17.0 || >=16.0.0. #8475
-   Update recommended eslint rules for @woocommerce/\* packages, please see `recommended.js` for details.

# 1.2.0

-   Updated `dependency-group` rule to have imports starting with `~/` labeled as local. #6517

# 1.1.0

-   Updated `@wordpress/eslint-plugin` dependency to latest version.
    -   `jsdoc` linting is configured to allow typescript style jsdocs.

# 1.0.0

-   Released package
