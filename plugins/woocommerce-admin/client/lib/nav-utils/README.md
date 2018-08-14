Nav Utils
=========

This is a library of functions used in navigation.

## `getPath()`

Get the current path from history.

## `getQuery()`

Get the current query string, parsed into an object, from history.

## `getAdminLink( string: path )`

JS version of `admin_url`. Returns the full URL for a page in wp-admin.

## `getNewPath( object: query, string: path, object: currentQuery )`

Return a URL with set query parameters. Optional `path`, `currentQuery`, both will default to the current value fetched from `history` if not provided.

## `updateQueryString( object: query )`

Updates the query parameters of the current page.
