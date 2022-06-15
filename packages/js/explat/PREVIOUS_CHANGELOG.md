# 2.1.0

-   Add missing dependencies. #8349
-   Update all js packages with minor/patch version changes. #8392
-   Add `@wordpress/api-fetch` as dependencies. #8428
-   Export `*WithAuth` methods to authenticate WPCOM users. #8428

# 2.0.0

-   Make ExPlat request URL args filterable. Added woocommerce_explat_request_args filter #8231

## Breaking changes

-   Update dependencies to support react 17. #8305
-   Drop support for IE11. #8305

# 1.1.4

-   Fix an error when getting woocommerce_default_country value. #7600
-   Attempts to get the woocommerce_default_country value in wcSettings.preloadSettings.general first for the backward compatibility #7600

# 1.1.3

-   Retry fix for missing build-module folder

# 1.1.2

-   Fix missing build-module folder

# 1.1.1

-   Update add woo_country_code param when fetching an assignment #7533

# 1.1.0

-   Fix commonjs module build, allow package to be built in isolation. #7286
-   Fix and refactor explat polling to use setTimeout #7274

# 1.0.1

-   Update ExPlat client dependencies

# 1.0.0

-   Initial package
