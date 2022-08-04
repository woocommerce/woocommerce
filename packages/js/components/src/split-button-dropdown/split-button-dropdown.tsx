/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
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
	children: React.ReactNode[];
};

export const SplitButtonDropdown: React.FC< SplitButtonDropdownProps > = ( {
	className = '',
	menuIcon = chevronDown,
	menuIconExpanded = chevronUp,
	children,
	variant = 'primary',
	...props
}: SplitButtonDropdownProps ) => {
	const groupButtonProps = { variant };
	const [ primaryButton, ...menuButtons ] = children;
	const primaryButtonProps = Object.assign(
		{},
		primaryButton?.props || {},
		{ className: `woocommerce-split-button-dropdown__main ${ className }` },
		groupButtonProps
	);

	return (
		<ButtonGroup className={ `woocommerce-split-button-dropdown` }>
			<Button { ...primaryButtonProps }></Button>
			<Dropdown
				contentClassName={ `woocommerce-split-button-dropdown__menu ${ className }` }
				renderToggle={ ( { isOpen, onToggle } ) => {
					return (
						<Button
							{ ...props }
							{ ...groupButtonProps }
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
					return <div>{ menuButtons }</div>;
				} }
			/>
		</ButtonGroup>
	);
};
