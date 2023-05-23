/**
 * External dependencies
 */
import classNames from 'classnames';
import { createElement, MouseEvent, ReactElement } from 'react';

export type OptionProps = {
	isHighlighted?: boolean;
	isSelected?: boolean;
	children: ReactElement | string;
	onClick: ( event: MouseEvent< HTMLLIElement > ) => void;
	onMouseDown: ( event: MouseEvent< HTMLLIElement > ) => void;
};

export const Option = ( {
	children,
	isHighlighted = false,
	isSelected = false,
	onClick,
	onMouseDown,
	...restProps
}: OptionProps ) => {
	const classes = classNames(
		'woocommerce-experimental-select-control__menu-item',
		{
			'is-highlighted': isHighlighted,
			'is-selected': isSelected,
		}
	);
	return (
		/* eslint-disable jsx-a11y/no-noninteractive-element-interactions, jsx-a11y/click-events-have-key-events */
		/* Disabled because of the onmouseup on the ul element below. */
		<li
			className={ classes }
			onClick={ onClick }
			onMouseDown={ onMouseDown }
			{ ...restProps }
		>
			{ children }
		</li>
	);
};
