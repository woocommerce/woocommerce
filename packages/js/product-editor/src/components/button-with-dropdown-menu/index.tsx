/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { chevronDown } from '@wordpress/icons';
import { Button, DropdownMenu, Flex, FlexItem } from '@wordpress/components';

import type {
	// @ts-expect-error no exported member.
	DropdownOption,
} from '@wordpress/components';

export interface ButtonWithDropdownMenuProps {
	label: string;
	controls?: DropdownOption[];
	onButtonClick?: () => void;
}

export const ButtonWithDropdownMenu: React.FC<
	ButtonWithDropdownMenuProps
> = ( { label, onButtonClick = () => {}, controls = [] } ) => {
	return (
		<Flex
			className="woocommerce-button-with-dropdown-menu"
			justify="left"
			gap={ 0 }
		>
			<FlexItem>
				<Button
					variant="primary"
					onClick={ onButtonClick }
					className="woocommerce-button-with-dropdown-menu__main-button"
				>
					{ label }
				</Button>
			</FlexItem>
			<FlexItem>
				<DropdownMenu
					toggleProps={ {
						variant: 'primary',
						className:
							'woocommerce-button-with-dropdown-menu__dropdown-button',
					} }
					controls={ controls }
					icon={ chevronDown }
					label="Select a direction."
				/>
			</FlexItem>
		</Flex>
	);
};
