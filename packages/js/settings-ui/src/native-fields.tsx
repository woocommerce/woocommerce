/**
 * External dependencies
 */
import { BaseControl, CheckboxControl } from '@wordpress/components';
import { createElement, RawHTML } from '@wordpress/element';
import { Field, InputControl, SelectControl, Textarea } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import { warn } from './diagnostics';
import { sanitizeSettingsHtml } from './html';
import { NumberSpinControl } from './number-spin-control';
import type { SettingsFieldComponentProps, SettingsValue } from './types';

type TextInputType =
	| 'text'
	| 'password'
	| 'datetime-local'
	| 'date'
	| 'time'
	| 'email'
	| 'url'
	| 'tel';

const textInputTypes: TextInputType[] = [
	'text',
	'password',
	'datetime-local',
	'date',
	'time',
	'email',
	'url',
	'tel',
];

const toStringValue = ( value: SettingsValue ) =>
	value === null || typeof value === 'undefined' ? '' : String( value );

const isTextInputType = ( type: string ): type is TextInputType =>
	textInputTypes.includes( type as TextInputType );

// Use HTML boolean attribute presence semantics: disabled="false" still
// means disabled, while a boolean false remains false.
const toPresenceBooleanCustomAttribute = (
	value: string | number | boolean | undefined
): boolean | undefined => {
	if ( typeof value === 'undefined' ) {
		return undefined;
	}

	return typeof value === 'boolean' ? value : true;
};

const toStringCustomAttribute = (
	value: string | number | undefined
): string | undefined => {
	return typeof value === 'undefined' ? undefined : String( value );
};

const getNumberInputAttributes = (
	customAttributes?: Record< string, string | number | boolean >
) => {
	const safeAttributes =
		customAttributes && typeof customAttributes === 'object'
			? customAttributes
			: {};
	const { disabled, placeholder, ...inputAttributes } = safeAttributes;
	const placeholderAttribute =
		typeof placeholder === 'boolean' ? undefined : placeholder;

	return {
		disabled: toPresenceBooleanCustomAttribute( disabled ),
		placeholder: toStringCustomAttribute( placeholderAttribute ),
		inputAttributes,
	};
};

const getHelp = ( description?: string ) =>
	description ? (
		<span
			dangerouslySetInnerHTML={ {
				__html: sanitizeSettingsHtml( description ),
			} }
		/>
	) : undefined;

export const NativeSettingsField = ( {
	field,
	value,
	onChange,
}: SettingsFieldComponentProps ) => {
	if ( field.type === 'info' ) {
		return (
			<div className="wc-settings-ui__info" id={ field.id }>
				<strong>{ field.label }</strong>
				{ field.description ? (
					<RawHTML>
						{ sanitizeSettingsHtml( field.description ) }
					</RawHTML>
				) : null }
			</div>
		);
	}

	if ( field.type === 'checkbox' ) {
		return (
			<CheckboxControl
				className="wc-settings-ui__control"
				label={ field.label }
				help={ getHelp( field.description ) }
				checked={ value === true || value === 'yes' || value === '1' }
				disabled={ field.disabled }
				onChange={ onChange }
				__nextHasNoMarginBottom
			/>
		);
	}

	if ( field.type === 'textarea' ) {
		return (
			<Field.Root className="wc-settings-ui__control">
				<Field.Label>{ field.label }</Field.Label>
				<Textarea
					value={ toStringValue( value ) }
					placeholder={ field.placeholder }
					disabled={ field.disabled }
					onChange={ ( event ) => onChange( event.target.value ) }
				/>
				{ field.description ? (
					<Field.Details>
						{ getHelp( field.description ) }
					</Field.Details>
				) : null }
			</Field.Root>
		);
	}

	if ( field.type === 'select' || field.type === 'radio' ) {
		const items = ( field.options || [] ).map( ( option ) => ( {
			value: option.value,
			label: option.label,
		} ) );
		const selectedItem =
			items.find( ( item ) => item.value === toStringValue( value ) ) ??
			null;

		return (
			<SelectControl
				className="wc-settings-ui__control"
				label={ field.label }
				details={ getHelp( field.description ) }
				items={ items }
				value={ selectedItem }
				disabled={ field.disabled }
				onValueChange={ ( item ) => onChange( item?.value ?? '' ) }
			/>
		);
	}

	if ( field.type === 'array' ) {
		const selectedValues = Array.isArray( value ) ? value : [];

		return (
			<BaseControl
				className="wc-settings-ui__control"
				id={ field.id }
				label={ field.label }
				help={ getHelp( field.description ) }
				__nextHasNoMarginBottom
			>
				<select
					id={ field.id }
					multiple
					disabled={ field.disabled }
					value={ selectedValues }
					onChange={ ( event ) => {
						onChange(
							Array.from(
								event.currentTarget.selectedOptions
							).map( ( option ) => option.value )
						);
					} }
				>
					{ ( field.options || [] ).map( ( option ) => (
						<option key={ option.value } value={ option.value }>
							{ option.label }
						</option>
					) ) }
				</select>
			</BaseControl>
		);
	}

	if ( field.type === 'number' ) {
		const numberInput = getNumberInputAttributes( field.customAttributes );

		return (
			<NumberSpinControl
				id={ field.id }
				label={ field.label }
				help={ getHelp( field.description ) }
				value={ toStringValue( value ) }
				placeholder={ field.placeholder ?? numberInput.placeholder }
				disabled={ field.disabled ?? numberInput.disabled }
				onChange={ onChange }
				inputAttributes={ numberInput.inputAttributes }
			/>
		);
	}

	if ( isTextInputType( field.type ) ) {
		return (
			<InputControl
				className="wc-settings-ui__control"
				type={ field.type }
				label={ field.label }
				details={ getHelp( field.description ) }
				value={ toStringValue( value ) }
				placeholder={ field.placeholder }
				disabled={ field.disabled }
				onChange={ ( event ) => onChange( event.target.value ) }
				{ ...field.customAttributes }
			/>
		);
	}

	warn( `Field type "${ field.type }" is not supported.`, { field } );

	return (
		<InputControl
			className="wc-settings-ui__control"
			label={ field.label }
			details={ getHelp( field.description ) }
			value={ toStringValue( value ) }
			disabled={ field.disabled }
			onChange={ ( event ) => onChange( event.target.value ) }
		/>
	);
};
