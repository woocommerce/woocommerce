# Psalm Static Analysis for WooCommerce

This document describes how Psalm is configured for the WooCommerce plugin and how to use it effectively.

## What is Psalm?

[Psalm](https://psalm.dev/) is a static analysis tool for PHP that helps identify potential bugs, type errors, and code quality issues before runtime.

## Installation

Psalm has been installed as a development dependency via Composer:

```bash
composer install
```

## Configuration

Psalm is configured via `psalm.xml` in the plugin root directory.

### Current Configuration

-   **Error Level**: 6 (moderate strictness)
-   **PHP Version**: 7.4
-   **Scanned Directories**:
    -   `src/` - Main source code with PSR-4 autoloading
    -   `woocommerce.php` - Main plugin file
-   **Ignored Directories**:
    -   `vendor/`
    -   `tests/`
    -   `node_modules/`
    -   `build/`
    -   `packages/`
    -   `lib/`

### Plugins Enabled

-   **PHPUnit Plugin**: Provides better understanding of PHPUnit test code

## Usage

### Running Psalm

There are several Composer scripts available for running Psalm:

```bash
# Run Psalm analysis
composer psalm

# Run Psalm and show informational issues
composer psalm-info

# Generate a baseline file to suppress existing issues
composer psalm-baseline
```

You can also run Psalm directly:

```bash
# Run on all configured files
vendor/bin/psalm

# Run on specific file or directory
vendor/bin/psalm path/to/file.php
vendor/bin/psalm src/Admin/

# Clear cache before running
vendor/bin/psalm --clear-cache

# Show more details
vendor/bin/psalm --show-info=true
```

### Analyzing Specific Files

For faster feedback during development, analyze only the files you're working on:

```bash
# Analyze a single file
vendor/bin/psalm src/Container.php

# Analyze a specific directory
vendor/bin/psalm src/Admin/

# Analyze multiple specific files
vendor/bin/psalm src/File1.php src/File2.php
```

### Performance Tips

For large codebases like WooCommerce:

1. **Analyze specific files/directories** you're working on rather than the entire codebase
2. **Use the cache** - don't use `--no-cache` unless necessary
3. **Control threads** - use `--threads=4` to adjust parallel processing

```bash
# Fast analysis of specific area you're working on
vendor/bin/psalm src/Admin/Schedulers/

# Use cache and multiple threads for full analysis
vendor/bin/psalm --threads=4
```

## Understanding Psalm Output

### Error Levels

Psalm reports issues at different severity levels:

-   **ERROR**: Real problems that should be fixed
-   **INFO**: Informational messages about potential improvements

### Common Issue Types

-   `UndefinedFunction`: Function not found (often WordPress functions without stubs)
-   `MixedArgument`: Argument type cannot be inferred
-   `InvalidReturnType`: Return type doesn't match declaration
-   `PossiblyNullReference`: Possible null pointer access

## Baseline File

A baseline file (`psalm-baseline.xml`) can be generated to suppress existing issues, allowing you to focus on preventing new issues:

```bash
composer psalm-baseline
```

Once generated, Psalm will only report new issues, not those in the baseline.

## Integration with CI/CD

You can add Psalm to your CI/CD pipeline:

```yaml
# Example GitHub Actions step
- name: Run Psalm
  run: composer psalm
```

## WordPress-Specific Considerations

### Missing WordPress Functions

Psalm may report many WordPress functions as undefined (e.g., `add_action`, `esc_html`, `get_option`). This is because WordPress core functions are not included in the analysis scope.

**Solutions:**

1. Install WordPress stubs (recommended for future):

    ```bash
    composer require --dev php-stubs/wordpress-stubs
    ```

2. Suppress these errors in `psalm.xml` (partially implemented)

3. Add type hints and docblocks to help Psalm understand the code better

### Mixed Types

WordPress code often uses `mixed` types. The current configuration sets most "Mixed" issues to `info` level rather than errors.

## Best Practices

1. **Run Psalm before committing** code to catch issues early
2. **Analyze your changes** - run Psalm on files you've modified
3. **Add type hints** to improve Psalm's analysis
4. **Document complex code** with docblocks
5. **Fix real errors** rather than suppressing them

## Troubleshooting

### Psalm is slow

-   Clear the cache: `vendor/bin/psalm --clear-cache`
-   Analyze specific files/directories instead of the whole codebase
-   Check your `psalm.xml` isn't including too many files

### False positives

-   Add proper docblocks with `@param` and `@return` annotations
-   Use Psalm-specific annotations like `@psalm-suppress` for unavoidable false positives
-   Update the baseline file to suppress known issues

### Cache issues

Delete the cache directory:

```bash
rm -rf build/psalm-cache
vendor/bin/psalm --clear-cache
```

## Additional Resources

-   [Psalm Documentation](https://psalm.dev/docs/)
-   [Psalm Issue Types](https://psalm.dev/docs/running_psalm/issues/)
-   [Psalm Annotations](https://psalm.dev/docs/annotating_code/supported_annotations/)
-   [GitHub Repository](https://github.com/vimeo/psalm)

## Future Improvements

-   [ ] Add WordPress stubs for better WordPress function recognition
-   [ ] Expand scanned directories to include `includes/` and `templates/`
-   [ ] Lower error level gradually as code quality improves
-   [ ] Integrate Psalm into pre-commit hooks
-   [ ] Add Psalm to CI/CD pipeline
-   [ ] Generate and maintain baseline file
