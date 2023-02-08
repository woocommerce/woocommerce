/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { __experimentalWooProductFieldItem as WooProductFieldItem } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import './style.scss';

const PLUGIN_ID = 'woocommerce-beta-tester';

const SlotFillViewer = () => {
	return (
		<WooProductFieldItem
			id="slot-fill-viewer-name"
			sections={ [ { name: 'tab/general/details', order: 1 } ] }
			pluginId={ PLUGIN_ID }
		>
			<div className="woocommerce-slot-fill-viewer-fill">
				<a
					href={ WooProductFieldItem.docUrl }
					className="woocommerce-slot-fill-viewer-fill__label"
					target="_blank"
					rel="noreferrer"
				>
					WooProductFieldItem
				</a>
				<div className="woocommerce-slot-fill-viewer-fill__wrapper" />
			</div>
		</WooProductFieldItem>
	);
};

registerPlugin( 'wc-slot-fill-viewer', {
	// @ts-expect-error 'scope' does exist. @types/wordpress__plugins is outdated.
	scope: 'woocommerce-product-editor',
	render: SlotFillViewer,
} );
