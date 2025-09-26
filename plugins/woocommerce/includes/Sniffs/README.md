# WooCommerce Custom PHPCS Sniffs

This directory contains custom PHP_CodeSniffer (PHPCS) rules for WooCommerce development.

## NoNewFunctionsInIncludesSniff

### Purpose
This sniff prevents the addition of new standalone functions in the `includes` directory. All new code should be added as classes in the `src` directory to maintain a consistent and organized codebase.

### What it detects
- Standalone function declarations in files within the `includes` directory
- Functions that are not methods inside classes

### What it ignores
- Methods inside classes (these are allowed)
- Functions in other directories (like `src/`)

### Usage
The sniff is automatically enabled when running PHPCS with the WooCommerce ruleset. It will report errors for any new standalone functions found in the `includes` directory.

### Example violation
```php
<?php
// File: includes/some-file.php

// This will trigger an error
function new_function_in_includes() {
    return 'This should be in a class in src/';
}
```

### Example that passes
```php
<?php
// File: includes/some-file.php

// This is allowed - it's a method inside a class
class SomeClass {
    public function some_method() {
        return 'This is fine';
    }
}
```

### Error message
When a violation is detected, PHPCS will report:
```
ERROR | New functions are not allowed in the includes directory. Please add new code as classes in the src directory instead.
```

### Configuration
The sniff is configured in `phpcs.xml` with the following rule:
```xml
<rule ref="WooCommerce.Sniffs.Functions.NoNewFunctionsInIncludesSniff">
    <include-pattern>includes/</include-pattern>
</rule>
```

### Running the linter
To run PHPCS with this custom rule:
```bash
# Run on all files
composer run-script phpcs

# Run on specific file
composer run-script phpcs path/to/file.php

# Run on changed files only
composer run-script lint
```