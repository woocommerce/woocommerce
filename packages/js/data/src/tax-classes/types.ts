/**
 * External dependencies
 */
import { DispatchFromMap } from '@automattic/data-stores';

/**
 * Internal dependencies
 */
import { CrudActions, CrudSelectors } from '../crud/types';
import { BaseQueryParams } from '../types';

/**
 * Tax class properties
 */
export interface TaxClass {
	/**
	 * Unique identifier for the resource.
	 */
	readonly slug: string;
	/**
	 * Tax class name.
	 */
	name: string;
}

type Query = BaseQueryParams< keyof TaxClass >;

type ReadOnlyProperties = 'slug';

type MutableProperties = Omit< TaxClass, ReadOnlyProperties >;

type TaxClassActions = CrudActions<
	'TaxClass',
	TaxClass,
	MutableProperties,
	'name'
>;

export type TaxClassSelectors = CrudSelectors<
	'TaxClass',
	'TaxClasses',
	TaxClass,
	Query,
	MutableProperties
>;

export type ActionDispatchers = DispatchFromMap< TaxClassActions >;
