/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useRef } from '@wordpress/element';
import { PanelBody, PanelRow } from 'wordpress-components';
import { Button } from '@woocommerce/base-components/cart-checkout';
import TextInput from '@woocommerce/base-components/text-input';
import Label from '@woocommerce/base-components/label';
import { ValidationInputError } from '@woocommerce/base-components/validation';
import PropTypes from 'prop-types';
import { withInstanceId } from 'wordpress-compose';
import classnames from 'classnames';
import { useValidationContext } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import LoadingMask from '../../loading-mask';
import './style.scss';

const TotalsCouponCodeInput = ( {
	instanceId,
	isLoading = false,
	initialOpen,
	onSubmit = () => {},
} ) => {
	const [ couponValue, setCouponValue ] = useState( '' );
	const currentIsLoading = useRef( false );
	const {
		getValidationError,
		clearValidationError,
		getValidationErrorId,
	} = useValidationContext();
	const validationMessage = getValidationError( 'coupon' );

	useEffect( () => {
		if ( currentIsLoading.current !== isLoading ) {
			if ( ! isLoading && couponValue && ! validationMessage ) {
				setCouponValue( '' );
			}
			currentIsLoading.current = isLoading;
		}
	}, [ isLoading, couponValue, validationMessage ] );

	const textInputId = `wc-block-coupon-code__input-${ instanceId }`;

	return (
		<PanelBody
			className="wc-block-coupon-code"
			title={
				<Label
					label={ __(
						'Coupon Code?',
						'woo-gutenberg-products-block'
					) }
					screenReaderLabel={ __(
						'Introduce Coupon Code',
						'woo-gutenberg-products-block'
					) }
					htmlFor={ textInputId }
				/>
			}
			initialOpen={ initialOpen }
		>
			<LoadingMask
				screenReaderLabel={ __(
					'Applying coupon…',
					'woo-gutenberg-products-block'
				) }
				isLoading={ isLoading }
				showSpinner={ false }
			>
				<PanelRow className="wc-block-coupon-code__row">
					<form className="wc-block-coupon-code__form">
						<TextInput
							id={ textInputId }
							className={ classnames(
								'wc-block-coupon-code__input',
								{
									'has-error': !! validationMessage,
								}
							) }
							label={ __(
								'Enter code',
								'woo-gutenberg-products-block'
							) }
							value={ couponValue }
							ariaDescribedBy={ getValidationErrorId(
								textInputId
							) }
							onChange={ ( newCouponValue ) => {
								setCouponValue( newCouponValue );
								clearValidationError( 'coupon' );
							} }
						/>
						<Button
							className="wc-block-coupon-code__button"
							disabled={ isLoading || ! couponValue }
							onClick={ ( e ) => {
								e.preventDefault();
								onSubmit( couponValue );
							} }
							type="submit"
						>
							{ __( 'Apply', 'woo-gutenberg-products-block' ) }
						</Button>
					</form>
				</PanelRow>
				<ValidationInputError
					errorMessage={ validationMessage }
					elementId={ textInputId }
				/>
			</LoadingMask>
		</PanelBody>
	);
};

TotalsCouponCodeInput.propTypes = {
	onSubmit: PropTypes.func,
	isLoading: PropTypes.bool,
};

export default withInstanceId( TotalsCouponCodeInput );
