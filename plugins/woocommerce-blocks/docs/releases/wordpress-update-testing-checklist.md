# WordPress Update Testing Checklist

Wherever a new version of WordPress is released, or is due for release, there are
a number of steps that developers should perform in order to ensure compatibility
exists between WordPress, Gutenberg, and the Blocks plugin.

**Important:** Before testing, ensure you **disable** the **Gutenberg Feature Plugin** to ensure you're using the correct version of the Editor that is shipping with the new version of WordPress.

Additionally, ensure your test environment includes a mixture of existing blocks to ensure any existing blocks work after the update.

## Testing Checklist

1. Run through the [smoke testing checklist](../contributors/smoke-testing.md) to ensure critical features are still functional.
2. Verify the appearance of blocks on the frontend using the latest official theme. This includes new Twenty-X themes introduced every year.

## Updating `readme.txt`

Once testing is complete and any discovered issues are patched, update the `Tested up to: 5.3` version in readme.txt to match the minor version of WordPress. For example, `5.6`.

If a new fix release is needed to deal with any compatibility problems, any patches, and the readme.txt update, should be cherry picked into the release branch and deployed. See [Releasing Updates](readme.md).

If no changes are needed, and no fix releases are scheduled, you can use an SVN client to update the Stable Tag of the Blocks plugin in trunk (https://plugins.svn.wordpress.org/woo-gutenberg-products-block/trunk/). This will prevent the page on WordPress.org from warning users about incompatibilities.

Instructions for updating specific files on SVN can be [found in this doc](readme.md#appendix-updating-a-specific-file-on-wporg).
