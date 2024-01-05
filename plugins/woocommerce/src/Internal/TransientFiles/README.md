# Transient Files Engine

## Index

* [Summary](#summary)
* [Contents of the engine](#contents-of-the-engine)
* [The code API](#the-code-api)
* [Hooks](#hooks)
* [The HTTP file serving endpoint](#the-http-file-serving-endpoint)


## Summary

The WooCommerce transient files engine allows for the creation of transient files. We define a _transient file_ as a physical file (in the server filesystem) that:

* Has a random name.
* Can have any arbitrary content.
* Lives in a dedicated subdirectory inside the WordPress `uploads` folder (customizable via [hook](#hooks)).
* Has an expiration date.
* Its contents can be retrieved via an [unauthenticated HTTP endpoint](#the-http-file-serving-endpoint).

As of now, the only way to create a transient file is by directly passing its contents to the [`create_transient_file`](#create_transient_file) method in a parameter. Additional mechanisms (e.g. passing the path of an already existing file) might be added in the future (pull requests welcome, as usual!)

Expiration dates don't include the time of the day. A file is considered to have expired after 23:59:59 of its expiration date.

A mechanism is in place too to periodically delete expired files using Action Scheduler.


## Contents of the engine

The transient files engine consists of one single class, `TransientFilesEngine`, all the files live in the `src/Internal/TransientFiles` directory.


## The code API

The `TransientFilesEngine` provides the following public methods and hooks, please refer to the documentation comment of each method for further details:


### create_transient_file

This is the method used to create a transient file by passing the file contents and an expiration date. The expiration date can be a string in `Y-m-d` format or a number representing a timestamp; when a timestamp is passed, the time of the day part is ignored and only the date part is used.

Here's how you can quickly test this method from the command line:

```bash
wp eval "echo wc_get_container()->get(\Automattic\WooCommerce\Internal\TransientFiles\TransientFilesEngine::class)->create_transient_file('foobar', '2023-12-31');"
```

The minimum allowed expiration date is the one for the current day. The expiration date is considered GMT and is always compared to the current date once converted to GMT too.

The method will return the randomly generated name of the physical transient file created (file only, without the path) prepended by a hexadecimal representation of the expiration date (3 digits for the year, 1 for the month, 2 for the day). For example, for a file named `0102030405060708090a0b0c0d0e0f00` and an expiration date of `2023-12-31`, the returned value will be `7e7c1f0102030405060708090a0b0c0d0e0f00`.


### get_transient_files_directory

Gets the base directory where existing transient files are stored. The actual directory for each file is the combination of the base directory and the expiration date of the file, formatted as `Y-m-d`.

The default base directory is `woocommerce_transient_files` inside the WordPress uploads directory. A different directory specified via a dedicated [hook](#hooks).

If the default base directory doesn't exist, this method will create it. If the dedicated hook is used to specify a different directory that doesn't exist, an exception will be thrown; it's the responsibility of either the caller or the code that sets the hook to ensure that the specified custom directory exists.


### get_transient_file_path

Returns the full physical path of a transient file given the name returned by `create_transient_file`.  If no file can be located with the supplied name, `null` will be returned.

For example, if you run the example code shown in `create_transient_file` and assuming the file name in the example, you can get the full file path running:

```bash
wp eval "echo wc_get_container()->get(\Automattic\WooCommerce\Internal\TransientFiles\TransientFilesEngine::class)->get_transient_file_path('7e7c1f0102030405060708090a0b0c0d0e0f00');"
```

and you'll get:

```
<WordPress directory>/wp-content/uploads/woocommerce_transient_files/2023-12-31/0102030405060708090a0b0c0d0e0f00
```


### file_has_expired

Given a full file path returned by `get_transient_file_path`, returns a boolean indicating if the file is past its expiration date. This is determined by comparing the expiration date of the file (determined by the directory where it's located) and the current date.


### delete_transient_file

Deletes a transient file given its name as returned by `create_transient_file`. Directly deleting the file whose path is returned by `get_transient_file_path` using filesystem functions is allowed too.


### delete_expired_files

Immediately (bypassing the scheduled action) deletes a limited amount of expired files, starting with the ones having the earlier expiration date. Subdirectories inside the base directory (those being formatted as `Y-m-d`) that become empty are deleted too.


### expired_files_cleanup_is_scheduled, schedule_expired_files_cleanup, unschedule_expired_files_cleanup

These methods control the scheduled cleanup of expired files. The scheduling works as follows:

1. `schedule_expired_files_cleanup` will schedule a cleanup operation (with `delete_expired_files`) to run immediately.
2. If the cleanup operation actually deletes at least one file, then the action is rescheduled to run again immediately.
3. When no files are deleted, the action is rescheduled for 24h later (this interval can be changed with a [hook](#hooks)).

A dedicated tool is provided in the WooCommerce debug tools page to trigger or cancel the cleanup scheduled action.


### Hooks

The `TransientFilesEngine` provides the following hooks:

* `woocommerce_transient_files_directory`: Filter that allows to change the base directory where transient files are stored. This affects both the creation of new transient files and the retrieval of existing files.

* `woocommerce_delete_expired_transient_files_interval`: Filter that allows to change the interval between cleanup scheduled actions when no files are left to delete. The default is 24h.

* `woocommerce_transient_file_contents_served`: Action triggered when the content of a transient file is served either via the JSON REST API endpoint or the public HTTP endpoint.


## The HTTP file serving endpoint

The engine provides an HTTP endpoint that will serve the contents of a non expired transient file with a content type header of `text/html`, the endpoint is `GET /wc/file/transient/<name>` where `<name>` is the file name as returned by `create_transient_file`. This endpoint is unauthenticated, no credentials will be checked whatsoever and the file will be served as soon as it's determined that it exists and it has not expired.
