/**
 * External dependencies
 */
import { DispatchFromMap } from '@automattic/data-stores';

/**
 * Internal dependencies
 */
import { CrudActions, CrudSelectors } from '../crud/types';
import { Product, ProductQuery, ReadOnlyProperties } from '../products/types';

export type ProductVariationAttribute = {
	id: number;
	name: string;
	slug: string;
	option: string;
};

/**
 * Product variation - Image properties
 */
export interface ProductVariationImage {
	/**
	 * Image ID.
	 */
	id: number;
	/**
	 * The date the image was created, in the site's timezone.
	 */
	readonly date_created?: string;
	/**
	 * The date the image was created, as GMT.
	 */
	readonly date_created_gmt?: string;
	/**
	 * The date the image was last modified, in the site's timezone.
	 */
	readonly date_modified?: string;
	/**
	 * The date the image was last modified, as GMT.
	 */
	readonly date_modified_gmt?: string;
	/**
	 * Image URL.
	 */
	src: string;
	/**
	 * 	Image name.
	 */
	name: string;
	/**
	 * 	Image alternative text.
	 */
	alt: string;
}

export type ProductVariation = Omit<
	Product,
	'slug' | 'attributes' | 'images' | 'manage_stock'
> &
	Pick< Product, 'id' > & {
		attributes: ProductVariationAttribute[];
		/**
		 * Variation image data.
		 */
		image?: ProductVariationImage;
		/**
		 * Stock management at variation level. It can have a
		 * 'parent' value if the parent product is managing
		 * the stock at the time the variation was created.
		 *
		 * @default false
		 */
		manage_stock: boolean | 'parent';
		/**
		 * The product id this variation belongs to
		 */
		parent_id: number;
	};

export type PartialProductVariation = Partial< ProductVariation > &
	Pick< ProductVariation, 'id' >;

type Query = Omit< ProductQuery, 'name' >;

type MutableProperties = Partial<
	Omit< ProductVariation, ReadOnlyProperties >
>;

type ProductVariationActions = CrudActions<
	'ProductVariation',
	ProductVariation,
	MutableProperties
>;

export type ProductVariationSelectors = CrudSelectors<
	'ProductVariation',
	'ProductVariations',
	ProductVariation,
	Query,
	MutableProperties
>;

export type ActionDispatchers = DispatchFromMap< ProductVariationActions >;

export type GenerateRequest = {
	delete?: boolean;
	default_values?: Partial< ProductVariation >;
};

export type BatchUpdateRequest = {
	create?: Partial< Omit< ProductVariation, 'id' > >[];
	update?: ( Pick< ProductVariation, 'id' > &
		Partial< Omit< ProductVariation, 'id' > > )[];
	delete?: ProductVariation[ 'id' ][];
};

export type BatchUpdateResponse = {
	create?: ProductVariation[];
	update?: ProductVariation[];
	delete?: ProductVariation[];
};
