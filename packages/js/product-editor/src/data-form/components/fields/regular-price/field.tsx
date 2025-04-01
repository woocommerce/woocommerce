/**
 * External dependencies
 */
import classNames from 'classnames';
import { useInstanceId } from '@wordpress/compose';
import { Product } from '@woocommerce/data';
import { createElement } from '@wordpress/element';
import { sprintf, __ } from '@wordpress/i18n';
import { DataFormControlProps } from '@wordpress/dataviews';
import {
	BaseControl,
	__experimentalInputControl as InputControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { Label } from '../../../../components/label/label';
import { useValidation } from '../../../../contexts/validation-context';
import { useCurrencyInputProps } from '../../../../hooks/use-currency-input-props';
import { sanitizeHTML } from '../../../../utils/sanitize-html';

export function RegularPriceBlockEdit( {
	data,
	onChange,
}: DataFormControlProps< Product > ) {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	const salePrice = data.sale_price;
	const regularPrice = data.regular_price;
	const isRequired = true;
	const label = __( 'Regular Price', 'woocommerce' );

	const inputProps = useCurrencyInputProps( {
		value: data.regular_price,
		onChange: ( nextValue ) => {
			onChange( {
				regular_price: nextValue ?? '',
			} );
		},
	} );

	const regularPriceId = useInstanceId(
		BaseControl,
		'wp-block-woocommerce-product-regular-price-field'
	) as string;

	const {
		ref: regularPriceRef,
		error: regularPriceValidationError,
		validate: validateRegularPrice,
	} = useValidation< Product >(
		`regular_price`,
		async function regularPriceValidator() {
			const listPrice = Number.parseFloat( regularPrice );
			if ( listPrice ) {
				if ( listPrice < 0 ) {
					return {
						message: __(
							'Regular price must be greater than or equals to zero.',
							'woocommerce'
						),
					};
				}
				if (
					salePrice &&
					listPrice <= Number.parseFloat( salePrice )
				) {
					return {
						message: __(
							'Regular price must be greater than the sale price.',
							'woocommerce'
						),
					};
				}
			} else if ( isRequired ) {
				return {
					message: sprintf(
						/* translators: label of required field. */
						__( '%s is required.', 'woocommerce' ),
						label
					),
				};
			}
		},
		[ regularPrice, salePrice ]
	);

	function renderHelp() {
		if ( regularPriceValidationError ) {
			return (
				<span
					dangerouslySetInnerHTML={ sanitizeHTML(
						regularPriceValidationError
					) }
				/>
			);
		}
	}

	return (
		<div>
			<BaseControl
				id={ regularPriceId }
				label={ <Label label={ label } required /> }
				help={
					regularPriceValidationError
						? regularPriceValidationError
						: renderHelp()
				}
				className={ classNames( {
					'has-error': regularPriceValidationError,
				} ) }
			>
				<InputControl
					{ ...inputProps }
					id={ regularPriceId }
					name={ 'regular_price' }
					inputMode="decimal"
					ref={ regularPriceRef }
					onChange={ ( nextValue ) => {
						onChange( {
							regular_price: nextValue ?? '',
						} );
					} }
					value={ regularPrice }
					// disabled={ disabled }
					onBlur={ () => validateRegularPrice() }
				/>
			</BaseControl>
		</div>
	);
}
