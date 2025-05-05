/**
 * External dependencies
 */
import { DispatchFromMap } from '@automattic/data-stores';

/**
 * Internal dependencies
 */
import { CrudActions, CrudSelectors } from '../crud/types';

export type ProductAttributeTermQuery = {
	attribute_id?: number;
	exclude?: number[];
	include?: number[];
	offset?: number;
	order?: string;
};

export type ProductAttributeTerm = {
	id: number;
	slug: string;
	name: string;
	description: string;
	menu_order: number;
	count: number;
	attribute_id?: number;
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
	attribute_id: number;
};

type ReadOnlyProperties = 'id' | 'count';

type MutableProperties = Partial<
	Omit< ProductAttributeTerm, ReadOnlyProperties >
>;

export type ProductAttributeTermActions = CrudActions<
	'ProductAttributeTerm',
	ProductAttributeTerm,
	MutableProperties
>;

export type ProductAttributeTermsSelectors = CrudSelectors<
	'ProductAttributeTerm',
	'ProductAttributeTerms',
	ProductAttributeTerm,
	Partial< Query >,
	MutableProperties
>;

export type ActionDispatchers = DispatchFromMap< ProductAttributeTermActions >;
