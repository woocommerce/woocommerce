/**
 * External dependencies
 */
import { DispatchFromMap } from '@automattic/data-stores';

/**
 * Internal dependencies
 */
import { CrudActions, CrudSelectors } from '../crud/types';

type ProductAttribute = {
	id: number;
	slug: string;
	name: string;
	type: string;
	order_by: string;
	has_archives: boolean;
};

type Query = {
	context?: string;
};

type ReadOnlyProperties = 'id';

type MutableProperties = Partial<
	Omit< ProductAttribute, ReadOnlyProperties >
>;

type ProductAttributeActions = CrudActions<
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
