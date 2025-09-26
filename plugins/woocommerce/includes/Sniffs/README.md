# WooCommerce Custom PHPCS Sniffs

This directory contains custom PHP_CodeSniffer (PHPCS) rules for WooCommerce development to enforce proper directory structure and coding standards.

## Directory Structure Rules

The following rules enforce the proper organization of code in WooCommerce:

### 1. NoNewFunctionsInIncludesSniff

**Purpose**: Prevents the addition of new standalone functions in the `includes` directory.

**What it detects**:
- Standalone function declarations in files within the `includes` directory
- Functions that are not methods inside classes

**What it ignores**:
- Methods inside classes (these are allowed)
- Functions in other directories

**Error message**:
```
ERROR | New functions are not allowed in the includes directory. No new functions or classes are allowed in the includes directory.
```

### 2. NoNewClassesInIncludesSniff

**Purpose**: Prevents the addition of new classes, interfaces, and traits in the `includes` directory.

**What it detects**:
- Class declarations (`class`)
- Interface declarations (`interface`)
- Trait declarations (`trait`)

**Error message**:
```
ERROR | New classes, interfaces, and traits are not allowed in the includes directory. No new functions or classes are allowed in the includes directory.
```

### 3. NoNewFunctionsInSrcSniff

**Purpose**: Prevents the addition of new standalone functions in the `src` directory. Only classes are allowed in `src`.

**What it detects**:
- Standalone function declarations in files within the `src` directory
- Functions that are not methods inside classes

**What it ignores**:
- Methods inside classes (these are allowed)
- Functions in other directories

**Error message**:
```
ERROR | New standalone functions are not allowed in the src directory. Only new classes are allowed in the src directory.
```

## Summary of Rules

| Directory | Functions | Classes/Interfaces/Traits |
|-----------|-----------|---------------------------|
| `includes/` | ❌ Not allowed | ❌ Not allowed |
| `src/` | ❌ Not allowed | ✅ Allowed |

## Example Violations

### includes/ directory violations:
```php
<?php
// File: includes/some-file.php

// This will trigger an error (function)
function new_function_in_includes() {
    return 'This should not be here';
}

// This will also trigger an error (class)
class NewClassInIncludes {
    public function method() {
        return 'This should not be here either';
    }
}
```

### src/ directory violations:
```php
<?php
// File: src/some-file.php

// This will trigger an error (standalone function)
function new_function_in_src() {
    return 'This should not be here';
}

// This is allowed (class)
class NewClassInSrc {
    public function method() {
        return 'This is fine';
    }
}
```

## Configuration

All sniffs are configured in `phpcs.xml`:

```xml
<!-- Custom rules to enforce directory structure -->
<rule ref="WooCommerce.Sniffs.Functions.NoNewFunctionsInIncludesSniff">
    <include-pattern>includes/</include-pattern>
</rule>

<rule ref="WooCommerce.Sniffs.Classes.NoNewClassesInIncludesSniff">
    <include-pattern>includes/</include-pattern>
</rule>

<rule ref="WooCommerce.Sniffs.Functions.NoNewFunctionsInSrcSniff">
    <include-pattern>src/</include-pattern>
</rule>
```

## Running the Linter

To run PHPCS with these custom rules:

```bash
# Run on all files
composer run-script phpcs

# Run on specific file
composer run-script phpcs path/to/file.php

# Run on changed files only
composer run-script lint
```