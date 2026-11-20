# CSS Build System <!-- omit in toc -->

## Table of contents <!-- omit in toc -->

-   [Right-to-left](#right-to-left)
-   [Relevant files](#relevant-files)

CSS files are built with the Rolldown build, which gathers all SCSS files in the app and processes them with Sass and some PostCSS plugins like Autoprefixer. The resulting stylesheets are merged into three files for the whole plugin:

-   `style.css`: loaded in the editor and the frontend, it includes the basic styles needed for the blocks frontend.
-   `editor.css`: only loaded in the editor, it includes styles only relevant to the block editor like specific editor components.
-   `vendors-style.css`: loaded in the editor and the frontend of some blocks, it includes external stylesheets which are required by some blocks but are not part of our codebase.

> Details on which stylesheets are included in each output file can be found in [`bin/rolldown/styles.mjs`](../../bin/rolldown/styles.mjs).

## Right-to-left

All files described above are generated in a LTR version and a RTL version. The RTL version is generated automatically with rtlcss and has a `-rtl.css` suffix at the end of the file name.

## Relevant files

-   [`bin/rolldown/styles.mjs`](../../bin/rolldown/styles.mjs): the two style builds. If you're looking for something, start here.
-   [`packages/js/rolldown-build/lib/css.mjs`](../../../../../../packages/js/rolldown-build/lib/css.mjs): Sass, PostCSS, minification and RTL.
-   [`bin/entries.js`](../../bin/entries.js): mapping source files to entry points. If you're adding a new block or module to the build, start here.

<!-- FEEDBACK -->

---

[We're hiring!](https://woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce/issues/new?assignees=&labels=type%3A+documentation&template=suggestion-for-documentation-improvement-correction.md&title=Feedback%20on%20./docs/contributors/css-build-system.md)

<!-- /FEEDBACK -->

