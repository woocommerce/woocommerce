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

type PositionYAxis = 'top' | 'middle' | 'bottom';
type PositionXAxis = 'left' | 'center' | 'right';
type PositionCorner = 'top' | 'right' | 'bottom' | 'left';

type PopoverPlacement =
	| 'left'
	| 'right'
	| 'bottom'
	| 'top'
	| 'left-end'
	| 'left-start'
	| 'right-end'
	| 'right-start'
	| 'bottom-end'
	| 'bottom-start'
	| 'top-end'
	| 'top-start'; // @todo: pick from core

type popoverPosition =
	| `${ PositionYAxis }`
	| `${ PositionYAxis } ${ PositionXAxis }`
	| `${ PositionYAxis } ${ PositionXAxis } ${ PositionCorner }`;

type popoverProps = {
	placement?: PopoverPlacement;
	position?: popoverPosition;
	offset?: number;
};

export interface ButtonWithDropdownMenuProps {
	label: string;
	variant?: Button.ButtonProps[ 'variant' ];
	controls?: DropdownOption[];

	popoverProps?: popoverProps;
	onButtonClick?: () => void;
}

export const ButtonWithDropdownMenu: React.FC<
	ButtonWithDropdownMenuProps
> = ( {
	label,
	onButtonClick = () => {},
	controls = [],
	variant = 'primary',
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
				/>
			</FlexItem>
		</Flex>
	);
};
