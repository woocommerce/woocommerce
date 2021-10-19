/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useRef } from '@wordpress/element';
import Button from '@woocommerce/base-components/button';
import { ValidatedTextInput } from '@woocommerce/base-components/text-input';
import Label from '@woocommerce/base-components/label';
import LoadingMask from '@woocommerce/base-components/loading-mask';
import PropTypes from 'prop-types';
import { withInstanceId } from '@wordpress/compose';
import {
	ValidationInputError,
	useValidationContext,
} from '@woocommerce/base-context';
import { Panel } from '@woocommerce/blocks-checkout';

/**
 * Internal dependencies
 */
import './style.scss';

const TotalsCoupon = ( {
	instanceId,
	isLoading = false,
	initialOpen = false,
	onSubmit = () => {},
} ) => {
	const [ couponValue, setCouponValue ] = useState( '' );
	const currentIsLoading = useRef( false );
	const { getValidationError, getValidationErrorId } = useValidationContext();
	const validationError = getValidationError( 'coupon' );

	useEffect( () => {
		if ( currentIsLoading.current !== isLoading ) {
			if ( ! isLoading && couponValue && ! validationError ) {
				setCouponValue( '' );
			}
			currentIsLoading.current = isLoading;
		}
	}, [ isLoading, couponValue, validationError ] );

	const textInputId = `wc-block-components-totals-coupon__input-${ instanceId }`;

	return (
		<Panel
			className="wc-block-components-totals-coupon"
			hasBorder={ false }
			initialOpen={ initialOpen }
			title={
				<Label
					label={ __(
						'Coupon code',
						'woocommerce'
					) }
					screenReaderLabel={ __(
						'Apply a coupon code',
						'woocommerce'
					) }
					htmlFor={ textInputId }
				/>
			}
		>
			<LoadingMask
				screenReaderLabel={ __(
					'Applying coupon…',
					'woocommerce'
				) }
				isLoading={ isLoading }
				showSpinner={ false }
			>
				<div className="wc-block-components-totals-coupon__content">
					<form className="wc-block-components-totals-coupon__form">
						<ValidatedTextInput
							id={ textInputId }
							errorId="coupon"
							className="wc-block-components-totals-coupon__input"
							label={ __(
								'Enter code',
								'woocommerce'
							) }
							value={ couponValue }
							ariaDescribedBy={ getValidationErrorId(
								textInputId
							) }
							onChange={ ( newCouponValue ) => {
								setCouponValue( newCouponValue );
							} }
							validateOnMount={ false }
							focusOnMount={ true }
							showError={ false }
						/>
						<Button
							className="wc-block-components-totals-coupon__button"
							disabled={ isLoading || ! couponValue }
							showSpinner={ isLoading }
							onClick={ ( e ) => {
								e.preventDefault();
								onSubmit( couponValue );
							} }
							type="submit"
						>
							{ __( 'Apply', 'woocommerce' ) }
						</Button>
					</form>
					<ValidationInputError
						propertyName="coupon"
						elementId={ textInputId }
					/>
				</div>
			</LoadingMask>
		</Panel>
	);
};

TotalsCoupon.propTypes = {
	onSubmit: PropTypes.func,
	isLoading: PropTypes.bool,
};

export default withInstanceId( TotalsCoupon );
