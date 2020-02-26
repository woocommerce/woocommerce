/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useRef } from '@wordpress/element';
import { PanelBody, PanelRow } from 'wordpress-components';
import Button from '@woocommerce/base-components/button';
import TextInput from '@woocommerce/base-components/text-input';
import Label from '@woocommerce/base-components/label';
import PropTypes from 'prop-types';
import withComponentId from '@woocommerce/base-hocs/with-component-id';

/**
 * Internal dependencies
 */
import './style.scss';
import LoadingMask from '../../loading-mask';

const TotalsCouponCodeInput = ( {
	componentId,
	isLoading = false,
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
					htmlFor={ `wc-block-coupon-code__input-${ componentId }` }
				/>
			}
			initialOpen={ true }
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
							id={ `wc-block-coupon-code__input-${ componentId }` }
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
							disabled={ isLoading }
							onClick={ () => {
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

export default withComponentId( TotalsCouponCodeInput );
