/* eslint-disable @wordpress/no-unsafe-wp-apis */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getCurrency } from '@woocommerce/price-format';
import {
	// @ts-expect-error Using experimental features
	__experimentalNumberControl as NumberControl,
	// @ts-expect-error Using experimental features
	__experimentalInputControlPrefixWrapper as InputControlPrefixWrapper,
	// @ts-expect-error Using experimental features
	__experimentalInputControlSuffixWrapper as InputControlSuffixWrapper,
} from '@wordpress/components';

interface PriceTextFieldProps {
	value: number;
	onChange: ( value: number ) => void;
	label?: string;
	suffix?: string;
}

const PriceTextField: React.FC< PriceTextFieldProps > = ( {
	value,
	onChange,
	label,
} ) => {
	const currency = getCurrency();

	return (
		<NumberControl
			value={ value }
			onChange={ ( val: string ) => {
				onChange( Number( val ) );
			} }
			label={ label }
			prefix={
				<InputControlPrefixWrapper>{ label }</InputControlPrefixWrapper>
			}
			suffix={
				<InputControlSuffixWrapper>
					{ currency?.symbol }
				</InputControlSuffixWrapper>
			}
			placeholder={ __( 'Auto', 'woo-gutenberg-products-block' ) }
			isPressEnterToChange
			hideLabelFromVision
			hideHTMLArrows
			type="number"
			min={ 0 }
			style={ {
				textAlign: 'right',
			} }
			__next40pxDefaultSize
			step="any"
		/>
	);
};

export default PriceTextField;
