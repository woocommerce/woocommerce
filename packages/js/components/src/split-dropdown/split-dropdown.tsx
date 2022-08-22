/**
 * External dependencies
 */
import { cloneElement, createElement } from '@wordpress/element';
import { Icon, chevronDown, chevronUp } from '@wordpress/icons';
import { Button, ButtonGroup, Dropdown } from '@wordpress/components';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
export type SplitDropdownProps = {
	className?: string | null;
	disabled?: boolean;
	children: JSX.Element | JSX.Element[];
} & Pick< Button.ButtonProps, 'variant' >;

export const SplitDropdown: React.FC< SplitDropdownProps > = ( {
	className = null,
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
		className: classNames(
			'woocommerce-split-dropdown__main-button',
			className
		),
	};
	const [ mainItem, ...menuItems ] = Array.isArray( children )
		? children
		: [ children ];
	return (
		<ButtonGroup
			className={ classNames(
				'woocommerce-split-dropdown',
				'woocommerce-split-dropdown__container',
				className
			) }
		>
			{ cloneElement( mainItem, mainItemProps ) }
			{ menuItems.length > 0 && (
				<Dropdown
					contentClassName={ classNames(
						'woocommerce-split-dropdown__menu',
						className
					) }
					position="bottom left"
					renderToggle={ ( { isOpen, onToggle } ) => {
						return (
							<Button
								{ ...groupItemProps }
								className={ classNames(
									'woocommerce-split-dropdown__toggle',
									className
								) }
								onClick={ onToggle }
							>
								<Icon
									icon={ isOpen ? chevronUp : chevronDown }
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
			) }
		</ButtonGroup>
	);
};
