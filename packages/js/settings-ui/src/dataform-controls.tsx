/**
 * External dependencies
 */
import type { DataFormControlProps, FieldValidity } from '@wordpress/dataviews';
import { createElement, RawHTML } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { InputControl, Notice } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import { sanitizeSettingsHtml } from './html';
import type {
	SettingsFieldComponent,
	SettingsFieldContext,
	SettingsUIField,
	SettingsValue,
	SettingsValues,
} from './types';

const getValidityMessage = ( validity?: FieldValidity ) => {
	const result = [
		validity?.required,
		validity?.elements,
		validity?.custom,
	].find( ( rule ) => rule && rule.type !== 'valid' );

	return result?.message;
};

const getDescription = ( description?: string ) =>
	description ? (
		<span
			dangerouslySetInnerHTML={ {
				__html: sanitizeSettingsHtml( description ),
			} }
		/>
	) : undefined;

export const createInfoEdit = ( settingsField: SettingsUIField ) =>
	function InfoEdit() {
		return (
			<div className="wc-settings-ui__info" id={ settingsField.id }>
				<strong>{ settingsField.label }</strong>
				{ settingsField.description ? (
					<RawHTML>
						{ sanitizeSettingsHtml( settingsField.description ) }
					</RawHTML>
				) : null }
			</div>
		);
	};

export const createDateTimeEdit = (
	settingsField: SettingsUIField,
	type: 'time' | 'datetime-local'
) =>
	function DateTimeEdit( {
		data,
		field,
		onChange,
		validity,
	}: DataFormControlProps< SettingsValues > ) {
		const value = field.getValue( { item: data } );
		const validityMessage = getValidityMessage( validity );

		return (
			<InputControl
				className="wc-settings-ui__control"
				type={ type }
				label={ field.label }
				details={
					validityMessage ||
					getDescription( settingsField.description )
				}
				value={ value === null ? '' : String( value ?? '' ) }
				placeholder={ field.placeholder }
				disabled={ settingsField.disabled }
				onChange={ ( event ) =>
					onChange(
						field.setValue( {
							item: data,
							value: event.target.value,
						} )
					)
				}
			/>
		);
	};

export const createUnsupportedEdit = ( settingsField: SettingsUIField ) =>
	function UnsupportedEdit() {
		return (
			<Notice.Root intent="error">
				<Notice.Description>
					{ sprintf(
						/* translators: %s: settings field type. */
						__(
							'The field type "%s" is not supported by the Settings UI.',
							'woocommerce'
						),
						settingsField.type
					) }
				</Notice.Description>
			</Notice.Root>
		);
	};

export const createSettingsComponentEdit = ( {
	component: Component,
	settingsField,
	initialValues,
	context,
}: {
	component: SettingsFieldComponent;
	settingsField: SettingsUIField;
	initialValues: SettingsValues;
	context: SettingsFieldContext;
} ) =>
	function SettingsComponentEdit( {
		data,
		field,
		onChange,
	}: DataFormControlProps< SettingsValues > ) {
		const value = field.getValue( { item: data } ) as SettingsValue;
		const setValue = ( fieldId: string, nextValue: SettingsValue ) =>
			onChange( { [ fieldId ]: nextValue } );

		return createElement( Component, {
			field: settingsField,
			value,
			onChange: ( nextValue: SettingsValue ) =>
				onChange(
					field.setValue( {
						item: data,
						value: nextValue,
					} )
				),
			values: data,
			initialValues,
			setValue,
			setValues: onChange,
			context,
		} );
	};
