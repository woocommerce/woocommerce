# Unreleased

-   Fix the batch fetch logic for the options data store. #7587
-   Add backwards compability for old function format. #7688
-   Add console warning for inbox note contents exceeding 320 characters and add dompurify dependency. #7869
-   Fix race condition in data package's options module. #7947

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
