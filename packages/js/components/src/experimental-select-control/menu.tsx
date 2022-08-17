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
		<div
			{ ...getMenuProps() }
			className={ classnames( 'woocommerce-select-control__menu', {
				'is-open': isOpen,
			} ) }
		>
			{ isOpen &&
				( ! Array.isArray( children ) || !! children.length ) && (
					<ul className="woocommerce-select-control__menu-inner">
						{ children }
					</ul>
				) }
		</div>
	);
};
