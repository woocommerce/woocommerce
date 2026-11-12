/**
 * External dependencies
 */
import clsx from 'clsx';
import { Children } from '@wordpress/element';
import type { ReactNode, Ref } from 'react';

/**
 * Internal dependencies
 */
import './style.scss';

export interface TotalsWrapperProps {
	children: ReactNode;
	/* If this TotalsWrapper is being used to wrap a Slot */
	slotWrapper?: boolean;
	className?: string;
	/* A ref to the wrapper element, such as for motion. */
	elementRef?: Ref< HTMLDivElement >;
	/* Set while the row animates out. The row is inert and hidden from assistive technology. */
	isExiting?: boolean;
}

const TotalsWrapper = ( {
	children,
	slotWrapper = false,
	className,
	elementRef,
	isExiting = false,
}: TotalsWrapperProps ): JSX.Element | null => {
	return Children.count( children ) ? (
		<div
			ref={ elementRef }
			className={ clsx( className, 'wc-block-components-totals-wrapper', {
				'slot-wrapper': slotWrapper,
			} ) }
			// React 18 has no `inert` prop.
			{ ...( isExiting && { inert: '', 'aria-hidden': true } ) }
		>
			{ children }
		</div>
	) : null;
};

export default TotalsWrapper;
