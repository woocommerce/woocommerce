/**
 * External dependencies
 */
import type { SupportedLayouts, ViewTable } from '@wordpress/dataviews';

export const DEFAULT_PRODUCT_TABLE_TITLE_FIELD = 'name';
export const DEFAULT_PRODUCT_TABLE_MEDIA_FIELD = 'images';

export const DEFAULT_PRODUCT_TABLE_FIELDS = [
	'product_status',
	'sku',
	'stock',
	'price',
] as const;

export const DEFAULT_PRODUCT_TABLE_LAYOUT: NonNullable<
	SupportedLayouts[ 'table' ]
> = {
	fields: [ ...DEFAULT_PRODUCT_TABLE_FIELDS ],
	layout: {
		styles: {
			price: {
				align: 'end',
			},
		},
	},
};

export const DEFAULT_PRODUCT_TABLE_VIEW: ViewTable = {
	type: 'table',
	filters: [],
	perPage: 20,
	mediaField: DEFAULT_PRODUCT_TABLE_MEDIA_FIELD,
	titleField: DEFAULT_PRODUCT_TABLE_TITLE_FIELD,
	fields: [ ...DEFAULT_PRODUCT_TABLE_FIELDS ],
	showLevels: false,
	showMedia: true,
	layout: DEFAULT_PRODUCT_TABLE_LAYOUT.layout,
};
