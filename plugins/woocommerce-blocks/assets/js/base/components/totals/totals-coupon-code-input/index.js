/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
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

const TotalsCouponCodeInput = ( { componentId, onSubmit } ) => {
	const [ couponValue, setCouponValue ] = useState( '' );
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
			initialOpen={ false }
		>
			<PanelRow className="wc-block-coupon-code__row">
				<TextInput
					id={ `wc-block-coupon-code__input-${ componentId }` }
					className="wc-block-coupon-code__input"
					label={ __( 'Enter code', 'woo-gutenberg-products-block' ) }
					value={ couponValue }
					onChange={ ( newCouponValue ) =>
						setCouponValue( newCouponValue )
					}
				/>
				<Button
					className="wc-block-coupon-code__button"
					onClick={ () => {
						onSubmit( couponValue );
					} }
					type="submit"
				>
					{ __( 'Apply', 'woo-gutenberg-products-block' ) }
				</Button>
			</PanelRow>
		</PanelBody>
	);
};

TotalsCouponCodeInput.propTypes = {
	onSubmit: PropTypes.func,
};

export default withComponentId( TotalsCouponCodeInput );
