/**
 * External dependencies
 */
import classnames from 'classnames';
import { createElement, ReactElement } from 'react';

/**
 * Internal dependencies
 */
import { getMenuPropsType } from './types';

type MenuProps = {
	children?: JSX.Element | JSX.Element[];
	getMenuProps: getMenuPropsType;
	isOpen: boolean;
};

export const Menu = ( { children, getMenuProps, isOpen }: MenuProps ) => {
	return (
		<ul
			{ ...getMenuProps() }
			className={ classnames(
				'woocommerce-experimental-select-control__menu',
				{
					'is-open': isOpen,
				}
			) }
		>
			{ isOpen && children }
		</ul>
	);
};
