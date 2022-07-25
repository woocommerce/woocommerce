/**
 * External dependencies
 */
import { DispatchFromMap } from '@automattic/data-stores';

/**
 * Internal dependencies
 */
import { CrudActions, CrudSelectors } from '../crud/types';

type ProductTag = {
	id: number;
	slug: string;
	name: string;
	type: string;
	description: string;
	count: number;
};

type Query = {
	context?: string;
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

type MutableProperties = Partial< Omit< ProductTag, ReadOnlyProperties > >;

type ProductTagActions = CrudActions<
	'ProductTag',
	ProductTag,
	MutableProperties,
	'name'
>;

export type ProductTagSelectors = CrudSelectors<
	'ProductTag',
	'ProductTags',
	ProductTag,
	Query,
	MutableProperties
>;

export type ActionDispatchers = DispatchFromMap< ProductTagActions >;
