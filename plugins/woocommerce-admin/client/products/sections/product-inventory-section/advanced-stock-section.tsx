/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl, RadioControl } from '@wordpress/components';
import { Product } from '@woocommerce/data';
import { useFormContext } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { getCheckboxTracks } from '../utils';

export const AdvancedStockSection: React.FC = () => {
	const { getCheckboxControlProps, getInputProps, values } =
		useFormContext< Product >();

	const backordersProp = getInputProps( 'backorders' );
	// These properties cause issues with the RadioControl component.
	// A fix to form upstream would help if we can identify what type of input is used.
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	delete backordersProp.checked;
	delete backordersProp.value;

	return (
		<>
			{ values.manage_stock && (
				<RadioControl
					label={ __( 'When out of stock', 'woocommerce' ) }
					options={ [
						{
							label: __( 'Allow purchases', 'woocommerce' ),
							value: 'yes',
						},
						{
							label: __(
								'Allow purchases, but notify customers',
								'woocommerce'
							),
							value: 'notify',
						},
						{
							label: __( "Don't allow purchases", 'woocommerce' ),
							value: 'no',
						},
					] }
					{ ...backordersProp }
				/>
			) }
			<h4>{ __( 'Restrictions', 'woocommerce' ) }</h4>
			<CheckboxControl
				label={ __(
					'Limit purchases to 1 item per order',
					'woocommerce'
				) }
				{ ...getCheckboxControlProps(
					'sold_individually',
					getCheckboxTracks( 'sold_individually' )
				) }
			/>
		</>
	);
};
