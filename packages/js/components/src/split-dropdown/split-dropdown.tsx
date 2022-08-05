/**
 * External dependencies
 */
import { cloneElement, createElement } from '@wordpress/element';
import { Icon, chevronDown, chevronUp } from '@wordpress/icons';
import { Button, ButtonGroup, Dropdown } from '@wordpress/components';

/**
 * Internal dependencies
 */
export type SplitDropdownProps = {
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
	children: JSX.Element[];
};

export const SplitDropdown: React.FC< SplitDropdownProps > = ( {
	className = '',
	children,
	disabled,
	variant = 'primary',
}: SplitDropdownProps ) => {
	const groupItemProps = Object.assign(
		{ variant },
		disabled ? { disabled } : {}
	);
	const mainItemProps = {
		...groupItemProps,
		className: `woocommerce-split-dropdown__main-button ${ className }`,
	};
	const [ mainItem, ...menuItems ] = children;
	const menuIcon = chevronDown;
	const menuIconExpanded = chevronUp;
	return (
		<ButtonGroup className={ `woocommerce-split-dropdown ${ className }` }>
			{ cloneElement( mainItem, mainItemProps ) }
			<Dropdown
				contentClassName={ `woocommerce-split-dropdown__menu ${ className }` }
				position="bottom left"
				renderToggle={ ( { isOpen, onToggle } ) => {
					return (
						<Button
							{ ...groupItemProps }
							className={ `woocommerce-split-dropdown__toggle ${ className }` }
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
							{ menuItems.map( ( item, index ) => {
								return cloneElement( item, {
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
