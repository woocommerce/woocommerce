# Psalm Setup Summary for WooCommerce Plugin

## What Was Done

Psalm, a static analysis tool for PHP, has been successfully set up for the WooCommerce plugin at `plugins/woocommerce/`.

### 1. Dependencies Added

The following packages were added to `composer.json`:

-   **vimeo/psalm** `^5.0` - The main Psalm static analysis tool
-   **psalm/plugin-phpunit** `^0.18` - PHPUnit plugin for better test code analysis

These are installed as development dependencies and don't affect production code.

### 2. Configuration Files

#### composer.json Changes

**Added Dependencies:**

```json
"require-dev": {
    // ... existing dependencies ...
    "psalm/plugin-phpunit": "^0.18",
    "vimeo/psalm": "^5.0"
}
```

**Added Composer Scripts:**

```json
"scripts": {
    "psalm": ["psalm --no-cache"],
    "psalm-baseline": ["psalm --no-cache --set-baseline=psalm-baseline.xml"],
    "psalm-info": ["psalm --no-cache --show-info=true"]
}
```

**Updated Allow-Plugins:**

```json
"allow-plugins": {
    "psalm/plugin-phpunit": true
}
```

#### psalm.xml

Created/updated `psalm.xml` configuration file with:

-   **Error Level**: 6 (balanced between strictness and practicality)
-   **PHP Version**: 7.4 (matches WooCommerce requirements)
-   **Scanned Directories**: `src/` and `woocommerce.php`
-   **Ignored Directories**: vendor, tests, node_modules, build, packages, lib
-   **PHPUnit Plugin**: Enabled for better test analysis
-   **Mixed Type Reporting**: Disabled for WordPress compatibility

### 3. Documentation Created

Created two documentation files:

1. **PSALM.md** - Comprehensive guide covering:

    - What Psalm is and why it's useful
    - Installation and configuration details
    - How to run Psalm (various commands and options)
    - Performance tips for large codebases
    - WordPress-specific considerations
    - Best practices and troubleshooting
    - Future improvement suggestions

2. **PSALM_SETUP_SUMMARY.md** (this file) - Quick reference of what was set up

## Quick Start

### Running Psalm

```bash
# Via Composer scripts (recommended)
composer psalm                  # Run analysis
composer psalm-info            # Show info-level issues
composer psalm-baseline        # Generate baseline

# Direct usage
vendor/bin/psalm                     # Analyze all configured files
vendor/bin/psalm src/Admin/         # Analyze specific directory
vendor/bin/psalm src/Container.php   # Analyze specific file
```

### Example Output

When you run Psalm, you'll see output like:

```
Target PHP version: 7.4 (set by config file).
Scanning files...
Analyzing files...

ERROR: UndefinedFunction - Function does not exist
ERROR: InvalidReturnType - Return type doesn't match

------------------------------
32 errors found
------------------------------

Checks took 2.18 seconds and used 88.376MB of memory
Psalm was able to infer types for 86.67% of the codebase
```

## Current State

✅ **Working**: Psalm is fully installed and functional  
✅ **Tested**: Verified on individual files and directories  
✅ **Documented**: Complete usage guide available in PSALM.md  
⚠️ **Expected Issues**: Many errors related to undefined WordPress functions (normal without WordPress stubs)

### Known Limitations

1. **WordPress Functions Not Recognized**

    - Functions like `add_action`, `esc_html`, `get_option`, etc. show as undefined
    - This is expected without WordPress stubs
    - Future improvement: Add `php-stubs/wordpress-stubs` package

2. **Performance on Full Codebase**

    - Scanning the entire `src/` directory takes time
    - Recommendation: Analyze specific files/directories during development
    - Use caching for better performance

3. **Many Existing Issues**
    - The codebase has existing issues that Psalm detects
    - Consider generating a baseline file to suppress existing issues
    - Focus on preventing new issues in new/modified code

## Integration Recommendations

### 1. Development Workflow

Add to your development process:

```bash
# Before committing changes
vendor/bin/psalm src/path/to/modified-files.php
```

### 2. Pre-commit Hook

Consider adding Psalm to git pre-commit hooks for automatic checks.

### 3. CI/CD Pipeline

Add to your continuous integration:

```yaml
- name: Run Psalm
  run: composer psalm
```

### 4. IDE Integration

Configure your IDE to show Psalm issues in real-time:

-   **PHPStorm**: Install Psalm plugin
-   **VS Code**: Install "Psalm (PHP)" extension

## Next Steps (Optional)

1. **Add WordPress Stubs**

    ```bash
    composer require --dev php-stubs/wordpress-stubs
    ```

    This will eliminate most "undefined function" errors.

2. **Generate Baseline**

    ```bash
    composer psalm-baseline
    ```

    This creates `psalm-baseline.xml` to suppress existing issues.

3. **Expand Coverage**
   Update `psalm.xml` to include more directories:

    - `includes/`
    - `templates/`
    - `uninstall.php`

4. **Lower Error Level**
   As code quality improves, decrease `errorLevel` in `psalm.xml` for stricter analysis.

5. **Add to CI/CD**
   Include Psalm in your continuous integration pipeline.

## Troubleshooting

### Psalm is Slow

```bash
# Use cache (default)
vendor/bin/psalm

# Analyze specific files only
vendor/bin/psalm src/Admin/Schedulers/

# Clear cache if needed
vendor/bin/psalm --clear-cache
```

### Too Many Errors

```bash
# Generate baseline to focus on new issues
composer psalm-baseline

# Adjust error level in psalm.xml (higher = less strict)
errorLevel="7"
```

## Files Modified/Created

-   ✏️ **Modified**: `composer.json` - Added Psalm dependencies and scripts
-   ✏️ **Modified**: `composer.lock` - Updated with new dependencies
-   ✏️ **Modified**: `psalm.xml` - Updated configuration
-   ✅ **Created**: `PSALM.md` - Comprehensive documentation
-   ✅ **Created**: `PSALM_SETUP_SUMMARY.md` - This summary

## Resources

-   **Psalm Documentation**: https://psalm.dev/docs/
-   **Psalm GitHub**: https://github.com/vimeo/psalm
-   **Issue Reference**: https://psalm.dev/docs/running_psalm/issues/
-   **Annotations Guide**: https://psalm.dev/docs/annotating_code/supported_annotations/

## Support

For detailed usage instructions, see **PSALM.md**.  
For Psalm-specific questions, consult the official documentation at https://psalm.dev/

---

**Setup completed**: October 30, 2025  
**Psalm version**: 5.26.1  
**Configuration**: `plugins/woocommerce/psalm.xml`
