/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import './step-placeholder.scss';

interface StepPlaceholderProps {
	/**
	 * The number of placeholder steps to display.
	 */
	rows: number;
}

/**
 * A component that renders placeholder steps that match the structure of SidebarNavigationItem.
 * Each placeholder has an icon on the left and text content on the right.
 * This component is typically used to indicate a loading state for payment steps.
 *
 * @example
 * // Render 3 placeholder steps
 * <StepPlaceholder rows={3} />
 */
export const StepPlaceholder = ( { rows }: StepPlaceholderProps ) => {
	// Create an array of placeholder items based on the number of rows.
	const placeholderItems = Array.from( { length: rows } ).map(
		( _, index ) => (
			<div
				key={ index }
				className="step-placeholder__item payment-step payment-step--disabled"
			>
				<div className="step-placeholder__icon" />
				<div className="step-placeholder__content">
					<div className="step-placeholder__text" />
				</div>
			</div>
		)
	);

	return <div className="step-placeholder">{ placeholderItems }</div>;
};
