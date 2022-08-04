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
	const groupActionProps = Object.assign(
		{ variant },
		disabled ? { disabled } : {}
	);
	const mainActionProps = {
		...groupActionProps,
		className: `woocommerce-split-dropdown__main-button ${ className }`,
	};
	const [ mainAction, ...menuActions ] = children;
	const menuIcon = chevronDown;
	const menuIconExpanded = chevronUp;
	return (
		<ButtonGroup className={ `woocommerce-split-dropdown ${ className }` }>
			{ cloneElement( mainAction, mainActionProps ) }
			<Dropdown
				contentClassName={ `woocommerce-split-dropdown__menu ${ className }` }
				position="bottom left"
				renderToggle={ ( { isOpen, onToggle } ) => {
					return (
						<Button
							{ ...groupActionProps }
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
