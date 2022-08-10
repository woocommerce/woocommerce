/**
 * External dependencies
 */
import { createElement, ReactElement } from 'react';

/**
 * Internal dependencies
 */
import { Props } from './types';

type MenuProps = {
	children?: ReactElement;
	menuProps: Props;
	isOpen: boolean;
};

export const Menu = ( { children, menuProps, isOpen }: MenuProps ) => {
	if ( ! isOpen ) {
		return null;
	}

	return (
		<ul { ...menuProps } className="woocommerce-select-control__menu">
			{ children }
		</ul>
	);
};
