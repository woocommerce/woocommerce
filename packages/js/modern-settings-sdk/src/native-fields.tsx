/**
 * External dependencies
 */
import {
	BaseControl,
	CheckboxControl,
	SelectControl,
	TextControl,
	TextareaControl,
} from '@wordpress/components';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { warn } from './diagnostics';
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

export const NativeSettingsField = ( {
	field,
	value,
	onChange,
}: SettingsFieldComponentProps ) => {
	if ( field.type === 'info' ) {
		return (
			<div id={ field.id }>
				<strong>{ field.label }</strong>
				{ field.description ? <p>{ field.description }</p> : null }
			</div>
		);
	}

	if ( field.type === 'checkbox' ) {
		return (
			<CheckboxControl
				label={ field.label }
				help={ field.description }
				checked={ value === true || value === 'yes' || value === '1' }
				disabled={ field.disabled }
				onChange={ onChange }
				__nextHasNoMarginBottom
			/>
		);
	}

	if ( field.type === 'textarea' ) {
		return (
			<TextareaControl
				label={ field.label }
				help={ field.description }
				value={ toStringValue( value ) }
				placeholder={ field.placeholder }
				disabled={ field.disabled }
				onChange={ onChange }
				__nextHasNoMarginBottom
			/>
		);
	}

	if ( field.type === 'select' || field.type === 'radio' ) {
		return (
			<SelectControl
				label={ field.label }
				help={ field.description }
				value={ toStringValue( value ) }
				options={ field.options || [] }
				disabled={ field.disabled }
				onChange={ onChange }
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>
		);
	}

	if ( field.type === 'array' ) {
		const selectedValues = Array.isArray( value ) ? value : [];

		return (
			<BaseControl
				id={ field.id }
				label={ field.label }
				help={ field.description }
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

	if ( isTextInputType( field.type ) ) {
		return (
			<TextControl
				type={ field.type }
				label={ field.label }
				help={ field.description }
				value={ toStringValue( value ) }
				placeholder={ field.placeholder }
				disabled={ field.disabled }
				onChange={ onChange }
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>
		);
	}

	warn( `Field type "${ field.type }" is not supported.`, { field } );

	return (
		<TextControl
			label={ field.label }
			help={ field.description }
			value={ toStringValue( value ) }
			disabled={ field.disabled }
			onChange={ onChange }
			__next40pxDefaultSize
			__nextHasNoMarginBottom
		/>
	);
};
