/**
 * External dependencies
 */
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { createElement, useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SortableListHandleContext } from './context';

export type SortableListHandleProps = {
	children?: React.ReactNode;
	className?: string;
	label?: string;
} & Omit< React.ButtonHTMLAttributes< HTMLButtonElement >, 'children' >;

export const SortableListHandle = ( {
	children,
	className,
	label = __( 'Drag to reorder', 'woocommerce' ),
	...props
}: SortableListHandleProps ) => {
	const { attributes, disabled, listeners, setActivatorNodeRef } = useContext(
		SortableListHandleContext
	);

	return (
		<button
			{ ...props }
			{ ...( disabled ? {} : attributes ) }
			{ ...( disabled ? {} : listeners ) }
			ref={ setActivatorNodeRef }
			type="button"
			className={ clsx( 'woocommerce-sortable-list__handle', className ) }
			aria-label={ props[ 'aria-label' ] || label }
			disabled={ disabled || props.disabled }
		>
			{ children }
		</button>
	);
};
