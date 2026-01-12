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
import {
	useGeneralSettings,
	type SettingsGroup,
	SettingsField,
} from './hooks/use-general-settings';
import { baseFieldTransformer, createChildrenWithRows } from './utils';
import './settings-general-main.scss';

/**
 * Row configuration for grouping fields into rows.
 */
const rowConfigurations: Record<
	string,
	Array< { id: string; fields: string[] } >
> = {
	store_address: [
		{
			id: 'city_zipcode_row',
			fields: [ 'woocommerce_store_city', 'woocommerce_store_postcode' ],
		},
	],
	pricing_options: [
		{
			id: 'separators_row',
			fields: [
				'woocommerce_price_thousand_sep',
				'woocommerce_price_decimal_sep',
			],
		},
		{
			id: 'position_decimals_row',
			fields: [
				'woocommerce_currency_pos',
				'woocommerce_price_num_decimals',
			],
		},
	],
	time_date_format_settings: [
		{
			id: 'datetime_formats_row',
			fields: [ 'date_format', 'time_format' ],
		},
	],
};

const localTransformer = ( field: SettingsField ) => {
	const baseField = baseFieldTransformer( field );
	if ( field.type === 'multiselect' ) {
		return {
			...baseField,
			isVisible: ( item: Record< string, string | string[] > ) => {
				if ( field.id === 'woocommerce_specific_ship_to_countries' ) {
					return item.woocommerce_ship_to_countries === 'specific';
				}

				if ( field.id === 'woocommerce_all_except_countries' ) {
					return item.woocommerce_allowed_countries === 'all_except';
				}

				if ( field.id === 'woocommerce_specific_allowed_countries' ) {
					return item.woocommerce_allowed_countries === 'specific';
				}
				return true;
			},
		};
	}
	return baseField;
};

/**
 * Main component for the General Settings page.
 * Uses WordPress DataForms for rendering settings.
 */
export const SettingsGeneralMain = () => {
	const { data, isLoading, error, updateSettings, isSaving, saveError } =
		useGeneralSettings();

	const [ formData, setFormData ] = useState< Record< string, unknown > >(
		{}
	);
	const [ isDirty, setIsDirty ] = useState( false );
	const [ saveSuccess, setSaveSuccess ] = useState( false );

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
		const allFields = ( Object.values( data.groups ) as SettingsGroup[] )
			.flatMap( ( group ) => group.fields )
			.map( ( field ) => localTransformer( field ) );

		// Type assertion needed because baseFieldTransformer returns a generic object
		// that matches the Field structure expected by DataForm at runtime
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		return allFields as any;
	}, [ data ] );

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
	}, [ data ] );

	const handleChange = ( newData: Record< string, unknown > ) => {
		setFormData( { ...formData, ...newData } );
		setIsDirty( true );
		setSaveSuccess( false );
	};

	const handleSave = async () => {
		try {
			await updateSettings( formData );
			setIsDirty( false );
			setSaveSuccess( true );

			// Hide success message after 3 seconds.
			setTimeout( () => setSaveSuccess( false ), 3000 );
		} catch ( err ) {
			// Error is handled by the hook.
		}
	};

	if ( isLoading ) {
		return (
			<div className="woocommerce-settings-general">
				<div className="woocommerce-settings-general__loading">
					<Spinner />
					<p>{ __( 'Loading settings', 'woocommerce' ) }</p>
				</div>
			</div>
		);
	}

	if ( error ) {
		return (
			<div className="woocommerce-settings-general">
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

	return (
		<div className="woocommerce-settings-general">
			{ saveSuccess && (
				<Notice
					status="success"
					isDismissible
					onRemove={ () => setSaveSuccess( false ) }
				>
					{ __( 'Settings saved successfully.', 'woocommerce' ) }
				</Notice>
			) }

			{ saveError && (
				<Notice status="error" isDismissible={ false }>
					{ __(
						'Error saving settings. Please try again.',
						'woocommerce'
					) }
					{ saveError.message && (
						<p>
							<strong>{ __( 'Error:', 'woocommerce' ) }</strong>{ ' ' }
							{ saveError.message }
						</p>
					) }
				</Notice>
			) }

			<DataForm
				data={ formData }
				fields={ fields }
				form={ form }
				onChange={ handleChange }
			/>

			<div className="woocommerce-settings-general__actions">
				<Button
					variant="primary"
					onClick={ handleSave }
					disabled={ ! isDirty || isSaving }
					isBusy={ isSaving }
				>
					{ isSaving
						? __( 'Saving', 'woocommerce' )
						: __( 'Save changes', 'woocommerce' ) }
				</Button>
			</div>
		</div>
	);
};
