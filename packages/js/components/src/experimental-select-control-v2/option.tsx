/**
 * External dependencies
 */
import classNames from 'classnames';
import { createElement, MouseEvent, ReactElement } from 'react';

export type OptionProps< Item > = {
	isActive?: boolean;
	item: Item;
	children: ReactElement | string;
	onClick: ( event: MouseEvent< HTMLLIElement > ) => void;
};

export const Option = < Item, >( {
	children,
	isActive = false,
	onClick,
}: OptionProps< Item > ) => {
	const classes = classNames(
		'woocommerce-experimental-select-control__menu-item',
		{
			'is-active': isActive,
		}
	);
	return (
		/* eslint-disable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */
		/* Disabled because of the onmouseup on the ul element below. */
		<li
			className={ classes }
			onClick={ onClick }
			onMouseDown={ ( event ) => event.preventDefault() }
		>
			{ children }
		</li>
	);
};
