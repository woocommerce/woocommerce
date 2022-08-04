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
	disabled,
	variant = 'primary',
	...props
}: SplitButtonDropdownProps ) => {
	const groupActionProps = Object.assign(
		{ variant },
		disabled ? { disabled } : {}
	);
	const mainActionProps = {
		...props,
		...groupActionProps,
		className: `woocommerce-split-button-dropdown__main-button ${ className }`,
	};
	const [ mainAction, ...menuActions ] = children;
	return (
		<ButtonGroup className={ `woocommerce-split-button-dropdown` }>
			{ cloneElement( mainAction, mainActionProps ) }
			<Dropdown
				contentClassName={ `woocommerce-split-button-dropdown__menu ${ className }` }
				position="bottom left"
				renderToggle={ ( { isOpen, onToggle } ) => {
					return (
						<Button
							{ ...props }
							{ ...groupActionProps }
							className={ `woocommerce-split-button-dropdown__toggle ${ className }` }
							onClick={ onToggle }
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
							{ menuActions.map( ( action, index ) => {
								return cloneElement( action, {
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
