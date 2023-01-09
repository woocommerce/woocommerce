/**
 * External dependencies
 */
import classNames from 'classnames';
import { createElement, forwardRef } from 'react';

/**
 * Internal dependencies
 */
import { MenuItem } from './menu-item';
import { MenuProps } from './types';

export const Menu = forwardRef( function ForwardedMenu(
	{
		items,
		selected,
		inputValue,
		level = 1,
		multiple,
		className,
		onSelect,
		onRemove,
		...props
	}: MenuProps,
	ref: React.ForwardedRef< HTMLOListElement >
) {
	return (
		<ol
			{ ...props }
			ref={ ref }
			className={ classNames(
				className,
				'experimental-woocommerce-autocomplete__menu',
				`experimental-woocommerce-autocomplete__menu--level-${ level }`
			) }
			role={ level === 1 ? 'tree' : 'group' }
		>
			{ items.map( ( item ) => (
				<MenuItem
					key={ item.value }
					item={ item }
					selected={ selected }
					inputValue={ inputValue }
					level={ level }
					multiple={ multiple }
					onSelect={ onSelect }
					onRemove={ onRemove }
				/>
			) ) }
		</ol>
	);
} );
