/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { Fill } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './example-fill.scss';

const MyFooterItem = () => (
	<Fill name="experimental_woocommerce_tasklist_footer_item">
		<div className="woocommerce-experiments-placeholder-slotfill">
			<div className="placeholder-slotfill-content">
				Slotfill goes in here!
			</div>
		</div>
	</Fill>
);

registerPlugin( 'my-tasklist-footer-extension', {
	render: MyFooterItem,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	scope: 'woocommerce-admin',
} );
