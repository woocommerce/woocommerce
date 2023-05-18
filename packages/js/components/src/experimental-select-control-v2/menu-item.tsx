/**
 * External dependencies
 */
import classNames from 'classnames';
import { createElement, ReactElement } from 'react';

export type MenuItemProps< Item > = {
	isActive?: boolean;
	item: Item;
	children: ReactElement | string;
	onClick: () => void;
};

export const MenuItem = < Item, >( {
	children,
	isActive = false,
	onClick,
}: MenuItemProps< Item > ) => {
	const classes = classNames(
		'woocommerce-experimental-select-control__menu-item',
		{
			'is-active': isActive,
		}
	);
	return (
		/* eslint-disable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */
		/* Disabled because of the onmouseup on the ul element below. */
		<li className={ classes } onClick={ onClick }>
			{ children }
		</li>
	);
};
