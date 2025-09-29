/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import React from 'react';
import {
	// @ts-ignore No types for this exist yet.
	__unstableMotion as motion,
} from '@wordpress/components';

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
 * A component that renders animated placeholder steps that match the structure of SidebarNavigationItem.
 * Each placeholder has an icon on the left and text content on the right with loading animations.
 * This component is typically used to indicate a loading state for payment steps.
 *
 * @example
 * // Render 3 animated placeholder steps
 * <StepPlaceholder rows={3} />
 */
export const StepPlaceholder = ( { rows }: StepPlaceholderProps ) => {
	// Create an array of placeholder items based on the number of rows.
	const placeholderItems = Array.from( { length: rows } ).map(
		( _, index ) => (
			<motion.div
				key={ index }
				className="step-placeholder__item payment-step payment-step--disabled"
				initial={ { opacity: 0, x: -20 } }
				animate={ { opacity: 1, x: 0 } }
				transition={ {
					duration: 0.3,
					delay: index * 0.1, // Stagger the animation
					ease: 'easeOut',
				} }
			>
				<div className="step-placeholder__icon step-placeholder__shimmer" />
				<div className="step-placeholder__content">
					<div className="step-placeholder__text step-placeholder__shimmer" />
				</div>
			</motion.div>
		)
	);

	return (
		<motion.div
			className="step-placeholder"
			initial={ { opacity: 0 } }
			animate={ { opacity: 1 } }
			transition={ { duration: 0.2 } }
		>
			{ placeholderItems }
		</motion.div>
	);
};
