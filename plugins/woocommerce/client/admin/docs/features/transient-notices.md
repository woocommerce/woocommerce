# WooCommerce Transient Notices

Transient notices provide a way to display snackbar notices from the server.  This is useful for displaying temporary messages to users based on a recent PHP based event.

### Adding a notice

Adding a notice will be display the notice the next time the page is loaded and reaches the `admin_enqueue_scripts` hook.  This means that if a notice is added prior to this hook (for example, on `admin_init`) it will be displayed on the current page load.

If a redirect occurs or the `admin_enqueue_scripts` hook is not reached, the notice will remain in the queue until it is shown on the next page load.

This method accepts an array with the following properties:

* `id`      => (string) Unique ID for the notice. Required.
* `status`  => (string) info|error|success
* `content` => (string) Content to be shown for the notice. Required.
* `options` => (array) Array of options to be passed to the notice component. See https://developer.wordpress.org/block-editor/reference-guides/data/data-core-notices/#createNotice for available options.
* `user_id` => (int|null) User ID to show the notice to.

```php
\Automattic\WooCommerce\Admin\Features\TransientNotices::add(
    array(
        'id'      => 'my-notice',
        'status'  => 'success',
        'content' => 'Some information to display to the user.',
        'options' => array(
            'actions' => array(
                array(
                    'label' => 'Click me',
                    'url'   => 'http://wordpress.com',
                )
            )
        )
    )
);
```

### Removing a notice

Removing a notice can be done prior to the `admin_enqueue_scripts` hook being run.  To remove a notice, simply pass the ID of the notice you no longer want to show.

* `id` - (string) The ID of the notice to remove. Required.

```php
\Automattic\WooCommerce\Admin\Features\TransientNotices::remove( 'my-notice' );
```
