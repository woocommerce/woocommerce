/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { chevronDown } from '@wordpress/icons';
import { Button, DropdownMenu, Flex, FlexItem } from '@wordpress/components';

export interface ActionSplitButtonProps {
	label: string;
	onMainButtonClick?: () => void;
	controls?: [];
}

export const ActionSplitButton: React.FC< ActionSplitButtonProps > = ( {
	label,
	onMainButtonClick = () => {},
	controls,
	secondaryActions
} ) => {
	return (
		<Flex
			className="woocommerce-button-with-dropdown-menu"
			justify="left"
			gap={ 0 }
		>
			<FlexItem>
				<Button
					variant="primary"
					onClick={ onMainButtonClick }
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
					onToggle={ function noRefCheck() {} }
				/>
			</FlexItem>
		</Flex>
	);
};
