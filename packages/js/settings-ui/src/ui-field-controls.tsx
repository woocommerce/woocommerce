/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import type { DataFormControlProps, FieldValidity } from '@wordpress/dataviews';
import { Field, InputControl, Select, Textarea } from '@wordpress/ui';

/**
 * Internal dependencies
 */
import { fromLocalDatetime, toLocalDatetime } from './hidden-inputs';
import { toSanitizedHtmlNode } from './html';
import type {
	SettingsFieldValidity,
	SettingsUIField,
	SettingsValues,
} from './types';
import { toStringValue } from './values';

export type UIFieldControl =
	| 'text'
	| 'email'
	| 'url'
	| 'password'
	| 'tel'
	| 'time'
	| 'date'
	| 'datetime-local'
	| 'textarea'
	| 'select';

const validityRules = [ 'required', 'elements', 'custom' ] as const;

/**
 * Convert DataForm's generation-specific rule result to the stable Woo shape.
 */
export const flattenDataFormValidity = (
	validity?: FieldValidity
): SettingsFieldValidity | undefined => {
	for ( const state of [ 'invalid', 'validating' ] as const ) {
		for ( const rule of validityRules ) {
			const result = validity?.[ rule ];
			if ( result?.type === state ) {
				return { state, message: result.message };
			}
		}
	}

	for ( const rule of validityRules ) {
		const result = validity?.[ rule ];
		if ( result?.type === 'valid' ) {
			return { state: 'valid', message: result.message };
		}
	}

	return undefined;
};

const getValidationMessage = ( validity?: FieldValidity ) => {
	const flattened = flattenDataFormValidity( validity );
	if ( flattened?.state !== 'invalid' ) {
		return undefined;
	}

	return flattened.message || __( 'This field is required.', 'woocommerce' );
};

const getDescriptionIds = (
	descriptionId: string | undefined,
	errorId: string | undefined
) => [ descriptionId, errorId ].filter( Boolean ).join( ' ' ) || undefined;

const RichDetails = ( {
	description,
	id,
}: {
	description?: string;
	id: string;
} ) =>
	description ? (
		<span id={ id }>{ toSanitizedHtmlNode( description ) }</span>
	) : undefined;

const ValidationMessage = ( {
	id,
	message,
}: {
	id: string;
	message?: string;
} ) =>
	message ? (
		<p className="wc-settings-ui__validation-error" id={ id }>
			{ message }
		</p>
	) : null;

const updateFieldValue = (
	props: DataFormControlProps< SettingsValues >,
	value: unknown
) => {
	props.onChange(
		props.field.setValue( {
			item: props.data,
			value,
		} )
	);
};

const UIInputField = ( {
	control,
	settingsField,
	props,
}: {
	control: Exclude< UIFieldControl, 'textarea' | 'select' >;
	settingsField: SettingsUIField;
	props: DataFormControlProps< SettingsValues >;
} ) => {
	const { data, field, hideLabelFromVision, validity } = props;
	const rawValue = field.getValue( { item: data } );
	const value =
		control === 'datetime-local'
			? toLocalDatetime( rawValue )
			: toStringValue( rawValue );
	const message = getValidationMessage( validity );
	const descriptionId = settingsField.description
		? `${ field.id }-details`
		: undefined;
	const errorId = message ? `${ field.id }-validation-error` : undefined;

	return (
		<div className="wc-settings-ui__control">
			<InputControl
				id={ field.id }
				type={ control }
				label={ field.label }
				details={
					descriptionId ? (
						<RichDetails
							description={ settingsField.description }
							id={ descriptionId }
						/>
					) : undefined
				}
				value={ value }
				placeholder={ field.placeholder }
				disabled={ Boolean( settingsField.disabled ) }
				hideLabelFromVision={ hideLabelFromVision }
				aria-invalid={ message ? true : undefined }
				aria-describedby={ getDescriptionIds( descriptionId, errorId ) }
				onChange={ ( event ) => {
					const nextValue = event.currentTarget.value;
					updateFieldValue(
						props,
						control === 'datetime-local'
							? fromLocalDatetime( nextValue )
							: nextValue
					);
				} }
			/>
			<ValidationMessage id={ errorId || '' } message={ message } />
		</div>
	);
};

const UITextareaField = ( {
	settingsField,
	props,
}: {
	settingsField: SettingsUIField;
	props: DataFormControlProps< SettingsValues >;
} ) => {
	const { data, field, hideLabelFromVision, validity } = props;
	const message = getValidationMessage( validity );
	const descriptionId = settingsField.description
		? `${ field.id }-details`
		: undefined;
	const errorId = message ? `${ field.id }-validation-error` : undefined;

	return (
		<Field.Root className="wc-settings-ui__control">
			<Field.Label hideFromVision={ hideLabelFromVision }>
				{ field.label }
			</Field.Label>
			<Textarea
				id={ field.id }
				value={ toStringValue( field.getValue( { item: data } ) ) }
				placeholder={ field.placeholder }
				disabled={ Boolean( settingsField.disabled ) }
				aria-invalid={ message ? true : undefined }
				aria-describedby={ getDescriptionIds( descriptionId, errorId ) }
				onChange={ ( event ) =>
					updateFieldValue( props, event.currentTarget.value )
				}
			/>
			{ descriptionId ? (
				<Field.Details id={ descriptionId }>
					{ toSanitizedHtmlNode( settingsField.description || '' ) }
				</Field.Details>
			) : null }
			<ValidationMessage id={ errorId || '' } message={ message } />
		</Field.Root>
	);
};

const UISelectField = ( {
	settingsField,
	props,
}: {
	settingsField: SettingsUIField;
	props: DataFormControlProps< SettingsValues >;
} ) => {
	const { data, field, hideLabelFromVision, validity } = props;
	const items = settingsField.options || [];
	const value = toStringValue( field.getValue( { item: data } ) );
	const selectedItem = items.find( ( item ) => item.value === value ) ?? null;
	const message = getValidationMessage( validity );
	const labelId = `${ field.id }-label`;
	const descriptionId = settingsField.description
		? `${ field.id }-details`
		: undefined;
	const errorId = message ? `${ field.id }-validation-error` : undefined;

	return (
		<Field.Root className="wc-settings-ui__control">
			<Field.Label id={ labelId } hideFromVision={ hideLabelFromVision }>
				{ field.label }
			</Field.Label>
			<Select.Root
				items={ items }
				value={ selectedItem }
				disabled={ Boolean( settingsField.disabled ) }
				onValueChange={ ( item ) =>
					updateFieldValue( props, item?.value ?? '' )
				}
			>
				<Select.Trigger
					id={ field.id }
					placeholder={ field.placeholder }
					aria-labelledby={ labelId }
					aria-invalid={ message ? true : undefined }
					aria-describedby={ getDescriptionIds(
						descriptionId,
						errorId
					) }
				>
					{ selectedItem?.label }
				</Select.Trigger>
				<Select.Popup>
					{ items.map( ( item ) => (
						<Select.Item key={ item.value } value={ item }>
							{ item.label }
						</Select.Item>
					) ) }
				</Select.Popup>
			</Select.Root>
			{ descriptionId ? (
				<Field.Details id={ descriptionId }>
					{ toSanitizedHtmlNode( settingsField.description || '' ) }
				</Field.Details>
			) : null }
			<ValidationMessage id={ errorId || '' } message={ message } />
		</Field.Root>
	);
};

/**
 * Build a DataForm Edit override from the catalogue UI controls.
 *
 * Delete these generation-A overrides when DataForm can use the WordPress 7.1
 * components generation directly.
 */
export const createUIFieldEdit = (
	settingsField: SettingsUIField,
	control: UIFieldControl
) => {
	return function UIFieldEdit(
		props: DataFormControlProps< SettingsValues >
	) {
		if ( control === 'textarea' ) {
			return (
				<UITextareaField
					settingsField={ settingsField }
					props={ props }
				/>
			);
		}

		if ( control === 'select' ) {
			return (
				<UISelectField
					settingsField={ settingsField }
					props={ props }
				/>
			);
		}

		return (
			<UIInputField
				control={ control }
				settingsField={ settingsField }
				props={ props }
			/>
		);
	};
};
