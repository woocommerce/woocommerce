/**
 * External dependencies
 */
import { registerPlugin } from '@wordpress/plugins';
import { Fill } from '@wordpress/components';

/**
 * Internal dependencies
 */
import './example-fill.scss';
import { EXPERIMENTAL_WC_MARKETING_OVERVIEW_SECTION_SLOT_NAME } from './utils'

const MySection = () => (
	<Fill name={ EXPERIMENTAL_WC_MARKETING_OVERVIEW_SECTION_SLOT_NAME }>
		<div className="woocommerce-experiments-placeholder-slotfill">
			<div className="placeholder-slotfill-content">
				Slotfill goes in here!
			</div>
		</div>
	</Fill>
);

registerPlugin( 'my-extension', {
	render: MySection,
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	scope: 'woocommerce-admin',
} );
