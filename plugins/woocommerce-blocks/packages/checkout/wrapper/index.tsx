/**
 * External dependencies
 */
import { Children, ReactNode } from 'react';

/**
 * Internal dependencies
 */
import './style.scss';

interface TotalsWrapperProps {
	children: ReactNode;
	/* If this TotalsWrapper is being used to wrap a Slot */
	slotWrapper?: boolean;
}

const TotalsWrapper = ( {
	children,
	slotWrapper = false,
}: TotalsWrapperProps ): JSX.Element | null => {
	return Children.count( children ) ? (
		<div
			className={ `wc-block-components-totals-wrapper${
				slotWrapper ? ' slot-wrapper' : ''
			}` }
		>
			{ children }
		</div>
	) : null;
};

export default TotalsWrapper;
