/**
 * External dependencies
 */
import { DEFAULT_COLUMNS, DEFAULT_ROWS } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { DEFAULT_PRODUCT_LIST_LAYOUT } from './base-utils';

export default {
	/**
	 * Number of columns.
	 */
	columns: {
		type: 'number',
		default: DEFAULT_COLUMNS,
	},

	/**
	 * Number of rows.
	 */
	rows: {
		type: 'number',
		default: DEFAULT_ROWS,
	},

	/**
	 * How to align cart buttons.
	 */
	alignButtons: {
		type: 'boolean',
		default: false,
	},

	/**
	 * Content visibility setting
	 */
	contentVisibility: {
		type: 'object',
		default: {
			orderBy: true,
		},
	},

	/**
	 * Order to use for the products listing.
	 */
	orderby: {
		type: 'string',
		default: 'date',
	},

	/**
	 * Layout config.
	 */
	layoutConfig: {
		type: 'array',
		default: DEFAULT_PRODUCT_LIST_LAYOUT,
	},

	/**
	 * Are we previewing?
	 */
	isPreview: {
		type: 'boolean',
		default: false,
	},
};
