/**
 * External dependencies
 */
import { createElement, CSSProperties, ReactElement } from 'react';

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
	activeStyle?: CSSProperties;
};

export const MenuItem = < ItemType, >( {
	children,
	getItemProps,
	index,
	isActive,
	activeStyle = { backgroundColor: '#bde4ff' },
	item,
}: MenuItemProps< ItemType > ) => {
	return (
		<li
			style={ isActive ? activeStyle : {} }
			{ ...getItemProps( { item, index } ) }
			className="woocommerce-experimental-select-control__menu-item"
		>
			{ children }
		</li>
	);
};
