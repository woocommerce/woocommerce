# 6.0.1

-   Update dependencies.

# 6.0.0

-   Moving `addHistoryListener()` to this package, which supports adding a listener that is executed for history changes.
-   Update dependencies.
-   Add management of persisted queries to navigation.
-   Add page parameter to getNewPath to override default page wc-admin #5821

## Breaking changes

-   Move Lodash to a peer dependency.

# 5.3.0

-   `getQueryExcludedScreens` Return a list of screens that should be excluded from persisted query logic.
-   `getScreenFromPath` Given a path (defaulting to current), return simple screen "name"

# 5.2.0

-   Add slot/fill components WooNavigationItem, NavSlotFillProvider, and useNavSlot.

# 5.1.1

-   Version bump to undeprecate the package.

# 5.1.0

-   Support multiple advanced filter instances in getActiveFiltersFromQuery() and getQueryFromActiveFilters().

# 5.0.0

-   `getPersistedQuery` Add a filter for extensions to add a persisted query, `woocommerce_admin_persisted_queries`.

# 4.0.0

## Breaking Changes

-   decouples `wcSettings` from the package (#3294)
-   `getAdminLink` is no longer available from this package. It is exported on the `wcSettings` global via the woo-blocks plugin (v2.5 or WC 3.9) when enqueued via the `wc-settings` handle.

# 3.0.0

-   `getHistory` updated to reflect path parameters in url query.
-   `getNewPath` also updated to reflect path parameters in url query.
-   `stringifyQuery` method is no longer available, instead use `addQueryArgs` from `@wordpress/url` package.
-   Added a new `<Form />` component.
-   Stepper component: Add new `content` and `description` props.
-   Remove `getAdminLink()` and dependency on global settings object.

# 2.1.1

-   Update license to GPL-3.0-or-later

# 2.1.0

-   New method `getSearchWords` that extracts search words given a query object.
-   Bump dependency versions.

# 2.0.0

-   Replace `history` export with `getHistory` (allows for lazy-create of history)

# 1.1.0

-   Rename `getTimeRelatedQuery` to `getPersistedQuery`
