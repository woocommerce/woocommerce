/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	CANCELLED_STATUS_BLOCK,
	COMPLETED_STATUS_BLOCK,
	DEFAULT_STATUS_BLOCK,
	FAILED_STATUS_BLOCK,
	REFUNDED_STATUS_BLOCK,
} from './inner-blocks';

export const attributes = {
	currentView: {
		type: 'string',
		default: DEFAULT_STATUS_BLOCK,
		source: 'readonly',
	},
	editorViews: {
		type: 'object',
		default: [
			{
				view: DEFAULT_STATUS_BLOCK,
				label: __( 'Default', 'woocommerce' ),
				icon: 'info',
			},
			{
				view: CANCELLED_STATUS_BLOCK,
				label: __( 'Cancelled', 'woocommerce' ),
				icon: 'info',
			},
			{
				view: REFUNDED_STATUS_BLOCK,
				label: __( 'Refunded', 'woocommerce' ),
				icon: 'info',
			},
			{
				view: COMPLETED_STATUS_BLOCK,
				label: __( 'Completed', 'woocommerce' ),
				icon: 'info',
			},
			{
				view: FAILED_STATUS_BLOCK,
				label: __( 'Failed', 'woocommerce' ),
				icon: 'info',
			},
		],
	},
};
