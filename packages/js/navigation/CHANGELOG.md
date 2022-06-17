## [8.0.0](https://www.npmjs.com/package/@woocommerce/navigation/v/8.0.0) - 2022-06-15

-   Minor - Add Jetpack Changelogger
-   Patch - Standardize lint scripts: add lint:fix
-   Patch - Update dependency history to ^5.3.0
-   Major [ **BREAKING CHANGE** ] - Upgraded react-router-dom to v6, which itself causes breaking changes. This upgrade will require consumers to also upgrade their react-router-dom to v6. #33156
-   Minor - Update dependency `@wordpress/hooks` to ^3.5.0
-   Minor - Added Typescript type declarations. #32615
-   Minor - Update dependency `history` to ^5.3.0

    BREAKING CHANGE:

    -   the returned object from getHistory() has methods changed: goBack() -> back() and goForward() -> forward()
    -   the listen() method from the returned object of getHistory() now takes a listener with an object parameter, ({location, action}) instead of (location, action)
    -   location.pathname is now validated and makes a warning if it is not a string

---

[See legacy changelogs for previous versions](https://github.com/woocommerce/woocommerce/blob/68581955106947918d2b17607a01bdfdf22288a9/packages/js/navigation/CHANGELOG.md).
