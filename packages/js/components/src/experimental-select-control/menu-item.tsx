/**
 * External dependencies
 */
import { createElement, CSSProperties, ReactElement } from 'react';
import classNames from 'classnames';
import { Tooltip } from '@wordpress/components';

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
	className?: string;
};

export const MenuItem = < ItemType, >( {
	children,
	getItemProps,
	index,
	isActive,
	activeStyle = { backgroundColor: '#bde4ff' },
	item,
	tooltipText,
	className,
}: MenuItemProps< ItemType > ) => {
	function renderListItem() {
		const itemProps = getItemProps( { item, index } );
		return (
			<li
				{ ...itemProps }
				style={ isActive ? activeStyle : itemProps.style }
				className={ classNames(
					'woocommerce-experimental-select-control__menu-item',
					itemProps.className,
					className
				) }
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
