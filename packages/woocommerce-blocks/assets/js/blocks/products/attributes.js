/**
 * External dependencies
 */
import { DEFAULT_COLUMNS, DEFAULT_ROWS } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { DEFAULT_PRODUCT_LIST_LAYOUT } from './base-utils';

export const defaults = {
	columns: DEFAULT_COLUMNS,
	rows: DEFAULT_ROWS,
	alignButtons: false,
	contentVisibility: {
		orderBy: true,
	},
	orderby: 'date',
	layoutConfig: DEFAULT_PRODUCT_LIST_LAYOUT,
	isPreview: false,
};

export const attributes = {
	/**
	 * Number of columns.
	 */
	columns: {
		type: 'number',
	},
	/**
	 * Number of rows.
	 */
	rows: {
		type: 'number',
	},
	/**
	 * How to align cart buttons.
	 */
	alignButtons: {
		type: 'boolean',
	},
	/**
	 * Content visibility setting
	 */
	contentVisibility: {
		type: 'object',
	},
	/**
	 * Order to use for the products listing.
	 */
	orderby: {
		type: 'string',
	},
	/**
	 * Layout config.
	 */
	layoutConfig: {
		type: 'array',
	},
	/**
	 * Are we previewing?
	 */
	isPreview: {
		type: 'boolean',
		default: false,
	},
};
