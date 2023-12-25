/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { chevronDown } from '@wordpress/icons';
import { Button, DropdownMenu, Flex, FlexItem } from '@wordpress/components';
/**
 * Internal dependencies
 */
import type { ButtonWithDropdownMenuProps } from './types';

export const ButtonWithDropdownMenu: React.FC<
	ButtonWithDropdownMenuProps
> = ( {
	label,
	onButtonClick = () => {},
	controls = [],
	variant = 'primary',
	defaultOpen = false,
	popoverProps: {
		placement = 'bottom-end',
		position = 'bottom left left',
		offset = 0,
	} = {
		placement: 'bottom-end',
		position: 'bottom left left',
		offset: 0,
	},
} ) => {
	return (
		<Flex
			className="woocommerce-button-with-dropdown-menu"
			justify="left"
			gap={ 0 }
			expanded={ false }
			role="group"
		>
			<FlexItem role="none">
				<Button
					variant={ variant }
					onClick={ onButtonClick }
					className="woocommerce-button-with-dropdown-menu__main-button"
				>
					{ label }
				</Button>
			</FlexItem>

			<FlexItem role="none">
				<DropdownMenu
					toggleProps={ {
						className:
							'woocommerce-button-with-dropdown-menu__dropdown-button',
						variant,
					} }
					controls={ controls }
					icon={ chevronDown }
					label="Select a direction."
					popoverProps={ {
						placement,
						// @ts-expect-error no exported member.
						position,
						offset,
					} }
					defaultOpen={ true }
				/>
			</FlexItem>
		</Flex>
	);
};
