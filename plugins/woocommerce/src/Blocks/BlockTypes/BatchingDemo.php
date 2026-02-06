<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Blocks\BlockTypes;

/**
 * BatchingDemo class.
 *
 * Demo block to demonstrate the mutation batcher. For development/review only.
 */
class BatchingDemo extends AbstractBlock {

	/**
	 * Block name.
	 *
	 * @var string
	 */
	protected $block_name = 'batching-demo';

	/**
	 * Get the frontend script handle for this block type.
	 * We don't need a frontend script because the viewScriptModule is loaded via block.json.
	 *
	 * @param string $key Data to get, or default to everything.
	 * @return null
	 */
	protected function get_block_type_script( $key = null ) {
		return null;
	}

	/**
	 * Get the frontend style handle for this block type.
	 * No styles needed for this demo block.
	 *
	 * @return null
	 */
	protected function get_block_type_style() {
		return null;
	}

	/**
	 * Include and render the block.
	 *
	 * @param array    $attributes Block attributes. Default empty array.
	 * @param string   $content    Block content. Default empty string.
	 * @param WP_Block $block      Block instance.
	 * @return string Rendered block type output.
	 */
	protected function render( $attributes, $content, $block ) {
		// Get some real product IDs from the store.
		$products = wc_get_products(
			array(
				'status' => 'publish',
				'limit'  => 10,
				'type'   => 'simple', // Simple products are easiest to add to cart.
				'return' => 'ids',
			)
		);

		// Fall back to empty array if no products found.
		$product_ids = ! empty( $products ) ? array_values( $products ) : array();

		$context = array(
			'isRunning'  => false,
			'lastDemo'   => '',
			'log'        => array(),
			'notices'    => array(),
			'productIds' => $product_ids,
		);

		$context_json = wp_interactivity_data_wp_context( $context );

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		return <<<HTML
<div
	class="wc-block-batching-demo"
	data-wp-interactive="woocommerce/batching-demo"
	$context_json
	style="
		font-family: system-ui, -apple-system, sans-serif;
		padding: 20px;
		border: 2px solid #7f54b3;
		border-radius: 8px;
		margin: 20px 0;
		background: #f8f4fb;
	"
>
	<h3 style="margin-top: 0; color: #7f54b3;">🧪 Mutation Batcher Demo</h3>

	<p style="color: #666; font-size: 14px;">
		Click the buttons below and watch the Network tab (filter by "batch") to see how
		synchronous calls get batched into single requests.
	</p>

	<div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 20px;">
		<button
			data-wp-on--click="actions.syncCallsDemo"
			data-wp-bind--disabled="context.isRunning"
			style="
				background: #7f54b3;
				color: white;
				border: none;
				padding: 10px 16px;
				border-radius: 4px;
				cursor: pointer;
				font-size: 14px;
			"
		>
			Sync Calls (→ 1 batch)
		</button>

		<button
			data-wp-on--click="actions.asyncCallsDemo"
			data-wp-bind--disabled="context.isRunning"
			style="
				background: #7f54b3;
				color: white;
				border: none;
				padding: 10px 16px;
				border-radius: 4px;
				cursor: pointer;
				font-size: 14px;
			"
		>
			Async Calls (→ 3 batches)
		</button>

		<button
			data-wp-on--click="actions.mixedCallsDemo"
			data-wp-bind--disabled="context.isRunning"
			style="
				background: #7f54b3;
				color: white;
				border: none;
				padding: 10px 16px;
				border-radius: 4px;
				cursor: pointer;
				font-size: 14px;
			"
		>
			Mixed Calls
		</button>

		<button
			data-wp-on--click="actions.wishlistDemo"
			data-wp-bind--disabled="context.isRunning"
			style="
				background: #2e7d32;
				color: white;
				border: none;
				padding: 10px 16px;
				border-radius: 4px;
				cursor: pointer;
				font-size: 14px;
			"
		>
			Wishlist "Add All" (5 items → 1 batch)
		</button>

		<button
			data-wp-on--click="actions.extensionsDemo"
			data-wp-bind--disabled="context.isRunning"
			style="
				background: #2e7d32;
				color: white;
				border: none;
				padding: 10px 16px;
				border-radius: 4px;
				cursor: pointer;
				font-size: 14px;
			"
		>
			Multi-Extension (3 sources → 1 batch)
		</button>

		<button
			data-wp-on--click="actions.failureDemo"
			data-wp-bind--disabled="context.isRunning"
			style="
				background: #c62828;
				color: white;
				border: none;
				padding: 10px 16px;
				border-radius: 4px;
				cursor: pointer;
				font-size: 14px;
			"
		>
			Failure Handling (invalid ID in batch)
		</button>

		<button
			data-wp-on--click="actions.clearLog"
			style="
				background: #666;
				color: white;
				border: none;
				padding: 10px 16px;
				border-radius: 4px;
				cursor: pointer;
				font-size: 14px;
			"
		>
			Clear
		</button>
	</div>

	<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
		<span
			data-wp-bind--hidden="!context.isRunning"
			style="color: #7f54b3;"
		>
			⏳ Running...
		</span>
		<span
			data-wp-bind--hidden="!context.lastDemo"
			data-wp-text="context.lastDemo"
			style="
				background: #7f54b3;
				color: white;
				padding: 4px 8px;
				border-radius: 4px;
				font-size: 12px;
			"
		></span>
	</div>

	<pre
		data-wp-text="state.logText"
		style="
			background: #1e1e1e;
			color: #d4d4d4;
			padding: 16px;
			border-radius: 4px;
			font-size: 13px;
			line-height: 1.5;
			overflow-x: auto;
			min-height: 200px;
			white-space: pre-wrap;
			margin: 0;
		"
	></pre>

	<p style="color: #666; font-size: 12px; margin-top: 10px; margin-bottom: 0;">
		💡 <strong>Why microtick batching?</strong> The batcher uses <code>queueMicrotask()</code>,
		not a time window. All synchronous calls in the same JS task get batched together.
		User clicks are separate events (separate batches), but extensions responding to
		the same event batch automatically.
	</p>
</div>
HTML;
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
