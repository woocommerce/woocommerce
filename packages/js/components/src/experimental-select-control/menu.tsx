/**
 * External dependencies
 */
import classnames from 'classnames';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { getMenuPropsType } from './types';

type MenuProps = {
	children?: JSX.Element | JSX.Element[];
	getMenuProps: getMenuPropsType;
	isOpen: boolean;
	className?: string;
};

export const Menu = ( {
	children,
	getMenuProps,
	isOpen,
	className,
}: MenuProps ) => {
	return (
		<ul
			{ ...getMenuProps() }
			className={ classnames(
				'woocommerce-experimental-select-control__menu',
				className,
				{
					'is-open': isOpen,
					'has-results': Array.isArray( children )
						? children.length
						: Boolean( children ),
				}
			) }
		>
			{ isOpen && children }
		</ul>
	);
};
