/**
 * External dependencies
 */
import { DispatchFromMap } from '@automattic/data-stores';

/**
 * Internal dependencies
 */
import { CrudActions, CrudSelectors } from '../crud/types';
import { BaseQueryParams } from '../types';

export type ProductShippingClass = {
	id: number;
	slug: string;
	name: string;
	description: string;
	count: number;
};

type Query = BaseQueryParams< keyof ProductShippingClass > & {
	context?: string;
	hide_empty?: boolean;
	slug?: string;
	product?: number;
};

type ReadOnlyProperties = 'id';

type MutableProperties = Omit< ProductShippingClass, ReadOnlyProperties >;

type ProductShippingClassActions = CrudActions<
	'ProductShippingClass',
	ProductShippingClass,
	MutableProperties,
	'name'
>;

export type ProductShippingClassSelectors = CrudSelectors<
	'ProductShippingClass',
	'ProductShippingClasses',
	ProductShippingClass,
	Query,
	MutableProperties
>;

export type ActionDispatchers = DispatchFromMap< ProductShippingClassActions >;
