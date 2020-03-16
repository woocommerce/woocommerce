/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useRef } from '@wordpress/element';
import { PanelBody, PanelRow } from 'wordpress-components';
import { Button } from '@woocommerce/base-components/cart-checkout';
import TextInput from '@woocommerce/base-components/text-input';
import Label from '@woocommerce/base-components/label';
import PropTypes from 'prop-types';
import { withInstanceId } from 'wordpress-compose';

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

	useEffect( () => {
		if ( currentIsLoading.current !== isLoading ) {
			if ( ! isLoading && couponValue ) {
				setCouponValue( '' );
			}
			currentIsLoading.current = isLoading;
		}
	}, [ isLoading, couponValue ] );

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
					htmlFor={ `wc-block-coupon-code__input-${ instanceId }` }
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
							id={ `wc-block-coupon-code__input-${ instanceId }` }
							className="wc-block-coupon-code__input"
							label={ __(
								'Enter code',
								'woo-gutenberg-products-block'
							) }
							value={ couponValue }
							onChange={ ( newCouponValue ) =>
								setCouponValue( newCouponValue )
							}
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
			</LoadingMask>
		</PanelBody>
	);
};

TotalsCouponCodeInput.propTypes = {
	onSubmit: PropTypes.func,
	isLoading: PropTypes.bool,
};

export default withInstanceId( TotalsCouponCodeInput );
