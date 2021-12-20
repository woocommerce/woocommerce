/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useRef } from '@wordpress/element';
import Button from '@woocommerce/base-components/button';
import { ValidatedTextInput } from '@woocommerce/base-components/text-input';
import Label from '@woocommerce/base-components/label';
import LoadingMask from '@woocommerce/base-components/loading-mask';
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

export interface TotalsCouponProps {
	/**
	 * Instance id of the input
	 */
	instanceId: string;
	/**
	 * Whether the component is in a loading state
	 */
	isLoading?: boolean;
	/**
	 * Whether the component's parent panel will begin in an open state
	 */
	initialOpen?: boolean;
	/**
	 * Submit handler
	 */
	onSubmit?: ( couponValue: string ) => void;
}

export const TotalsCoupon = ( {
	instanceId,
	isLoading = false,
	initialOpen = false,
	onSubmit = () => void 0,
}: TotalsCouponProps ): JSX.Element => {
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
						'woo-gutenberg-products-block'
					) }
					screenReaderLabel={ __(
						'Apply a coupon code',
						'woo-gutenberg-products-block'
					) }
					htmlFor={ textInputId }
				/>
			}
		>
			<LoadingMask
				screenReaderLabel={ __(
					'Applying coupon…',
					'woo-gutenberg-products-block'
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
								'woo-gutenberg-products-block'
							) }
							value={ couponValue }
							ariaDescribedBy={ getValidationErrorId(
								textInputId
							) }
							onChange={ ( newCouponValue ) => {
								setCouponValue( newCouponValue );
							} }
							focusOnMount={ true }
							showError={ false }
						/>
						<Button
							className="wc-block-components-totals-coupon__button"
							disabled={ isLoading || ! couponValue }
							showSpinner={ isLoading }
							onClick={ (
								e: React.MouseEvent< HTMLElement, 'click' >
							) => {
								e.preventDefault();
								onSubmit( couponValue );
							} }
							type="submit"
						>
							{ __( 'Apply', 'woo-gutenberg-products-block' ) }
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

export default withInstanceId( TotalsCoupon );
