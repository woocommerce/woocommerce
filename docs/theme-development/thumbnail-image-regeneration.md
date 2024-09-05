---
post_title: Thumbnail image regeneration
tags: reference
---

WooCommerce 3.3 introduced thumbnail regeneration functionality. In the past when image size settings were changed you would need to install an external plugin and then have it regenerate all WordPress image thumbnails before the changes would be visible.

The new image regeneration functionality, combined with the introduction of WooCommerce image settings in the customizer, now ensure that as you make changes to your store image settings you can preview the changes in real-time within the customizer.

## How it works

When you make changes to image sizes/aspect ratios in the customiser, or if your theme changes image sizes, WC will detect these changes and queue a background regeneration job. The 2 events which can trigger this are:

- Publishing settings in the customizer
- Switching themes

Whilst in the customizer, size changes can be previewed due to our on-the-fly image regen. These changes will not be persisted to the live site until you hit publish.

### Background jobs and BasicAuth

If your site is behind BasicAuth, both async requests and background processes will fail to complete. This is because WP Background Processing relies on the WordPress HTTP API, which requires you to attach your BasicAuth credentials to requests.

You can pass these credentials via a snippet, see:[BasicAuth documentation](https://github.com/A5hleyRich/wp-background-processing#basicauth).

### Viewing background regeneration logs

To view the logs for background image regeneration go to `WooCommerce > Status > Logs` and select the `wc-background-regeneration` log from the dropdown.

This log file will list images which have been processed and when the job was completed or cancelled.

### Cancelling a background regeneration job

As of WooCommerce 3.3.2 you will see an admin notice when background image regeneration is running. Within this notice is a link to cancel the job.

Cancelling the job will stop more thumbnails being regenerated. If image sizes do not look correct inside your catalog, you'll need to run thumbnail regeneration manually (either using our tool, or using another plugin such as [Regenerate Thumbnails](https://en-gb.wordpress.org/plugins/regenerate-thumbnails/).

### CDN plugins

Most CDN plugins listen to WordPress core hooks and upload generated thumbnails to their service once created. This will continue to function with our background image regeneration code. Generation may be slower due to uploading the images to the 3rd party service as it progresses.

## How to disable background regeneration

The `woocommerce_background_image_regeneration` filter can be used to disable background regeneration completely. Example code:

```php
add_filter( 'woocommerce_background_image_regeneration', '__return_false' );
```

Once disabled, you'll need to regenerate thumbnails manually using another tool should you change image size settings and need new thumbnails.

Alternatively, you can use the [Jetpack Photon module](https://jetpack.com/support/photon/) which can do image resizing on the fly and will be used instead of background regeneration as of WooCommerce 3.3.2.

## Using Jetpack Photon instead

[Jetpack](https://jetpack.com/) is a plugin by Automattic, makers of WordPress.com. It gives your self-hosted WordPress site some of the functionality that is available to WordPress.com-hosted sites.

[The Photon module](https://jetpack.com/support/photon/) makes the images on your site be served from WordPress.com's global content delivery network (CDN) which should speed up the loading of images. 

Photon can create thumbnails on the fly which means you'll never need to use our background image regeneration functionality.
