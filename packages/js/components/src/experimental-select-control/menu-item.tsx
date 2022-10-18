/**
 * External dependencies
 */
import { createElement, ReactElement } from 'react';

/**
 * Internal dependencies
 */
import { getItemPropsType } from './types';

export type MenuItemProps< ItemType > = {
	index: number;
	isActive: boolean;
	item: ItemType;
	children: ReactElement | string;
	getItemProps: getItemPropsType< ItemType >;
};

export const MenuItem = < ItemType, >( {
	children,
	getItemProps,
	index,
	isActive,
	item,
}: MenuItemProps< ItemType > ) => {
	return (
		<li
			style={ isActive ? { backgroundColor: '#bde4ff' } : {} }
			{ ...getItemProps( { item, index } ) }
			className="woocommerce-experimental-select-control__menu-item"
		>
			{ children }
		</li>
	);
};
