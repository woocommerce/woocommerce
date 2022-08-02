/**
 * External dependencies
 */
import { createElement, ReactElement } from 'react';

/**
 * Internal dependencies
 */
import { ItemType, getItemPropsType } from './types';
import './menu-item.scss';

type MenuItemProps = {
	index: number;
	isActive: boolean;
	item: ItemType;
	children: ReactElement | string;
	getItemProps: getItemPropsType;
};

export const MenuItem = ( {
	children,
	getItemProps,
	index,
	isActive,
	item,
}: MenuItemProps ) => {
	return (
		<li
			style={ isActive ? { backgroundColor: '#bde4ff' } : {} }
			{ ...getItemProps( { item, index } ) }
			className="woocommerce-search-control__menu-item"
		>
			{ children }
		</li>
	);
};
