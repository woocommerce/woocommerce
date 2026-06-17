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
import { createElement, RawHTML } from '@wordpress/element';

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
			<TextareaControl
				className="wc-settings-ui__control"
				label={ field.label }
				help={ getHelp( field.description ) }
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
				className="wc-settings-ui__control"
				label={ field.label }
				help={ getHelp( field.description ) }
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
		return (
			<NumberSpinControl
				id={ field.id }
				label={ field.label }
				help={ getHelp( field.description ) }
				value={ toStringValue( value ) }
				placeholder={ field.placeholder }
				disabled={ field.disabled }
				onChange={ onChange }
				inputAttributes={ field.customAttributes }
			/>
		);
	}

	if ( isTextInputType( field.type ) ) {
		return (
			<TextControl
				className="wc-settings-ui__control"
				type={ field.type }
				label={ field.label }
				help={ getHelp( field.description ) }
				value={ toStringValue( value ) }
				placeholder={ field.placeholder }
				disabled={ field.disabled }
				onChange={ onChange }
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				{ ...field.customAttributes }
			/>
		);
	}

	warn( `Field type "${ field.type }" is not supported.`, { field } );

	return (
		<TextControl
			className="wc-settings-ui__control"
			label={ field.label }
			help={ getHelp( field.description ) }
			value={ toStringValue( value ) }
			disabled={ field.disabled }
			onChange={ onChange }
			__next40pxDefaultSize
			__nextHasNoMarginBottom
		/>
	);
};
