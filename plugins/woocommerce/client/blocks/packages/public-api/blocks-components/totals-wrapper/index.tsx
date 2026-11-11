/**
 * External dependencies
 */
import clsx from 'clsx';
import { Children, forwardRef } from '@wordpress/element';
import { useMergeRefs } from '@wordpress/compose';
import { useHeightChangeMotion } from '@woocommerce/base-hooks';
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
	// A row whose content changes size, such as Delivery gaining a description, moves what's below it smoothly.
	const mergedRef = useMergeRefs( [ ref, useHeightChangeMotion() ] );
	return Children.count( children ) ? (
		<div
			className={ clsx( className, 'wc-block-components-totals-wrapper', {
				'slot-wrapper': slotWrapper,
			} ) }
			ref={ mergedRef }
		>
			{ children }
		</div>
	) : null;
};

export default forwardRef( TotalsWrapper );
