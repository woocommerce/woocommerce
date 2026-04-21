/**
 * External dependencies
 */
import { BaseControl } from '@wordpress/components';

type StackProps = React.HTMLAttributes< HTMLDivElement > & {
	direction?: 'row' | 'column';
	gap?: number;
	align?: React.CSSProperties[ 'alignItems' ];
	justify?: React.CSSProperties[ 'justifyContent' ];
};

type InputControlProps = {
	id?: string;
	label?: React.ReactNode;
	value?: string | number;
	type?: React.HTMLInputTypeAttribute;
	min?: number | string;
	max?: number | string;
	step?: number | string;
	prefix?: React.ReactNode;
	suffix?: React.ReactNode;
	description?: React.ReactNode;
	placeholder?: string;
	maxLength?: number;
	hideLabelFromVision?: boolean;
	customValidity?: string;
	disabled?: boolean;
	onChange?: ( event: React.ChangeEvent< HTMLInputElement > ) => void;
};

type TextareaControlProps = {
	label?: React.ReactNode;
	value?: string;
	rows?: number;
	placeholder?: string;
	maxLength?: number;
	description?: React.ReactNode;
	hideLabelFromVision?: boolean;
	onValueChange?: ( value: string ) => void;
};

type SelectItem = {
	label: string;
	value: string;
	disabled?: boolean;
};

type SelectControlProps = {
	label?: React.ReactNode;
	value?: string;
	items?: SelectItem[];
	description?: React.ReactNode;
	hideLabelFromVision?: boolean;
	onValueChange?: ( value: string ) => void;
};

type CheckboxControlProps = {
	label?: React.ReactNode;
	checked?: boolean;
	help?: React.ReactNode;
	onCheckedChange?: ( checked: boolean ) => void;
};

const inputBaseStyle: React.CSSProperties = {
	width: '100%',
	minHeight: '40px',
	border: '1px solid #949494',
	borderRadius: '2px',
	padding: '6px 8px',
	background: '#fff',
};

const adornmentStyle: React.CSSProperties = {
	display: 'inline-flex',
	alignItems: 'center',
	color: '#50575e',
	padding: '0 8px',
};

export const Stack = ( {
	direction = 'column',
	gap = 0,
	align,
	justify,
	style,
	...props
}: StackProps ) => {
	return (
		<div
			{ ...props }
			style={ {
				display: 'flex',
				flexDirection: direction,
				gap: `${ gap * 4 }px`,
				alignItems: align,
				justifyContent: justify,
				...style,
			} }
		/>
	);
};

function Slot( {
	children,
}: {
	children?: React.ReactNode;
	padding?: 'minimal';
} ) {
	return <span style={ adornmentStyle }>{ children }</span>;
}

export const InputLayout = {
	Slot,
};

export const InputControl = ( {
	id,
	label,
	value = '',
	type = 'text',
	min,
	max,
	step,
	prefix,
	suffix,
	description,
	placeholder,
	maxLength,
	hideLabelFromVision,
	customValidity,
	disabled,
	onChange,
}: InputControlProps ) => {
	return (
		<BaseControl
			id={ id }
			label={ label }
			help={ description }
			hideLabelFromVision={ hideLabelFromVision }
		>
			<div
				style={ {
					...inputBaseStyle,
					display: 'flex',
					alignItems: 'center',
					padding: 0,
					overflow: 'hidden',
				} }
			>
				{ prefix }
				<input
					id={ id }
					value={ value }
					type={ type }
					min={ min }
					max={ max }
					step={ step }
					placeholder={ placeholder }
					maxLength={ maxLength }
					disabled={ disabled }
					onChange={ onChange }
					style={ {
						flex: 1,
						border: 0,
						outline: 'none',
						padding: '8px',
						minWidth: 0,
					} }
					ref={ ( input ) => {
						if ( input ) {
							input.setCustomValidity( customValidity || '' );
						}
					} }
				/>
				{ suffix }
			</div>
		</BaseControl>
	);
};

export const TextareaControl = ( {
	label,
	value = '',
	rows = 4,
	placeholder,
	maxLength,
	description,
	hideLabelFromVision,
	onValueChange,
}: TextareaControlProps ) => {
	return (
		<BaseControl
			label={ label }
			help={ description }
			hideLabelFromVision={ hideLabelFromVision }
		>
			<textarea
				rows={ rows }
				value={ value }
				placeholder={ placeholder }
				maxLength={ maxLength }
				onChange={ ( event ) => onValueChange?.( event.target.value ) }
				style={ {
					...inputBaseStyle,
					minHeight: `${ Math.max( rows, 4 ) * 24 }px`,
					resize: 'vertical',
				} }
			/>
		</BaseControl>
	);
};

export const SelectControl = ( {
	label,
	value = '',
	items = [],
	description,
	hideLabelFromVision,
	onValueChange,
}: SelectControlProps ) => {
	return (
		<BaseControl
			label={ label }
			help={ description }
			hideLabelFromVision={ hideLabelFromVision }
		>
			<select
				value={ value }
				onChange={ ( event ) => onValueChange?.( event.target.value ) }
				style={ inputBaseStyle }
			>
				{ items.map( ( item ) => (
					<option
						key={ `${ item.value }-${ item.label }` }
						value={ item.value }
						disabled={ item.disabled }
					>
						{ item.label }
					</option>
				) ) }
			</select>
		</BaseControl>
	);
};

export const CheckboxControl = ( {
	label,
	checked = false,
	help,
	onCheckedChange,
}: CheckboxControlProps ) => {
	return (
		<BaseControl help={ help }>
			<label
				style={ {
					display: 'inline-flex',
					alignItems: 'center',
					gap: '8px',
				} }
			>
				<input
					type="checkbox"
					checked={ checked }
					onChange={ ( event ) =>
						onCheckedChange?.( event.target.checked )
					}
				/>
				<span>{ label }</span>
			</label>
		</BaseControl>
	);
};
