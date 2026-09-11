# Public API package instructions

Files below this directory implement extension-facing JavaScript contracts.

- Preserve stable exports from each `@woocommerce/*` package root.
- Prefer additive changes. Deprecate existing exports before removing or
  renaming them.
- Preserve the package import, WordPress script handle, and `window.wc` global
  mappings.
- Treat `__experimental` and `__unstable` exports as unstable, and retain their
  prefixes until they are intentionally stabilized.
- Treat deep imports and unexported implementation files as internal unless
  public developer documentation says otherwise.
- State the backward-compatibility impact when changing a root export.
- Check extension usage before changing signatures or runtime behavior.

The Blocks workspace is marked `"private": true` to prevent npm publication.
That setting does not change the public status of these browser APIs.
