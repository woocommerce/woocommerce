/**
 * External dependencies
 */
import type { View } from '@wordpress/dataviews';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../fields/types';
import {
	DEFAULT_PRODUCT_TABLE_LAYOUT,
	DEFAULT_PRODUCT_TABLE_VIEW,
} from './layouts';

export const PAGE_SIZE = 20;

export const EMPTY_ARRAY: ProductEntityRecord[] = [];

export const DEFAULT_LAYOUTS = {
	table: DEFAULT_PRODUCT_TABLE_LAYOUT,
};

export const DEFAULT_VIEW: View = {
	...DEFAULT_PRODUCT_TABLE_VIEW,
	page: 1,
};

export const PRODUCT_LIST_TAB_VALUES = [
	'all',
	'publish',
	'draft',
	'trash',
] as const;

export type StatusTab = ( typeof PRODUCT_LIST_TAB_VALUES )[ number ];

export const PRODUCT_LIST_TABS: Array< {
	value: StatusTab;
	label: string;
} > = [
	{
		value: 'all',
		label: __( 'All', 'woocommerce' ),
	},
	{
		value: 'publish',
		label: __( 'Published', 'woocommerce' ),
	},
	{
		value: 'draft',
		label: __( 'Draft', 'woocommerce' ),
	},
	{
		value: 'trash',
		label: __( 'Trash', 'woocommerce' ),
	},
];
