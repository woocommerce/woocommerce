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
			className={ classnames( 'woocommerce-select-control__menu', {
				'is-open': isOpen,
			} ) }
		>
			{ isOpen &&
				( ! Array.isArray( children ) || !! children.length ) && (
					<div className="woocommerce-select-control__menu-inner">
						{ children }
					</div>
				) }
		</ul>
	);
};
