/**
 * External dependencies
 */
import type { ReactElement } from 'react';

export interface RadioControlProps {
	// Class name for control.
	className?: string;
	// ID for the control.
	id?: string;
	// The selected option. This is a controlled component.
	selected: string;
	// Fired when an option is changed.
	onChange: ( value: string ) => void;
	// List of radio control options.
	options: RadioControlOption[];
	// Is the control disabled.
	disabled?: boolean;
	// Should the selected option be highlighted with a border?
	highlightChecked?: boolean;
	// How the description should stack, follows the same naming as flex layout,
	// so column means vertical stacking, and row means horizontal stacking.
	descriptionStackingDirection?: 'column' | 'row';
}

export interface RadioControlOptionProps {
	checked: boolean;
	name?: string;
	onChange: ( value: string ) => void;
	option: RadioControlOption;
	disabled?: boolean;
	// Should the selected option be highlighted with a border?
	highlightChecked?: boolean;
	descriptionStackingDirection?: 'column' | 'row' | undefined;
}

interface RadioControlOptionContent {
	label: string | JSX.Element;
	content?: string | JSX.Element;
	description?: string | ReactElement | undefined;
	secondaryLabel?: string | ReactElement | undefined;
	secondaryDescription?: string | ReactElement | undefined;
}

export interface RadioControlOption extends RadioControlOptionContent {
	value: string;
	onChange?: ( value: string ) => void;
}

export interface RadioControlOptionLayout extends RadioControlOptionContent {
	id?: string;
	descriptionStackingDirection?: 'column' | 'row' | undefined;
}
