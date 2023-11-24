# Transient Files Engine

## Index

* [Summary](#summary)
* [Contents of the engine](#contents-of-the-engine)
* [The code API](#the-code-api)
* [Hooks](#hooks)
* [The template rendering context](#the-template-rendering-context)
* [Defining reusable blocks in templates](#defining-reusable-blocks-in-templates)
* [The REST API endpoints](#the-rest-api-endpoints)


## Summary

The WooCommerce transient files engine allows to create, delete, and get information for transient files. We define a _transient file_ as a physical file (in the server filesystem) that:

* Has a random name.
* Can have any arbitrary content.
* Lives in a dedicated subdirectory inside the WordPress `uploads` folder (customizable via [hook](#hooks)).
* Has an expiration date.
* Its contents can be retrieved via [authenticated REST API endpoint](#get-wp-jsonwcv3filestransientid).
* Optionally, its contents can be retrieved via [public (unauthenticated) HTTP endpoint](#get-wcfiletransientname).

As of now, the only way to create a transient file is by rendering a text-based template, containing a mix of literal text and PHP code, that receives a set of variables. Additional mechanisms (e.g. directly supplying binary content) might be added in the future (pull requests welcome, as usual!)

A mechanism is in place too to periodically delete expired files using Action Scheduler.


## Contents of the engine

The transient files engine consists of the following, all the files live in the `src/TransientFiles` directory:

* `TransientFilesEngine.php`: The `TransientFilesEngine` class provides the code API for the engine. It contains public methods that allow creating and deleting transient files, and retrieving information about an existing file by its id or file name. It also handles the cleanup of expired files, manually or via Action Scheduler; and provides the debug tool that can be used to re-trigger the scheduled action.
* `TemplateRenderingContext.php`: When creating a transient file with [`TransientFilesEngine::create_file_by_rendering_template`](#create_file_by_rendering_template) the code in the template will see an instance of this class as `$this`.
* `TransientFilesRestController.php`: The `TransientFilesRestController` class handles the JSON REST API endpoints that allow to create, delete and retrieve information for transient files; and also the public HTTP endpoint that serves files created as "public".
* `Templates`: This directory contains the text+code templates that allow creating transient files with the [`TransientFilesEngine::create_file_by_rendering_template`](#create_file_by_rendering_template), additional templates can be used via a dedicated [hook](#hooks).
* `wp_wc_transient_files`: Database table that contains one entry per existing transient file, including basic information like the file name, the expiration date, and whether the file is public (reachable via unauthenticated HTTP endpoint) or not.
* `wp_wc_transient_files_meta`: Database table that contains metadata for the existing transient files. Providing metadata when creating transient files is optional, and with the exception of the `content-type` key, the engine won't make any use of this metadata.

The database schema can be seen in the value returned by `TransientFilesEngine::get_database_schema`.


## The code API

The `TransientFilesEngine` provides the following public methods and hooks, please refer to the documentation comment of each method for further details:


### create_file_by_rendering_template

This is the method used to create a transient file from the combination of a template and a set of variables. It gets the template path (relative to the templates directory) and name, the variables, and the metadata; the returned value is the name (file name only, without the path) of the created file. If `null` is passed instead of a metadata array, no transient file is actually generated: instead, the rendered content is returned directly as the method result (this can be useful for debugging templates from the command line).

Here's how you can quickly test this method from the command line:

```bash
wp eval "echo wc_get_container()->get(\Automattic\WooCommerce\TransientFiles\TransientFilesEngine::class)->create_file_by_rendering_template('examples/show_variables', ['foo'=>'bar', 'fizz'=>'buzz']);"
```

Or to actually create a file with an expiration time of 10 minutes:

```bash
wp eval "echo wc_get_container()->get(\Automattic\WooCommerce\TransientFiles\TransientFilesEngine::class)->create_file_by_rendering_template('examples/show_variables', ['foo'=>'bar', 'fizz'=>'buzz'], ['expiration_seconds'=>600]);"
```


### get_transient_files_directory

Gets the base directory where existing transient files are stored. The actual directory for each file is the combination of the base directory and the expiration date of the file, formatted as `yyyy-mm-dd`.


### get_file_by_id, get_file_by_name

Gets information about an existing transient file given its database id or its file name. You can get the database id of a file by invoking `get_file_by_name` with the file name that [`create_file_by_rendering_template`](#create_file_by_rendering_template) returns.

Here's how you can quickly test this method from the command line, replace `<file name>` with the value that you got from [`create_file_by_rendering_template`](#create_file_by_rendering_template):

```bash
wp eval "var_export(wc_get_container()->get(\Automattic\WooCommerce\TransientFiles\TransientFilesEngine::class)->get_file_by_name('<file name>'));"
```


### delete_file_by_id, delete_file_by_name

Deletes a transient file and all its associated database entries given its database id or its file name.


### delete_expired_files

Immediately (bypassing the scheduled action) deletes a limited amount of expired files. Only files that have expired in the previous day or earlier are deleted.

For performance reasons database entries are deleted only when no expired physical files are left. This means that database entries can exist for no longer existing files, but that can happen only for expired files.


### expired_files_cleanup_is_scheduled, schedule_expired_files_cleanup, unschedule_expired_files_cleanup

These methods control the scheduled cleanup of expired files. The scheduling works as follows:

1. `schedule_expired_files_cleanup` will schedule a cleanup operation (with `delete_expired_files`) to run immediately.
2. If the cleanup operation actually deletes at least one file (or database entry), then the action is rescheduled to run again immediately.
3. When no files or database entries are deleted, the action is rescheduled for 24h later (this interval can be changed with a hook).

A dedicated tool is provided in the debug tools page to trigger or cancel the cleanup scheduled action.


### Hooks

The `TransientFilesEngine` provides the following hooks:

* `woocommerce_transient_file_creation_metadata`: Filter to modify or extend the metadata that will be stored for a transient file upon its creation.

* `woocommerce_transient_file_creation_filename`: Filter that allows to change the name that a newly created transient file will get (file name only, not path).

* `woocommerce_transient_files_minimum_expiration_seconds`: Filter that allows to change the minimum lifetime (the minimum expiration interval to add to the current date, in seconds) allowed for a newly created transient file, the default value is 60.

* `woocommerce_transient_files_directory`: Filter that allows to change the base directory where transient files are stored. This affects both the creation of new transient files and the retrieval of existing files.

* `woocommerce_transient_file_creation_template_file_path`: Filter that allows to change or set the path of the template file to be used when creating a new transient file with `create_file_by_rendering_template`. If the method can't find the supplied template name it will first trigger this hook (passing `null` as the template file path) before resorting to throwing an error; this allows plugins to use their own template files.

* `woocommerce_delete_expired_transient_files_interval`: Filter that allows to change the interval between cleanup scheduled actions when no files are left to delete. The default is 24h.

Additionally, the `TransientFilesRestController` class provides one hook:

* `woocommerce_transient_file_contents_served`: Action triggered when the content of a transient file is served either via the JSON REST API endpoint or the public HTTP endpoint.


## The template rendering context

When creating a transient file with [`TransientFilesEngine::create_file_by_rendering_template`](#create_file_by_rendering_template) the code in the template will see an instance of the `TemplateRenderingContext` class as `$this`. This class provides the following methods and properties.


### $x = $this->variable_name

Variables passed to `create_file_by_rendering_template` can be retrieved from the template by directly referencing them by name as a property of the context object. If no variable exists with the specified name `null` will be obtained.

[Local variables](#this-variable_name--value) can be retrieved like this as well. In case of name conflict the value of the local variable will be returned.


### $this->get_variable('name')

Using the `get_variable` method is equivalent to retrieving the variable with `$x = $this->variable_name`.


### $this->variable_name = value

Sets the value of a _local variable_. These variables can be retrieved via `$x = $this->variable_name`, `$this->get_variable` and are also recognized by `has_variable` and `get_variables`; however these won't be passed to secondary templates rendered with [`render`](#this-rendertemplate_path-variables-relative).


### $this->set_variable('name', value)

Using the `set_variable` method is equivalent to setting a local variable with `$this->variable_name = value`.


### $this->get_variable_names()

Gets the names of all the available variables, including the local variables.


### $this->has_variable('name')

Returns a boolean value indicating if a variable with a given name is available (includes local variables).


### $this->get_template_display_name()

Gets the display name of the template being rendered. By default this is the file name (without path or extension) of the template file, but it can be changed with [`set_template_display_name`](#this-set_template_display_namename).


### $this->set_template_display_name('name')

Changes the display name of the template being rendered, which can be returned with [`get_template_display_name`](#this-get_template_display_name).


### $this->render('template_path', variables, relative)

Renders a secondary template. The variables that were passed to the current template (**not** including local variables) are merged with the variables passed in `$variables`, and the result of the merge is what the secondary template gets.

If `$relative` is `true` (default) the supplied template path is considered to be relative to the template currently being rendered, so if no path information (only a file name) is passed, the template is searched for in the same directory of the current template.


## Defining reusable blocks in templates

While the template rendering context class doesn't provide a way to define reusable content blocks to be used multiple times within the same template, it's easy to define them as strings and then using `eval` to apply them.

Let's see an example. The `src/TransientFiles/Templates/examples` directory has a `show_variables.template` file that contains the following:

```php
<?php foreach( $variable_names as $name ) { ?>
    <?php echo "<tr><td>{$name}</td><td>{$this->get_variable( $name )}</td></tr>"; ?>
<?php } ?>
```

Assume that we want to convert the row generation line to a reusable block. We can do it in a number of ways:

```php
// Regular string
<?php $RENDER_ROW='?><tr><td><?php echo $name; ?></td><td><?php echo $this->get_variable( $name ); ?></td></tr>'; ?>

// Heredoc string v1
<?php $RENDER_ROW = <<<'__BODY__'
?><tr><td><?php echo $name; ?></td><td><?php echo $this->get_variable( $name ); ?></td></tr>
__BODY__; ?>

// Heredoc string v2
<?php $RENDER_ROW = <<<'__BODY__'
echo "<tr><td>{$name}</td><td>{$this->get_variable( $name )}</td></tr>";
__BODY__; ?>
```

Then we can replace the variables rendering block with this version:

```php
<?php foreach( $variable_names as $name ) {
    eval( $RENDER_ROW );
} ?>
```

## The REST API endpoints

The following JSON REST API endpoints are available to interact remotely with the transient files engine. All the required capabilities are granted by default to administrators and shop managers.


### POST /wp-json/wc/v3/files/transient/render

This is the endpoint that allows to create a transient file from a template and a set of variables. The data for the file creation is passed in the body as in the following example:

```json
{
    "template_name": "examples/show_variables",
    "expiration_date": "2023-12-01 12:34:56",
    "is_public": true,
    "variables": {
        "greeting": "Hello from REST!"
    },
    "metadata": {
        "content-type": "text/html",
        "meta": "data"
    }
}
```

where:

* An expiration interval in seconds can be passed in an `expiration_seconds` key as an alternative to `expiration_date`.
* `is_public` is optional and defaults to `false`. The public endpoint will serve the file contents only if this key is passed with a value of `true`.
* `variables` is optional and defaults to an empty array.
* `metadata` is optional and defaults to an empty array. The `content-type` metadata key is the only one used by the engine, but it's also optional and defaults to `text/html`.

The result from the request will contain information about the generated file as in the following example:

```json
{
    "id": "7",
    "file_name": "aa24d02fab6fff5b5ec2cc147fe097ee",
    "date_created_gmt": "2023-11-22 10:54:52",
    "expiration_date_gmt": "2023-12-01 12:34:56",
    "is_public": true,
    "has_expired": false,
    "public_url": "http://localhost/wc/file/transient/aa24d02fab6fff5b5ec2cc147fe097ee"
}
```

`public_url` will be present only if `is_public` is `true`.

This endpoint requires the `create_transient_file` capability.


## GET /wp-json/wc/v3/files/transient/&lt;id&gt;/info [?include_metadata=true]

Retrieves information about an existing transient file identified by the supplied id (it's the value returned in `id` by the file creation endpoint). The information returned has the same format as the one returned by [the file creation endpoint](#post-wp-jsonwcv3filestransientrender), but an optional `?include_metadata=true` argument can be appended to the query URL to include also a `metadata` key in the response (which will be an object with the same format as the `metadata` object that was supplied when creating the file).

This endpoint requires the `read_transient_file` capability.


## DELETE /wp-json/wc/v3/files/transient/&lt;id&gt;

Deletes the transient file identified by the supplied id (it's the value returned in `id` by [the file creation endpoint](#post-wp-jsonwcv3filestransientrender)). The return value has one single key: `deleted`, with the value `true` if the file was actually deleted and `false` if no transient file exists with the supplied id.

This endpoint requires the `delete_transient_file` capability.


## GET /wp-json/wc/v3/files/transient/&lt;id&gt;

This endpoint does **not** return JSON. It retrieves the raw contents of the file with the value of the `Content-Type` HTTP header taken from the `content-type` metadata key supplied when the file was created, with a default of `text/html`. The file contents will be served only if the file has not expired.

This endpoint requires the `read_transient_file` capability.


## GET /wc/file/transient/&lt;name&gt;

This is the public unauthenticated endpoint that allows retrieving the raw contents of the file given the file name. The file contents will be served only if the file was created with `is_public` set to `true` **and** it has not expired.

 As in the case of [the equivalent authenticated enpoint](#get-wp-jsonwcv3filestransientid), the value used for the `Content-Type` HTTP header is taken from the `content-type` metadata key supplied when the file was created, with a default of `text/html`.

 As this is a public endpoint that doesn't handle authentication, no capabilities are checked.
