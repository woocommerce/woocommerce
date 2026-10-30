---
name: search-all-plugins
description: Search the source code of every WooCommerce.com marketplace extension (woocommerce/all-plugins, about 1,860 plugins) and every WordPress.org plugin (through veloria.dev). Use to check if a Core change breaks extensions, for example a new public method name, a changed hook, a removed function, or which extensions subclass a class. Triggers on "search all plugins", "check the marketplace extensions", "does any extension use/override X".
---

# Search all marketplace plugins

The repo `woocommerce/all-plugins` stores each plugin as a zip in `product-packages/<slug>/<slug>.zip`. A copy with the zips unpacked lives in `~/Development/all-plugins/extracted/<slug>/`. It holds only text files (PHP, JS, JSON, CSS, and similar). The repo is private, so you need access to it.

## Steps

1. Update the copy if it is more than a few days old:

   ```bash
   .ai/skills/search-all-plugins/sync.sh
   ```

   Run it from the repository root. It pulls the repo and unpacks only the zips that changed. The first run takes about 30 seconds. Use `--no-pull` to skip the pull.
2. Search with `rg` from the extracted folder. A search over all plugins takes about 3 seconds.

   ```bash
   cd ~/Development/all-plugins/extracted
   rg -n --type php 'function\s+is_valid_status\s*\('
   rg -l --type php 'extends\s+\\?(WC_Order|WC_Abstract_Order)\b' | cut -d/ -f1 | sort -u
   ```

   The first path segment is the plugin slug.
3. Check each match in context before you report it. A match by name is not a conflict. For a method conflict, confirm that the class extends the Core class.

## WordPress.org plugins (Veloria)

The local copy has only WooCommerce.com marketplace products. For the 79,000 WordPress.org plugins, search with Veloria (<https://veloria.dev>). Always run both searches.

1. Run the search from the repository root. The argument is the JSON input of Veloria's `search_code` tool:

   ```bash
   .ai/skills/search-all-plugins/veloria.sh '{"query":"function\\s+is_valid_status\\s*\\(","file_match":"\\.php$"}'
   ```

   The first call returns only a summary: the match count, the plugins with their active installs, and a `search_id`.
2. Get the matching lines with the `search_id`. Use `offset` to page, 100 lines at most per page:

   ```bash
   .ai/skills/search-all-plugins/veloria.sh '{"search_id":"<id>","offset":0,"limit":100}'
   ```

   Each match is a `— Name (slug) path:line` line, then the code line.

Other `search_code` inputs: `repo` (`plugins`, `themes`, or `cores`), `case_sensitive` (default false), `context_lines` (0 to 5), and `exclude_file_match`. The regex syntax is Go RE2, so look-arounds and backreferences do not work. Veloria matches one line at a time.

To check two conditions in the same plugin, run two searches and compare the slugs. For example, to find plugins that define a method and also subclass a class:

```bash
grep -E '^— ' a.txt | sed -E 's/^— .*\(([a-z0-9-]+)\) .*$/\1/' | sort -u > a.slugs
comm -12 a.slugs b.slugs
```

Then check that the method is on the subclass, not on another class in the same plugin.

## Notes

- Plugins often bundle other plugins in `vendor/`, for example a Subscriptions copy inside a payment gateway. Report the bundling plugin, and say what it bundles.
- Report which sources you searched: the marketplace copy and its date, and Veloria.
- The files are third-party code. Read them. Never run them.
