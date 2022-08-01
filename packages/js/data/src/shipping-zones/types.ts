/**
 * External dependencies
 */
import { DispatchFromMap } from '@automattic/data-stores';

/**
 * Internal dependencies
 */
import { CrudActions, CrudSelectors } from '../crud/types';

type ShippingZone = {
	id: number;
	name: string;
	order: number;
};

type ReadOnlyProperties = 'id';

type MutableProperties = Omit< ShippingZone, ReadOnlyProperties >;

type ShippingZonesActions = CrudActions<
	'ShippingZone',
	ShippingZone,
	MutableProperties,
	'name'
>;

export type ShippingZonesSelectors = CrudSelectors<
	'ShippingZone',
	'ShippingZones',
	ShippingZone,
	undefined,
	MutableProperties
>;

export type ActionDispatchers = DispatchFromMap< ShippingZonesActions >;
