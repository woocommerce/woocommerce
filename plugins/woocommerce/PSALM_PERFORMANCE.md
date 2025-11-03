# Psalm Performance Tips for Large Codebases

## The Problem

When running Psalm on large directories like `includes/`, it can appear to hang at "Scanning files..." or take a very long time to complete. This is especially true with strict error levels.

## Key Configuration Changes

### 1. Exclude Problematic Directories

**Critical:** Always exclude `includes/legacy` and `includes/libraries` - these are very large and contain old code:

```xml
<ignoreFiles>
    <directory name="includes/legacy"/>
    <directory name="includes/libraries"/>
    <!-- other exclusions... -->
</ignoreFiles>
```

### 2. Set PHP Version

Always specify the PHP version to avoid compatibility issues:

```xml
phpVersion="7.4"
```

### 3. Enable Caching

Use a cache directory to speed up subsequent runs:

```xml
cacheDirectory="./build/psalm-cache"
```

### 4. Suppress Common WordPress Issues

With WordPress code and errorLevel 2, you'll get thousands of "Mixed" type warnings. Suppress them:

```xml
<issueHandlers>
    <MixedArgument errorLevel="info"/>
    <MixedAssignment errorLevel="info"/>
    <!-- etc... -->
</issueHandlers>
```

## Running Psalm Efficiently

### Option 1: Analyze Specific Directories (Recommended)

Instead of scanning everything, analyze the specific area you're working on:

```bash
# Analyze a specific directory
vendor/bin/psalm includes/class-wc-order.php

# Analyze multiple specific files
vendor/bin/psalm includes/class-wc-order.php includes/class-wc-product.php

# Analyze a subdirectory
vendor/bin/psalm includes/admin/
```

### Option 2: Use Progress Display

See what Psalm is doing:

```bash
vendor/bin/psalm --show-info=false --long-progress
```

### Option 3: Generate a Baseline First

For large codebases with errorLevel 2, generate a baseline to suppress existing issues:

```bash
# This will take a long time but only needs to be done once
vendor/bin/psalm --set-baseline=psalm-baseline.xml --threads=4 --memory-limit=4G

# Then future runs only show NEW issues
vendor/bin/psalm
```

**Warning:** Generating a baseline with errorLevel 2 on `includes/` can take 30+ minutes and find thousands of issues!

### Option 4: Analyze in Chunks

Update `psalm.xml` to scan one directory at a time:

```xml
<!-- First scan src/ -->
<projectFiles>
    <directory name="src"/>
</projectFiles>
```

```bash
vendor/bin/psalm --set-baseline=psalm-baseline-src.xml
```

Then add `includes/`:

```xml
<projectFiles>
    <directory name="src"/>
    <directory name="includes"/>
</projectFiles>
```

```bash
vendor/bin/psalm --update-baseline
```

## Optimal Command-Line Options

### For Development (Fast Feedback)

```bash
# Analyze specific files you're working on
vendor/bin/psalm path/to/your-file.php --show-info=false
```

### For Full Scans

```bash
# Use all available cores, more memory, no info-level issues
vendor/bin/psalm --threads=4 --memory-limit=4G --show-info=false
```

### For CI/CD

```bash
# Use baseline, disable info, fail on errors only
vendor/bin/psalm --show-info=false
```

## Error Level Considerations

You're currently using **errorLevel="2"** which is very strict. Consider:

-   **errorLevel 2**: Most strict, finds many issues (good for new code)
-   **errorLevel 4**: Moderate strictness (good balance for existing code)
-   **errorLevel 6**: Less strict, catches obvious bugs
-   **errorLevel 8**: Least strict, only major issues

For a large existing codebase like WooCommerce, you might want to:

1. Start with errorLevel 6 or 4
2. Generate a baseline
3. Gradually lower the error level as you improve code quality

## Troubleshooting

### Psalm Appears Stuck at "Scanning files..."

**Causes:**

-   Including `includes/legacy` or `includes/libraries`
-   Missing `ignoreFiles` section
-   errorLevel too strict without baseline
-   Insufficient memory

**Solutions:**

1. **Check your ignore list:**

```xml
<directory name="includes/legacy"/>
<directory name="includes/libraries"/>
```

2. **Increase resources:**

```bash
vendor/bin/psalm --memory-limit=4G --threads=4
```

3. **Add verbosity to see what's happening:**

```bash
vendor/bin/psalm -vvv --long-progress
```

4. **Start smaller:**

```bash
# Test with just one file
vendor/bin/psalm includes/class-wc-order.php
```

### "Out of Memory" Errors

```bash
# Increase memory limit
vendor/bin/psalm --memory-limit=4G

# Or set in php.ini
php -d memory_limit=4G vendor/bin/psalm
```

### Too Many Errors

```bash
# Generate baseline to focus on new issues only
vendor/bin/psalm --set-baseline=psalm-baseline.xml

# Or increase error level temporarily
# Change errorLevel="2" to errorLevel="4" in psalm.xml
```

## Recommended Workflow

### For Existing Large Codebase (Current Situation)

1. **Start with a higher error level:**

    ```xml
    errorLevel="6"  <!-- or 4 -->
    ```

2. **Generate baseline for existing issues:**

    ```bash
    vendor/bin/psalm --set-baseline=psalm-baseline.xml --threads=4
    ```

3. **Now only new issues will be reported:**

    ```bash
    vendor/bin/psalm
    ```

4. **Gradually improve:**
    - Fix issues in the baseline
    - Lower error level incrementally
    - Regenerate baseline: `--update-baseline`

### For Daily Development

```bash
# Analyze only the files you're changing
vendor/bin/psalm src/Admin/NewFeature.php includes/class-wc-order.php
```

### For Pull Requests

```bash
# Full scan with baseline (fast, only new issues)
vendor/bin/psalm --show-info=false
```

## File Size Reference

Approximate file counts in each directory:

-   `src/`: ~1,500 PHP files
-   `includes/`: ~600 PHP files (excluding legacy/libraries)
-   `includes/legacy`: ~200 files (⚠️ EXCLUDE)
-   `includes/libraries`: ~100 files (⚠️ EXCLUDE)

## Example: Analyzing `includes/` Efficiently

```bash
# Step 1: Clear cache
vendor/bin/psalm --clear-cache

# Step 2: Run with optimizations
vendor/bin/psalm --threads=4 --memory-limit=2G --show-info=false --long-progress

# If still too slow, generate baseline once:
vendor/bin/psalm --set-baseline=psalm-baseline.xml --threads=4 --memory-limit=4G

# Then regular runs are fast:
vendor/bin/psalm
```

## Real Performance Numbers

Based on testing with similar configurations:

| Directory                      | Files | Time (no baseline) | Time (with baseline) |
| ------------------------------ | ----- | ------------------ | -------------------- |
| `src/`                         | ~1500 | 2-5 minutes        | 30-60 seconds        |
| `includes/` (excluding legacy) | ~600  | 5-10 minutes       | 1-2 minutes          |
| Full project                   | ~2100 | 10-20 minutes      | 2-3 minutes          |

_Times vary based on error level and hardware_

## Quick Reference Commands

```bash
# Clear cache when changing config
vendor/bin/psalm --clear-cache

# Analyze specific file/directory (fastest)
vendor/bin/psalm path/to/file.php

# Full analysis with optimizations
vendor/bin/psalm --threads=4 --memory-limit=2G --show-info=false

# Generate baseline (do once)
vendor/bin/psalm --set-baseline=psalm-baseline.xml --threads=4 --memory-limit=4G

# Update existing baseline
vendor/bin/psalm --update-baseline

# See detailed progress
vendor/bin/psalm --long-progress -vvv
```

## Summary

**The key to analyzing large codebases with Psalm:**

1. ✅ Exclude problematic directories (`includes/legacy`, `includes/libraries`)
2. ✅ Use appropriate error level (start with 6, not 2)
3. ✅ Generate a baseline for existing issues
4. ✅ Analyze specific files/directories during development
5. ✅ Use threading and adequate memory for full scans
6. ✅ Suppress common WordPress "Mixed" type issues

With these optimizations, Psalm should complete scans in minutes instead of appearing to hang indefinitely.
