/**
 * External dependencies
 */
import type {
	Product,
	ProductStatus as WooProductStatus,
} from '@woocommerce/data';

export type ProductEntityRecord = Omit< Product, 'categories' | 'tags' > & {
	categories: Array< {
		id: number;
		name?: string;
		image?: {
			src?: string;
			alt?: string;
		};
	} >;
	tags: Array< {
		id: number;
		name?: string;
	} >;
	cross_sell_ids?: number[];
	upsell_ids?: number[];
	date_on_sale_from?: string | null;
	date_on_sale_to?: string | null;
	seo_title?: string;
	seo_description?: string;
	visible_in_pos?: boolean;
	images: Array< {
		alt: string;
		date_created: string;
		date_created_gmt: string;
		date_modified: string;
		date_modified_gmt: string;
		id: number;
		name: string;
		src: string;
		thumbnail: string;
	} >;
};

export type ProductStatus = WooProductStatus;

export type SettingsEntityRecord = {
	values?: {
		woocommerce_dimension_unit?: string;
		woocommerce_weight_unit?: string;
	};
};
