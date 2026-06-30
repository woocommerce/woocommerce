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
	label: __( 'Download limit', 'woocommerce' ),
	enableSorting: false,
	filterBy: false,
	isVisible: ( item ) => !! item.downloadable,
	getValue: ( { item } ) => item.download_limit ?? -1,
	Edit: ( { data, onChange, field } ) => {
		const isLimited = ( data.download_limit ?? -1 ) !== -1;
		return (
			<div
				className="woocommerce-fields-field__download-limit"
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '12px',
					marginBottom: isLimited ? '8px' : 0,
				} }
			>
				<CheckboxControl
					__nextHasNoMarginBottom
					label={ __(
						'Limit downloads per customer',
						'woocommerce'
					) }
					checked={ isLimited }
					onChange={ ( checked ) => {
						onChange( {
							download_limit: checked ? 1 : -1,
						} );
					} }
				/>
				{ isLimited && (
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ field.label }
						type="number"
						min={ 1 }
						value={ String( data.download_limit ) }
						onChange={ ( next ) => {
							const parsed = parseInt( next, 10 );
							onChange( {
								download_limit: Number.isNaN( parsed )
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
