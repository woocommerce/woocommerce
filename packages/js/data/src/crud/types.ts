/**
 * Internal dependencies
 */
import { BaseQueryParams, WPDataSelector, WPDataSelectors } from '../types';
import {
	getItem,
	getItemError,
	getItems,
	getItemsError,
	getItemCreateError,
	getItemDeleteError,
	getItemUpdateError,
} from './selectors';

export type IdType = number | string;

export type Item = {
	id: IdType;
	[ key: string ]: unknown;
};

export type ItemQuery = BaseQueryParams & {
	[ key: string ]: unknown;
};

export type CrudActions< ResourceName, ItemConfig, MutableProperties > =
	MapActions<
		{
			create: ( query: ItemQuery ) => Item;
			update: ( query: ItemQuery ) => Item;
		},
		ResourceName,
		MutableProperties,
		ItemConfig
	> &
		MapActions<
			{
				delete: ( id: IdType ) => Item;
			},
			ResourceName,
			IdType,
			ItemConfig
		>;

export type CrudSelectors<
	ResourceName,
	PluralResourceName,
	ItemConfig,
	ItemQueryConfig,
	MutableProperties
> = MapSelectors<
	{
		'': WPDataSelector< typeof getItem >;
	},
	ResourceName,
	IdType,
	ItemConfig
> &
	MapSelectors<
		{
			Error: WPDataSelector< typeof getItemError >;
			DeleteError: WPDataSelector< typeof getItemDeleteError >;
			UpdateError: WPDataSelector< typeof getItemUpdateError >;
		},
		ResourceName,
		IdType,
		unknown
	> &
	MapSelectors<
		{
			'': WPDataSelector< typeof getItems >;
		},
		PluralResourceName,
		ItemQueryConfig,
		ItemConfig[]
	> &
	MapSelectors<
		{
			Error: WPDataSelector< typeof getItemsError >;
		},
		PluralResourceName,
		ItemQueryConfig,
		unknown
	> &
	MapSelectors<
		{
			CreateError: WPDataSelector< typeof getItemCreateError >;
		},
		PluralResourceName,
		MutableProperties,
		unknown
	> &
	WPDataSelectors;

export type MapSelectors< Type, ResourceName, ParamType, ReturnType > = {
	[ Property in keyof Type as `get${ Capitalize<
		string & ResourceName
	> }${ Capitalize< string & Property > }` ]: ( x: ParamType ) => ReturnType;
};

export type MapActions< Type, ResourceName, ParamType, ReturnType > = {
	[ Property in keyof Type as `${ Lowercase<
		string & Property
	> }${ Capitalize< string & ResourceName > }` ]: (
		x: ParamType
	) => ReturnType;
};
