/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { createElement, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { HiddenInputs } from './hidden-inputs';
import { NativeSettingsField } from './native-fields';
import { resolveFieldComponent } from './registry';
import type {
	ModernSettingsSchema,
	SettingsFieldContext,
	SettingsValue,
} from './types';

type Values = Record< string, SettingsValue >;

const getInitialValues = ( schema: ModernSettingsSchema ): Values => {
	const values: Values = {};

	Object.values( schema.groups ).forEach( ( group ) => {
		group.fields.forEach( ( field ) => {
			values[ field.id ] =
				typeof field.value === 'undefined' ? '' : field.value;
		} );
	} );

	return values;
};

const getFieldTypeClassName = ( type: string ) =>
	`wc-modern-settings__field--${ type.replace( /[^a-z0-9_-]/gi, '-' ) }`;

export const ModernSettingsPage = ( {
	schema,
	page,
	section,
}: {
	schema: ModernSettingsSchema;
	page?: string;
	section?: string;
} ) => {
	const [ values, setValues ] = useState< Values >( () =>
		getInitialValues( schema )
	);
	const [ isDirty, setIsDirty ] = useState( false );
	const context: SettingsFieldContext = useMemo(
		() => ( {
			page: page || schema.id,
			section: section || schema.section,
		} ),
		[ page, schema.id, schema.section, section ]
	);

	return (
		<div className="wc-modern-settings">
			{ Object.values( schema.groups ).map( ( group ) => (
				<section className="wc-modern-settings__group" key={ group.id }>
					<div className="wc-modern-settings__group-header">
						{ group.title ? <h2>{ group.title }</h2> : null }
						{ group.description ? (
							<p>{ group.description }</p>
						) : null }
					</div>
					<div className="wc-modern-settings__group-panel">
						{ group.fields.map( ( field ) => {
							const FieldComponent =
								resolveFieldComponent( field, context ) ||
								NativeSettingsField;
							const value = values[ field.id ];

							return (
								<div
									className={ [
										'wc-modern-settings__field',
										getFieldTypeClassName( field.type ),
									].join( ' ' ) }
									key={ field.id }
								>
									<FieldComponent
										field={ field }
										value={ value }
										context={ context }
										onChange={ (
											nextValue: SettingsValue
										) => {
											setValues( {
												...values,
												[ field.id ]: nextValue,
											} );
											setIsDirty( true );
										} }
									/>
									<HiddenInputs
										field={ field }
										value={ value }
									/>
								</div>
							);
						} ) }
					</div>
				</section>
			) ) }
			<div className="wc-modern-settings__actions">
				<Button
					variant="primary"
					type="submit"
					name="save"
					value={ __( 'Save changes', 'woocommerce' ) }
					disabled={ ! isDirty }
				>
					{ __( 'Save changes', 'woocommerce' ) }
				</Button>
			</div>
		</div>
	);
};
