/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import type {
	// @ts-expect-error no exported member.
	DropdownOption,
} from '@wordpress/components';

type ButtonVariant = Button.ButtonProps[ 'variant' ];

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
	text: string;
	dropdownButtonLabel?: string;
	variant?: ButtonVariant;
	defaultOpen?: boolean;
	controls?: DropdownOption[];

	popoverProps?: popoverProps;
	onButtonClick?: () => void;
	className?: string;
}
