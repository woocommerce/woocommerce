# Unreleased

## Breaking change

-   Remove `PaymentMethodsState` type. Use `Plugin` instead. #32683

# 3.1.0

-   Add "moment" to peerDependencies. #8349
-   Update all js packages with minor/patch version changes. #8392
-   Fix type errors. #8392

# 3.0.0

## Breaking changes

-   Update dependencies to support react 17. #8305
-   Drop support for IE11. #8305

# 2.0.0

## Breaking changes

-   Fix the batch fetch logic for the options data store. #7587
-   Add backwards compability for old function format. #7688
-   Add console warning for inbox note contents exceeding 320 characters and add dompurify dependency. #7869
-   Fix race condition in data package's options module. #7947
-   Remove dev dependency `@woocommerce/wc-admin-settings`. #8057
-   Update plugins data store actions #8042
-   Add `defaultDateRange` parameter to `getRequestQuery` #8189
-   Change `getLocale` selector parameter from country to id #8123
-   Add countries data store #8119
-   Rename `is_visible` to `can_view` #7918
-   Replace old task list option calls with data store selectors #7820
-   Remove task status endpoint #7841
-   Add country validation to subscription inclusion #7777
-   Move some of the deprecated tasks #7761
-   Change how `getTasksFromDeprecatedFilter` works #7749
-   Add query args for removeAllNotes #7743
-   Removed some attributes from `TasksStatusState` #7736
-   Add an endpoint and method for actioning tasks #7746
-   Add show/hide behavior for task list API #7733
-   Add optimistic task completion and cache invalidation #7722
-   Add extended task list support to the new REST api task lists #7730
-   Migrate tasks to task API #7699
-   Revert `searchItemsByString` to use selector param again #7682
-   Add hide task list endpoint and data actions #7578
-   Add task list components to consume task list REST API #7556
-   Add Newsletter Signup to onboarding data store #7601
-   Add task selectors and actions to onboarding data store #7545
-   Add super admin check to preloaded user data #7489
-   Add free extensions data store #7420
-   Add `isPluginsRequesting` selector #7383
-   Add options and change selector param for `searchItemsByString`. #7385
-   Change select to selector param for `searchItemsByString`. #7355
-   Change item data store's `getItems` selector #7395

# 1.4.0

-   Fix commonjs module build, allow package to be built in isolation. #7286

# 1.3.2

-   Add fallback for the select/dispatch data-controls for older WP versions. #7204
-   Fix error parsing of plugin data package. #7164
-   Update dependencies

# 1.3.1

-   Fix, state md5 as npm dependency. #7087

# 1.3.0

-   Fix parsing bad JSON string data in useUserPreferences hook. #6819
-   Removed allowed keys list for adding woocommerce_meta data. #6889

# 1.2.0

-   Add management of persisted queries to navigation data store.
-   Add TypeScript support.
-   Generate MD5 hashes without bundling all of Node crypto. #5768
-   Fix HoC-wrapped components from being named "Anonymous". #5898
-   Reduce Unnecessary Re-renders in Revenue Report. #5986
-   Add useUser hook. #6365
-   Fix bug in useSettings that causes an infinite loop. #6540

# 1.1.1

-   Remove usage of wc-admin alias `@woocommerce/wc-admin-settings`.

# 1.1.0

-   Add export, import, items, notes, reports, and reviews data stores.

# 1.0.0

-   Released package
