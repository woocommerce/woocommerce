# 3.1.0

-   Return countryInfo as an empty object if not in locale info #6188
-   Localize regional currency information for use during onboarding setup #5969
-   Update dependencies

# 3.0.0

## Breaking changes

-   Currency is now a factory function instead of a class.

-   Add getCurrencyConfig method to retrieve currency config.

-   `formatCurrency` is deprecated in favor of `formatAmount`.

# 2.0.0

## Breaking changes

-   Decouple from global `wcSettings` object.
-   The currency package has been rewritten to export a `Currency` class instead of several utility functions.

## Other changes

-   Remove lodash dependency.

# 1.1.3

-   Update dependencies.

# 1.1.2

-   Update license to GPL-3.0-or-later.

# 1.1.1

-   Change text domain on i18n functions.
-   Bump dependency versions.

# 1.1.0

-   Format using store currency settings (instead of locale)
-   Add optional currency symbol parameter

# 1.0.0

-   Released package
