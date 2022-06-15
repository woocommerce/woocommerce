# 4.0.1

-   Update all js packages with minor/patch version changes. #8392

# 4.0.0

## Breaking changes

-   Update dependencies to support react 17. #8305
-   Drop support for IE11. #8305

# 3.2.0

-   Remove dev dependency `@woocommerce/wc-admin-settings`. #8057
-   Add "defaultDateRange" argument to "getAllowedIntervalsForQuery" for default period value. #8189
-   Add type option to `getDateFormatsForInterval` to support `getDateFormatsForIntervalPhp` feature. #8129
-   Sentence case all the things analytics #6501
-   Fix end date for last periods #6584

# 3.1.0

-   Fix commonjs module build, allow package to be built in isolation. #7286

# 3.0.0

-   Take into account leap year in calculating `getLastPeriod`.

## Breaking changes

-   Move Lodash to a peer dependency.

# 2.1.0

-   Update to @wordpress/eslint coding standards.

# 2.0.0

## Breaking changes

-   Decouple from global wcSettings object (#3278)
-   Exported methods of the date package have been rewritten to accept a configuration object as their second parameter.
-   `loadLocaleData` is no longer called within the date package. Consuming code must take care of that themselves.

# 1.2.1

-   Update dependencies.

# 1.2.0

-   Enhancement: gather default date settings from `wcSettings.wcAdminSettings.woocommerce_default_date_range` if they exist.
-   Update license to GPL-3.0-or-later.

# 1.0.7

-   Change text domain on i18n functions.
-   Bump dependency versions.

# 1.0.6

-   Removed timezone from `appendTimestamp()` output.

# 1.0.5

-   Fixed bug in getAllowedIntervalsForQuery() to not return `hour` for default intervals

# 1.0.4

-   Remove deprecated @wordpress/date::getSettings() usage.

# 1.0.3

-   Fix missing comma seperator in date inside tooltips.

# 1.0.2

-   Add `getChartTypeForQuery` function to ensure chart type is always `bar` or `line`
