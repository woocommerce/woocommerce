/**
 * External dependencies
 */
import { DispatchFromMap } from '@automattic/data-stores';

/**
 * Internal dependencies
 */
import { CrudActions, CrudSelectors } from '../crud/types';

export type ProductAttribute = {
	id: number;
	slug: string;
	name: string;
	type: string;
	order_by: string;
	has_archives: boolean;
};

export type QueryProductAttribute = {
	slug: string;
	name: string;
	type: 'select' | string;
	order_by: string;
	has_archives: boolean;
	generate_slug: boolean;
	attribute_id: number;
};

type Query = {
	context?: string;
	order_by?: string;
};

type ReadOnlyProperties = 'id';

type MutableProperties = Partial<
	Omit< QueryProductAttribute, ReadOnlyProperties >
>;

export type ProductAttributeActions = CrudActions<
	'ProductAttribute',
	ProductAttribute,
	MutableProperties
>;

export type ProductAttributeSelectors = CrudSelectors<
	'ProductAttribute',
	'ProductAttributes',
	ProductAttribute,
	Query,
	MutableProperties
>;

export type ActionDispatchers = DispatchFromMap< ProductAttributeActions >;
