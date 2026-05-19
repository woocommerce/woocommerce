/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl, TextControl } from '@wordpress/components';
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';

export const fieldExtensions: Partial< Field< ProductEntityRecord > > = {
	type: 'integer',
	label: __( 'Download expiry', 'woocommerce' ),
	enableSorting: false,
	filterBy: false,
	isVisible: ( item ) => !! item.downloadable,
	getValue: ( { item } ) => item.download_expiry ?? -1,
	Edit: ( { data, onChange, field } ) => {
		const hasExpiry = ( data.download_expiry ?? -1 ) !== -1;
		return (
			<div className="woocommerce-fields-field__download-expiry">
				<CheckboxControl
					__nextHasNoMarginBottom
					label={ __( 'Expire download link', 'woocommerce' ) }
					checked={ hasExpiry }
					onChange={ ( checked ) => {
						onChange( {
							download_expiry: checked ? 1 : -1,
						} );
					} }
				/>
				{ hasExpiry && (
					<TextControl
						__nextHasNoMarginBottom
						label={ field.label }
						hideLabelFromVision
						type="number"
						min={ 1 }
						value={ String( data.download_expiry ) }
						onChange={ ( next ) => {
							const parsed = parseInt( next, 10 );
							onChange( {
								download_expiry: Number.isNaN( parsed )
									? 1
									: parsed,
							} );
						} }
					/>
				) }
			</div>
		);
	},
};
