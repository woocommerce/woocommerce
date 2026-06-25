/**
 * External dependencies
 */
import type {
	Product,
	ProductStatus as WooProductStatus,
} from '@woocommerce/data';

export type ProductEntityAttribute = {
	id: number;
	name: string;
	slug: string;
	position: number;
	visible: boolean;
	variation: boolean;
	options: string[];
};

export type ProductEntityDefaultAttribute = {
	id: number;
	name: string;
	option: string;
};

export type ProductEntityRecord = Omit<
	Product,
	'attributes' | 'categories' | 'default_attributes' | 'tags'
> & {
	attributes: ProductEntityAttribute[];
	default_attributes: ProductEntityDefaultAttribute[];
	cost_of_goods_sold?: {
		values?: Array< {
			defined_value?: number | string | null;
			effective_value?: number | string | null;
		} >;
		defined_value_is_additive?: boolean;
		total_value?: number | string | null;
	};
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
	brands?: Array< {
		id: number;
		name?: string;
		slug?: string;
	} >;
	global_unique_id?: string;
	cross_sell_ids?: number[];
	upsell_ids?: number[];
	grouped_products?: number[];
	date_on_sale_from?: string | null;
	date_on_sale_to?: string | null;
	parent_id?: number;
	_embedded?: {
		variations?: ProductEntityRecord[];
	};
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
