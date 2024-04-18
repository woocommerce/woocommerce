/**
 * External dependencies
 */
import { DispatchFromMap } from '@automattic/data-stores';

/**
 * Internal dependencies
 */
import { CrudActions, CrudSelectors } from '../crud/types';

export type QueryProductAttribute = {
	id: number;
	slug: string;
	name: string;
	type: string;
	order_by: string;
	has_archives: boolean;
	generate_slug: boolean;
};

type Query = {
	context?: string;
	order_by?: string;
};

type ReadOnlyProperties = 'id';

type MutableProperties = Partial<
	Omit< QueryProductAttribute, ReadOnlyProperties >
>;

type ProductAttributeActions = CrudActions<
	'ProductAttribute',
	QueryProductAttribute,
	MutableProperties
>;

export type ProductAttributeSelectors = CrudSelectors<
	'ProductAttribute',
	'ProductAttributes',
	QueryProductAttribute,
	Query,
	MutableProperties
>;

export type ActionDispatchers = DispatchFromMap< ProductAttributeActions >;

/*
 * Add a the second `options` parameter to the `createProductAttribute` action dispatcher
 * todo: do this in a more generic way
 */
export interface CustomActionDispatchers extends ActionDispatchers {
	createProductAttribute: (
		x: Partial< Omit< QueryProductAttribute, 'id' > >,
		options?: {
			optimisticQueryUpdate: Partial< QueryProductAttribute >;
		}
	) => Promise< QueryProductAttribute >;
}
