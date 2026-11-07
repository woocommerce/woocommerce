/**
 * External dependencies
 */
import clsx from 'clsx';
import { Children, forwardRef } from '@wordpress/element';
import type { ForwardedRef, ReactNode } from 'react';

/**
 * Internal dependencies
 */
import './style.scss';

export interface TotalsWrapperProps {
	children: ReactNode;
	/* If this TotalsWrapper is being used to wrap a Slot */
	slotWrapper?: boolean;
	className?: string;
}

const TotalsWrapper = (
	{ children, slotWrapper = false, className }: TotalsWrapperProps,
	ref: ForwardedRef< HTMLDivElement >
): JSX.Element | null => {
	return Children.count( children ) ? (
		<div
			className={ clsx( className, 'wc-block-components-totals-wrapper', {
				'slot-wrapper': slotWrapper,
			} ) }
			ref={ ref }
		>
			{ children }
		</div>
	) : null;
};

export default forwardRef( TotalsWrapper );
