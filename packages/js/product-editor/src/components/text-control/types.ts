/**
 * This must inherit the InputControlProp from @wordpress/components
 * but it has not been exported yet
 */

export type TextControlProps = Omit<
	React.DetailedHTMLProps<
		React.InputHTMLAttributes< HTMLInputElement >,
		HTMLInputElement
	>,
	'onChange' | 'size' | 'value' | 'onDrag' | 'onDragEnd' | 'onDragStart'
> & {
	label: string;
	help?: string;
	error?: string;
	tooltip?: string;
	prefix?: React.ReactNode;
	suffix?: React.ReactNode;
	value?: string;
	onChange( value: string ): void;
};
