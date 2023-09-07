/**
 * External dependencies
 */
import { Tooltip } from '@wordpress/components';
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
	tooltipText?: string;
};

export const MenuItem = < ItemType, >( {
	children,
	getItemProps,
	index,
	isActive,
	activeStyle = { backgroundColor: '#bde4ff' },
	item,
	tooltipText,
}: MenuItemProps< ItemType > ) => {
	function renderListItem() {
		return (
			<li
				style={ isActive ? activeStyle : {} }
				{ ...getItemProps( { item, index } ) }
				className="woocommerce-experimental-select-control__menu-item"
			>
				{ children }
			</li>
		);
	}

	if ( tooltipText ) {
		return (
			<Tooltip text={ tooltipText } position="top center">
				{ renderListItem() }
			</Tooltip>
		);
	}

	return renderListItem();
};
