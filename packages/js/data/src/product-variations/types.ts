/**
 * External dependencies
 */
import { DispatchFromMap } from '@automattic/data-stores';

/**
 * Internal dependencies
 */
import { CrudActions, CrudSelectors } from '../crud/types';
import { Product, ProductQuery, ReadOnlyProperties } from '../products/types';

export type ProductVariation = Omit< Product, 'name' | 'slug' >;

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
