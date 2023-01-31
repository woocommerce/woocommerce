/**
 * External dependencies
 */
import { DispatchFromMap } from '@automattic/data-stores';

/**
 * Internal dependencies
 */
import { CrudActions, CrudSelectors } from '../crud/types';
import { BaseQueryParams } from '../types';

export type ProductCategoryImage = {
	id: number;
	date_created: string;
	date_created_gmt: string;
	date_modified: string;
	date_modified_gmt: string;
	src: string;
	name: string;
	alt: string;
};

export type ProductCategory = {
	id: number;
	name: string;
	slug: string;
	parent: number;
	description: string;
	display: string;
	image: ProductCategoryImage;
	menu_order: number;
	count: number;
};

type Query = Partial< BaseQueryParams< keyof ProductCategory > > & {
	hide_empty?: boolean;
	slug?: string;
	product?: number;
};

type ReadOnlyProperties = 'id' | 'count';

type MutableProperties = Omit< ProductCategory, ReadOnlyProperties >;

type ProductCategoryActions = CrudActions<
	'ProductCategory',
	ProductCategory,
	MutableProperties,
	'name'
>;

export type ProductCategorySelectors = CrudSelectors<
	'ProductCategory',
	'ProductCategories',
	ProductCategory,
	Query,
	MutableProperties
>;

export type ActionDispatchers = DispatchFromMap< ProductCategoryActions >;
