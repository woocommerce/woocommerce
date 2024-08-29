/**
 * External dependencies
 */
import { totals } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import attributes from './attributes';
import deprecated from './deprecated';

registerBlockType( 'woocommerce/checkout-order-summary-block', {
	icon: {
		src: (
			<Icon
				icon={ totals }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	attributes,
	edit: Edit,
	save: Save,
	deprecated,
} );
