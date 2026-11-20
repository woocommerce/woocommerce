/**
 * External dependencies
 */
import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import {
	createContext,
	useContext,
	useState,
	useRef,
} from '@wordpress/element';
import Button from '@woocommerce/base-components/button';
import LoadingMask from '@woocommerce/base-components/loading-mask';
import {
	ValidatedTextInput,
	ValidationInputError,
	ValidatedTextInputHandle,
	Panel,
	Spinner,
} from '@woocommerce/blocks-components';
import { useSelect } from '@wordpress/data';
import { validationStore } from '@woocommerce/block-data';
import type { FormEventHandler, JSX, ReactNode, RefObject } from 'react';
import type { StyleProps } from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import './style.scss';

const CouponContext = createContext< {
	couponValue: string;
	setCouponValue: ( value: string ) => void;
	isLoading: boolean;
	instanceId: string;
	validationErrorId?: string;
	inputRef: RefObject< ValidatedTextInputHandle >;
} | null >( null );

export const CouponInput = (): JSX.Element | null => {
	const context = useContext( CouponContext );
	if ( ! context ) {
		return null;
	}
	const {
		couponValue,
		setCouponValue,
		instanceId,
		validationErrorId,
		inputRef,
	} = context;
	return (
		<ValidatedTextInput
			id={ `wc-block-components-totals-coupon__input-${ instanceId }` }
			errorId="coupon"
			className="wc-block-components-totals-coupon__input"
			label={ __( 'Enter code', 'woocommerce' ) }
			value={ couponValue }
			ariaDescribedBy={ validationErrorId || '' }
			onChange={ setCouponValue }
			focusOnMount={ true }
			validateOnMount={ false }
			showError={ false }
			ref={ inputRef }
		/>
	);
};

export const CouponButton = ( {
	buttonStyleProps,
}: {
	buttonStyleProps?: StyleProps;
} ): JSX.Element | null => {
	const context = useContext( CouponContext );
	if ( ! context ) {
		return null;
	}
	const { couponValue, isLoading } = context;
	return (
		<Button
			className={ clsx(
				'wc-block-components-totals-coupon__button',
				buttonStyleProps?.className,
				{
					'wc-block-components-totals-coupon__button--loading':
						isLoading,
				}
			) }
			style={ buttonStyleProps?.style }
			disabled={ isLoading || ! couponValue }
			type="submit"
		>
			{ isLoading && <Spinner /> }
			{ __( 'Apply', 'woocommerce' ) }
		</Button>
	);
};

export interface TotalsCouponProps {
	children?: ReactNode;
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
	 * Submit handler
	 */
	onSubmit?: ( couponValue: string ) => Promise< boolean > | undefined;
}

export const TotalsCoupon = ( {
	instanceId,
	isLoading = false,
	onSubmit,
	displayCouponForm = false,
	children,
}: TotalsCouponProps ): JSX.Element => {
	const [ couponValue, setCouponValue ] = useState( '' );
	const [ isCouponFormVisible, setIsCouponFormVisible ] =
		useState( displayCouponForm );
	const { validationErrorId } = useSelect(
		( select ) => {
			const store = select( validationStore );
			return {
				validationErrorId: store.getValidationErrorId( instanceId ),
			};
		},
		[ instanceId ]
	);
	const inputRef = useRef< ValidatedTextInputHandle >( null );

	const handleCouponSubmit: FormEventHandler< HTMLFormElement > = ( e ) => {
		e.preventDefault();
		if ( isLoading || ! couponValue ) {
			return;
		}
		if ( typeof onSubmit !== 'undefined' ) {
			void onSubmit( couponValue )?.then( ( result ) => {
				if ( result ) {
					setCouponValue( '' );
					setIsCouponFormVisible( false );
				} else if ( inputRef.current?.focus ) {
					inputRef.current.focus();
				}
			} );
		} else {
			setCouponValue( '' );
			setIsCouponFormVisible( true );
		}
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
					<CouponContext.Provider
						value={ {
							couponValue,
							setCouponValue,
							isLoading,
							instanceId,
							validationErrorId,
							inputRef,
						} }
					>
						<form
							className="wc-block-components-totals-coupon__form"
							id="wc-block-components-totals-coupon__form"
							onSubmit={ handleCouponSubmit }
						>
							{ children ?? (
								<>
									<CouponInput />
									<CouponButton />
								</>
							) }
						</form>
					</CouponContext.Provider>
					<ValidationInputError
						propertyName="coupon"
						elementId={ instanceId }
					/>
				</div>
			</LoadingMask>
		</Panel>
	);
};

export default TotalsCoupon;
