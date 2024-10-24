/**
 * Internal dependencies
 */
import { BaseQueryParams, WPDataSelector, WPDataSelectors } from '../types';
import {
	getItem,
	getItemError,
	getItems,
	getItemsTotalCount,
	getItemsError,
	getItemCreateError,
	getItemDeleteError,
	getItemUpdateError,
} from './selectors';

export type IdType = number | string;

export type IdQuery =
	| IdType
	| {
			id: IdType;
			[ key: string ]: IdType;
	  };

export type Item = {
	id: IdType;
	[ key: string ]: unknown;
};

export type ItemQuery = BaseQueryParams & {
	[ key: string ]: unknown;
	parent_id?: IdType;
	order_by?: string;
};

export type Params = {
	[ key: string ]: IdType;
};

type WithRequiredProperty< Type, Key extends keyof Type > = Type & {
	[ Property in Key ]-?: Type[ Property ];
};

export type CrudCreateItemActionOptions = {
	optimisticQueryUpdate?: ItemQuery;
	optimisticUrlParameters?: IdType[];
	optimisticPropagation?: boolean;

	/*
	 * This is a temporary ID that is used to identify the item in the store
	 * before the actual ID is known. This is used for optimistic updates.
	 */
	tempId?: IdType;
};

export type CrudActionOptions = {
	optimisticQueryUpdate?: ItemQuery;
	optimisticUrlParameters?: IdType[];
};

export type CreateAction< ResourceName extends string, ItemType > = {
	[ Property in `create${ Capitalize< ResourceName > }` ]: (
		query: Partial< ItemType >,
		options?: CrudCreateItemActionOptions
	) => Generator< unknown, ItemType >;
};

export type DeleteAction< ResourceName extends string, ItemType > = {
	[ Property in `delete${ Capitalize< ResourceName > }` ]: (
		idQuery: IdQuery,
		force?: boolean
	) => Generator< unknown, ItemType >;
};

export type UpdateAction< ResourceName extends string, ItemType > = {
	[ Property in `update${ Capitalize< ResourceName > }` ]: (
		query: Partial< ItemType >
	) => Generator< unknown, ItemType >;
};

export type CrudActions<
	ResourceName,
	ItemType,
	MutableProperties,
	RequiredFields extends keyof MutableProperties | undefined = undefined
> = MapActions<
	{
		create: (
			query: Partial< ItemType >,
			options?: CrudActionOptions
		) => ItemType;
		update: ( query: Partial< ItemType > ) => Item;
	},
	ResourceName,
	RequiredFields extends keyof MutableProperties
		? WithRequiredProperty< Partial< MutableProperties >, RequiredFields >
		: Partial< MutableProperties >,
	Generator< unknown, ItemType >
> &
	MapActions<
		{
			delete: ( id: IdType ) => Item;
		},
		ResourceName,
		IdType,
		Generator< unknown, ItemType >
	>;

export type CrudSelectors<
	ResourceName,
	PluralResourceName,
	ItemType,
	ItemQueryType,
	MutableProperties
> = MapSelectors<
	{
		'': WPDataSelector< typeof getItem >;
	},
	ResourceName,
	IdQuery,
	ItemType
> &
	MapSelectors<
		{
			Error: WPDataSelector< typeof getItemError >;
			DeleteError: WPDataSelector< typeof getItemDeleteError >;
			UpdateError: WPDataSelector< typeof getItemUpdateError >;
		},
		ResourceName,
		IdQuery,
		unknown
	> &
	MapSelectors<
		{
			'': WPDataSelector< typeof getItems >;
		},
		PluralResourceName,
		ItemQueryType,
		ItemType[]
	> &
	MapSelectors<
		{
			TotalCount: WPDataSelector< typeof getItemsTotalCount >;
		},
		PluralResourceName,
		ItemQueryType,
		number
	> &
	MapSelectors<
		{
			Error: WPDataSelector< typeof getItemsError >;
		},
		PluralResourceName,
		ItemQueryType,
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
	> }${ Capitalize< string & Property > }` ]: ( x?: ParamType ) => ReturnType;
};

export type MapActions< Type, ResourceName, ParamType, ReturnType > = {
	[ Property in keyof Type as `${ Lowercase<
		string & Property
	> }${ Capitalize< string & ResourceName > }` ]: (
		x: ParamType
	) => ReturnType;
};
