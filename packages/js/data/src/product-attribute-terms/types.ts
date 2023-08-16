/**
 * External dependencies
 */
import { DispatchFromMap } from '@automattic/data-stores';

/**
 * Internal dependencies
 */
import { CrudActions, CrudSelectors, MapResolveSelectors } from '../crud/types';

export type ProductAttributeTerm = {
	id: number;
	slug: string;
	name: string;
	description: string;
	menu_order: number;
	count: number;
};

type Query = {
	context: string;
	page: number;
	per_page: number;
	search: string;
	exclude: number[];
	include: number[];
	offset: number;
	order: string;
	orderby: string;
	hide_empty: boolean;
	product: number;
	slug: string;
};

type ReadOnlyProperties = 'id' | 'count';

type MutableProperties = Partial<
	Omit< ProductAttributeTerm, ReadOnlyProperties >
>;

type ProductAttributeTermActions = CrudActions<
	'ProductAttributeTerm',
	ProductAttributeTerm & { attribute_id: string | number },
	MutableProperties,
	'name'
>;

export type ProductAttributeTermsSelectors = CrudSelectors<
	'ProductAttributeTerm',
	'ProductAttributeTerms',
	ProductAttributeTerm,
	Partial< Query > & { attribute_id: string | number },
	MutableProperties
>;

export type ProductAttributeTermsResolveSelectors =
	MapResolveSelectors< ProductAttributeTermsSelectors >;

export type ActionDispatchers = DispatchFromMap< ProductAttributeTermActions >;
