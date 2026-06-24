/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';
import { InputControl, InputLayout } from '@wordpress/ui';
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
			<div
				className="woocommerce-fields-field__download-expiry"
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '12px',
				} }
			>
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
					<InputControl
						label={ field.label }
						type="number"
						min={ 1 }
						value={ String( data.download_expiry ) }
						onChange={ ( event ) => {
							const parsed = parseInt( event.target.value, 10 );
							onChange( {
								download_expiry:
									Number.isNaN( parsed ) || parsed < 1
										? 1
										: parsed,
							} );
						} }
						suffix={
							<InputLayout.Slot padding="minimal">
								{ __( 'days', 'woocommerce' ) }
							</InputLayout.Slot>
						}
					/>
				) }
			</div>
		);
	},
};
