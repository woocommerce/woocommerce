/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { Icon, chevronDown, chevronUp } from '@wordpress/icons';
import { Button, Dropdown } from '@wordpress/components';

/**
 * Internal dependencies
 */

export type SplitButtonProps = {
	isSmall?: boolean;
	isPressed?: boolean;
	className?: string;
	disabled?: boolean;
	icon?: string | typeof Icon;
	iconPosition?: string;
	iconSize?: number;
	showTooltip?: boolean;
	//tooltipPosition?: string;
	label?: string;
	text?: string;
	variant?: string;
	menuIcon?: string | React.ReactNode;
	menuIconExpanded?: string | React.ReactNode;
	children: React.ReactNode;
};

export const SplitButton: React.FC< SplitButtonProps > = ( {
	className = '',
	menuIcon = chevronDown,
	menuIconExpanded = chevronUp,
	children,
	label,
	text,
	...props
}: SplitButtonProps ) => {
	const outerButtonProps = { label, text, ...props };
	return (
		<div className={ `woocommerce-split-button` }>
			<Button
				className={ `woocommerce-split-button__main ${ className }` }
				{ ...outerButtonProps }
			>
				<Dropdown
					contentClassName={ `woocommerce-split-button__menu ${ className }` }
					renderToggle={ ( { isOpen, onToggle } ) => {
						return (
							<Button
								{ ...props }
								className={ `woocommerce-split-button__toggle ${ className }` }
								onClick={ onToggle }
							>
								{ isOpen ? menuIconExpanded : menuIcon }
							</Button>
						);
					} }
					renderContent={ () => {
						return <div>{ children }</div>;
					} }
				/>
			</Button>
		</div>
	);
};
