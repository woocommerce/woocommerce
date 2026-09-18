/**
 * External dependencies
 */
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { useEffect, useRef, useState } from '@wordpress/element';
import Button from '@woocommerce/base-components/button';
import LoadingMask from '@woocommerce/base-components/loading-mask';
import {
	TextInput,
	ValidationInputError,
	Panel,
	Spinner,
} from '@woocommerce/blocks-components';
import { useSelect } from '@wordpress/data';
import { checkoutStore } from '@woocommerce/block-data';
import type { MouseEvent, MouseEventHandler } from 'react';

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
	 * Whether the coupon form is hidden
	 */
	displayCouponForm?: boolean;
	/**
	 * Submit handler. Resolves true when the coupon was applied. Rejects with
	 * an Error whose message is shown under the input when it was not.
	 */
	onSubmit?: ( couponValue: string ) => Promise< boolean > | undefined;
}

export const TotalsCoupon = ( {
	instanceId,
	isLoading = false,
	onSubmit,
	displayCouponForm = false,
}: TotalsCouponProps ): JSX.Element => {
	const [ couponValue, setCouponValue ] = useState( '' );
	const [ errorMessage, setErrorMessage ] = useState( '' );
	const [ isCouponFormVisible, setIsCouponFormVisible ] =
		useState( displayCouponForm );
	const textInputId = `wc-block-components-totals-coupon__input-${ instanceId }`;
	const errorId = `wc-block-components-totals-coupon__error-${ instanceId }`;
	const inputRef = useRef< HTMLInputElement >( null );
	const isCheckoutIdle = useSelect(
		( select ) => select( checkoutStore ).isIdle(),
		[]
	);
	const hasError = errorMessage !== '';

	useEffect( () => {
		if ( isCouponFormVisible ) {
			inputRef.current?.focus();
		}
	}, [ isCouponFormVisible ] );

	// The message only concerns this form. Drop it once the shopper places the
	// order so a later checkout error does not read as a coupon problem.
	useEffect( () => {
		if ( ! isCheckoutIdle ) {
			setErrorMessage( '' );
		}
	}, [ isCheckoutIdle ] );

	const handleCouponSubmit: MouseEventHandler< HTMLButtonElement > = (
		e: MouseEvent< HTMLButtonElement >
	) => {
		e.preventDefault();
		if ( typeof onSubmit === 'undefined' ) {
			setCouponValue( '' );
			setIsCouponFormVisible( true );
			return;
		}
		setErrorMessage( '' );
		void onSubmit( couponValue )
			?.then( ( result ) => {
				if ( result ) {
					setCouponValue( '' );
					setIsCouponFormVisible( false );
				} else {
					inputRef.current?.focus();
				}
			} )
			.catch( ( error: Error ) => {
				setErrorMessage( error.message );
				inputRef.current?.focus();
			} );
	};

	return (
		<Panel
			className="wc-block-components-totals-coupon"
			initialOpen={ isCouponFormVisible }
			hasBorder={ false }
			headingLevel={ 2 }
			title={ __( 'Add coupons', 'woocommerce' ) }
			state={ [ isCouponFormVisible, setIsCouponFormVisible ] }
		>
			<LoadingMask
				screenReaderLabel={ __( 'Applying coupon…', 'woocommerce' ) }
				isLoading={ isLoading }
				showSpinner={ false }
			>
				<div className="wc-block-components-totals-coupon__content">
					<form
						className="wc-block-components-totals-coupon__form"
						id="wc-block-components-totals-coupon__form"
					>
						<TextInput
							id={ textInputId }
							className={ clsx(
								'wc-block-components-totals-coupon__input',
								{ 'has-error': hasError }
							) }
							label={ __( 'Enter code', 'woocommerce' ) }
							value={ couponValue }
							ariaDescribedBy={ hasError ? errorId : undefined }
							aria-invalid={ hasError }
							aria-errormessage={ hasError ? errorId : undefined }
							onChange={ ( newCouponValue ) => {
								setErrorMessage( '' );
								setCouponValue( newCouponValue );
							} }
							ref={ inputRef }
						/>
						<Button
							className={ clsx(
								'wc-block-components-totals-coupon__button',
								{
									'wc-block-components-totals-coupon__button--loading':
										isLoading,
								}
							) }
							disabled={ isLoading || ! couponValue }
							onClick={ handleCouponSubmit }
							type="submit"
						>
							{ isLoading && <Spinner /> }
							{ __( 'Apply', 'woocommerce' ) }
						</Button>
					</form>
					{ hasError && (
						<ValidationInputError
							errorMessage={ errorMessage }
							id={ errorId }
						/>
					) }
				</div>
			</LoadingMask>
		</Panel>
	);
};

export default TotalsCoupon;
