/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { Fill } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './example-fill.scss';

const MyHeaderItem = () => (
	<Fill name="woocommerce_homescreen_header_banner_item">
		<div className="woocommerce-experiments-placeholder-slotfill">
			<div className="placeholder-slotfill-content">
				Slotfill goes in here!
			</div>
		</div>
	</Fill>
);

registerPlugin( 'my-extension', {
	render: MyHeaderItem,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	scope: 'woocommerce-admin',
} );
