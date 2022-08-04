/**
 * External dependencies
 */
import { cloneElement, createElement } from '@wordpress/element';
import { Icon, chevronDown, chevronUp } from '@wordpress/icons';
import { Button, ButtonGroup, Dropdown } from '@wordpress/components';

/**
 * Internal dependencies
 */

export type SplitButtonDropdownProps = {
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
	menuIcon?: JSX.Element;
	menuIconExpanded?: JSX.Element;
	children: JSX.Element[];
};

export const SplitButtonDropdown: React.FC< SplitButtonDropdownProps > = ( {
	className = '',
	menuIcon = chevronDown,
	menuIconExpanded = chevronUp,
	children,
	variant = 'primary',
	...props
}: SplitButtonDropdownProps ) => {
	const [ mainButton, ...menuButtons ] = children;

	return (
		<ButtonGroup className={ `woocommerce-split-button-dropdown` }>
			{ cloneElement( mainButton, {
				className: 'woocommerce-split-button-dropdown__main-button',
				variant,
			} ) }
			<Dropdown
				contentClassName={ `woocommerce-split-button-dropdown__menu ${ className }` }
				position="bottom left"
				renderToggle={ ( { isOpen, onToggle } ) => {
					return (
						<Button
							{ ...props }
							className={ `woocommerce-split-button-dropdown__toggle ${ className }` }
							onClick={ onToggle }
							// eslint-disable-next-line @typescript-eslint/ban-ts-comment
							// @ts-ignore
							variant={ variant }
						>
							<Icon
								icon={ isOpen ? menuIconExpanded : menuIcon }
							/>
						</Button>
					);
				} }
				renderContent={ () => {
					return (
						<div>
							{ menuButtons.map( ( button, index ) => {
								return cloneElement( button, {
									key: index,
								} );
							} ) }
						</div>
					);
				} }
			/>
		</ButtonGroup>
	);
};
