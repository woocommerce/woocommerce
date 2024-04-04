# Block Script Assets <!-- omit in toc -->

## Table of contents <!-- omit in toc -->

-   [When are assets needed?](#when-are-assets-needed)
-   [Choosing Handles for Assets (and scripts in general)](#choosing-handles-for-assets-and-scripts-in-general)
-   [Using the `AbstractBlock` class](#using-the-abstractblock-class)
    -   [AbstractBlock::render](#abstractblockrender)
    -   [AbstractBlock::enqueue_editor_assets](#abstractblockenqueue_editor_assets)
    -   [AbstractBlock::enqueue_assets](#abstractblockenqueue_assets)
    -   [AbstractBlock::enqueue_data](#abstractblockenqueue_data)
-   [woocommerce_shared_settings deprecated filter](#woocommerce_shared_settings-deprecated-filter)

[Block Types](https://github.com/woocommerce/woocommerce-gutenberg-products-block/tree/trunk/src/BlockTypes) are often responsible for enqueuing script assets that make blocks functional on both the front-end and within the editor. Additionally, some block scripts require extra data from the server and thus have extra dependencies that need to be loaded.

For performance reasons the blocks plugin ensures assets and asset data is made available only as needed.

## When are assets needed?

Assets are needed when we know a block will be rendered.

In the context of [Block Types](https://github.com/woocommerce/woocommerce-gutenberg-products-block/tree/trunk/src/BlockTypes), assets and asset data is enqueued within the block `render()` method.

In an admin editor context we must also ensure asset _data_ is available when the `enqueue_block_editor_assets` hook is fired. That is because block scripts are enqueued ready for the Block Inserter, but the block may not be rendered.

Note: `enqueue_block_editor_assets` fires regardless of whether or not a block has been rendered in the editor context, so unless handled correctly, block data may be loaded twice. The `AbstractBlock` class below handles this for you, or you can track whether or not assets have been loaded already with a class variable.

## Choosing Handles for Assets (and scripts in general)

When creating a script or style assets, the following rules should be followed to keep asset names consistent, and to avoid
conflicts with other similarly named scripts.

1. All asset handles should have a `wc-` prefix.
2. If the asset handle is for a Block (in editor context) use the `-block` suffix.
3. If the asset handle is for a Block (in frontend context) use the `-block-frontend` suffix.
4. If the asset is for any other script being consumed or enqueued by the blocks plugin, use the `wc-blocks-` prefix.

Some examples:

1. A Block called 'featured-product' would have asset handles named: `wc-featured-product-block` and `wc-featured-product-block-frontend`.
2. A script being used by a block, for example, named 'tag-manager' would have the handle: `wc-blocks-tag-manager`.

These rules also apply to styles.

## Using the `AbstractBlock` class

The [`AbstractBlock` class](https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/src/BlockTypes/AbstractBlock.php) has some helper methods to make asset management easier. Most Block Types in this plugin extend this class.

### AbstractBlock::render

The default `render` method will call `AbstractBlock::enqueue_assets`—this ensures both scripts and asset data is ready once the block is rendered.

### AbstractBlock::enqueue_editor_assets

This method is hooked into `enqueue_block_editor_assets`. If assets have not already loaded, it calls `AbstractBlock::enqueue_data` to ensure data dependencies exist ready for the Block Editor.

### AbstractBlock::enqueue_assets

If assets have not already been loaded, this method calls `enqueue_data` and `enqueue_scripts`.

Classes which extend `AbstractBlock` should override `enqueue_data` and `enqueue_scripts` to handle their specific assets.

### AbstractBlock::enqueue_data

If extending `AbstractBlock` this method can be overridden. It should enqueue/register asset data used by the block.

```php
protected function enqueue_data( array $attributes = [] ) {
    $data_registry = Automattic\WooCommerce\Blocks\Package::container()->get(
        Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry::class
    );
    $data_registry->add( 'some-asset-data', 'data-value' );
}
```

## woocommerce_shared_settings deprecated filter

This filter was used as a workaround. Currently the best way to achieve data registration comes from using AssetsDataRegistry:

```php
	Automattic\WooCommerce\Blocks\Package::container()
		->get( Automattic\WooCommerce\Blocks\Assets\AssetDataRegistry::class )
		->add( $key, $value );
```

On the client side the value will be available via:

```js
wc.wcSettings.getSetting( 'key' );
```

<!-- FEEDBACK -->

---

[We're hiring!](woocommerce.com/careers/) Come work with us!

🐞 Found a mistake, or have a suggestion? [Leave feedback about this document here.](https://github.com/woocommerce/woocommerce-blocks/issues/new?assignees=&labels=type%3A+documentation&template=--doc-feedback.md&title=Feedback%20on%20./docs/contributors/block-assets.md)

<!-- /FEEDBACK -->

