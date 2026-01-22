/**
 * External dependencies
 */
import { useMemo, useState, useEffect } from '@wordpress/element';
import { Button, Notice, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { DataForm } from '@wordpress/dataviews';
import type { FormField } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type {
	FieldTransformer,
	ReactSettingsGroup,
	ReactSettingsResponse,
	RowConfigurations,
} from './types';
import { createChildrenWithRows } from './field-transformers';
import './react-settings.scss';

type ReactSettingsPageProps = {
	className?: string;
	data: ReactSettingsResponse | null;
	error: Error | null;
	fieldTransformer: FieldTransformer;
	isLoading: boolean;
	rowConfigurations?: RowConfigurations;
};

export const ReactSettingsPage = ( {
	className,
	data,
	error,
	fieldTransformer,
	isLoading,
	rowConfigurations = {},
}: ReactSettingsPageProps ) => {
	const wrapperClassName = [
		'modern-woocommerce-settings',
		className,
		'wc-settings-prevent-change-event',
	]
		.filter( Boolean )
		.join( ' ' );
	const [ formData, setFormData ] = useState< Record< string, unknown > >(
		{}
	);
	const [ isDirty, setIsDirty ] = useState( false );

	// Initialize form data when API data loads.
	useEffect( () => {
		if ( data?.values ) {
			setFormData( data.values );
			setIsDirty( false );
		}
	}, [ data ] );

	// Transform all fields from all groups into DataForm fields.
	const fields = useMemo( () => {
		if ( ! data?.groups ) {
			return [];
		}

		// Select only fields from all groups and transform them.
		const allFields = ( Object.values( data.groups ) as ReactSettingsGroup[] )
			.flatMap( ( group ) => group.fields )
			.map( ( field ) => fieldTransformer( field ) );

		// Type assertion needed because fieldTransformer returns a generic object
		// that matches the Field structure expected by DataForm at runtime
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		return allFields as any;
	}, [ data, fieldTransformer ] );

	// Build form structure with groups and field ordering.
	const form = useMemo( () => {
		const formStructure: {
			type: 'card';
			fields: FormField[];
		} = {
			type: 'card' as const,
			fields: [],
		};

		if ( ! data?.groups ) {
			return formStructure;
		}

		// Sort groups by order.
		const sortedGroups = Object.entries( data.groups ).sort(
			( [ , a ], [ , b ] ) => a.order - b.order
		);

		for ( const [ groupId, group ] of sortedGroups ) {
			// Get field IDs for this group.
			const orderedFieldIds = group.fields.map( ( field ) => field.id );

			// Create children with row configurations if available.
			const children = rowConfigurations[ groupId ]
				? createChildrenWithRows(
						orderedFieldIds,
						rowConfigurations[ groupId ]
				  )
				: orderedFieldIds;

			formStructure.fields.push( {
				id: groupId,
				layout: {
					type: 'card' as const,
				},
				children: children as FormField[],
				label: group.title,
			} as unknown as FormField );
		}

		return formStructure;
	}, [ data, rowConfigurations ] );

	const handleChange = ( newData: Record< string, unknown > ) => {
		setFormData( { ...formData, ...newData } );
		setIsDirty( true );
	};

	if ( isLoading ) {
		return (
			<div className={ wrapperClassName }>
				<div className="woocommerce-settings-general__loading">
					<Spinner />
					<p>{ __( 'Loading settings', 'woocommerce' ) }</p>
				</div>
			</div>
		);
	}

	if ( error ) {
		return (
			<div className={ wrapperClassName }>
				<Notice status="error" isDismissible={ false }>
					{ __(
						'Error loading settings. Please try refreshing the page.',
						'woocommerce'
					) }
					{ error.message && (
						<p>
							<strong>{ __( 'Error:', 'woocommerce' ) }</strong>{ ' ' }
							{ error.message }
						</p>
					) }
				</Notice>
			</div>
		);
	}

	if ( ! data ) {
		return null;
	}

	const fieldsForSubmit = ( Object.values( data.groups ) as ReactSettingsGroup[] )
		.flatMap( ( group ) => group.fields )
		.filter( ( field ) => field?.id );

	return (
		<div className={ wrapperClassName }>
			<DataForm
				data={ formData }
				fields={ fields }
				form={ form }
				onChange={ handleChange }
			/>

			{ fieldsForSubmit.map( ( field ) => {
				const rawValue = formData[ field.id ];
				if ( field.type === 'multiselect' ) {
					let values: unknown[] = [];
					if ( Array.isArray( rawValue ) ) {
						values = rawValue;
					} else if ( rawValue ) {
						values = [ rawValue ];
					}
					return values.map( ( value, index ) => (
						<input
							key={ `${ field.id }-${ index }` }
							type="hidden"
							name={ `${ field.id }[]` }
							value={ String( value ) }
						/>
					) );
				}

				let value = rawValue ?? '';
				if ( field.type === 'checkbox' ) {
					value = rawValue === true || rawValue === 'yes' ? 'yes' : 'no';
				}

				return (
					<input
						key={ field.id }
						type="hidden"
						name={ field.id }
						value={ String( value ) }
					/>
				);
			} ) }

			<div className="woocommerce-settings-general__actions">
				<Button
					variant="primary"
					type="submit"
					name="save"
					value="Save changes"
					disabled={ ! isDirty }
				>
					{ __( 'Save changes', 'woocommerce' ) }
				</Button>
			</div>
		</div>
	);
};
