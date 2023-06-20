# WooCommerce Docs Plugin

This is a work-in-progress plugin with the desired goal of consolidating documentation from various sources
into Wordpress posts.

Although this is called WooCommerce Docs, it should be able to be used with any Wordpress site and
a manifest conforming to the data structure (TBD) to create Wordpress posts from Markdown content.

## Development

Set up the monorepo as usual, now from this directory run `pnpm build` to build the webpack assets.
This plugin creates a top level menu called "WooCommerce Docs" that you can navigate to once
you've mounted the plugin in your development environment.
