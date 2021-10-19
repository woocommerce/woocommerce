/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useExpressPaymentMethods,
	usePaymentMethodInterface,
} from '@woocommerce/base-context/hooks';
import {
	cloneElement,
	isValidElement,
	useCallback,
	useRef,
} from '@wordpress/element';
import {
	useEditorContext,
	usePaymentMethodDataContext,
} from '@woocommerce/base-context';
import deprecated from '@wordpress/deprecated';

/**
 * Internal dependencies
 */
import PaymentMethodErrorBoundary from './payment-method-error-boundary';

const ExpressPaymentMethods = () => {
	const { isEditor } = useEditorContext();
	const {
		setActivePaymentMethod,
		setExpressPaymentError,
		activePaymentMethod,
		paymentMethodData,
		setPaymentStatus,
	} = usePaymentMethodDataContext();
	const paymentMethodInterface = usePaymentMethodInterface();
	const { paymentMethods } = useExpressPaymentMethods();
	const previousActivePaymentMethod = useRef( activePaymentMethod );
	const previousPaymentMethodData = useRef( paymentMethodData );

	/**
	 * onExpressPaymentClick should be triggered when the express payment button is clicked.
	 *
	 * This will store the previous active payment method, set the express method as active, and set the payment status to started.
	 */
	const onExpressPaymentClick = useCallback(
		( paymentMethodId ) => () => {
			previousActivePaymentMethod.current = activePaymentMethod;
			previousPaymentMethodData.current = paymentMethodData;
			setPaymentStatus().started( {} );
			setActivePaymentMethod( paymentMethodId );
		},
		[
			activePaymentMethod,
			paymentMethodData,
			setActivePaymentMethod,
			setPaymentStatus,
		]
	);

	/**
	 * onExpressPaymentClose should be triggered when the express payment process is cancelled or closed.
	 *
	 * This restores the active method and returns the state to pristine.
	 */
	const onExpressPaymentClose = useCallback( () => {
		setPaymentStatus().pristine();
		setActivePaymentMethod( previousActivePaymentMethod.current );
		if ( previousPaymentMethodData.current.isSavedToken ) {
			setPaymentStatus().started( previousPaymentMethodData.current );
		}
	}, [ setActivePaymentMethod, setPaymentStatus ] );

	/**
	 * onExpressPaymentError should be triggered when the express payment process errors.
	 *
	 * This shows an error message then restores the active method and returns the state to pristine.
	 */
	const onExpressPaymentError = useCallback(
		( errorMessage ) => {
			setPaymentStatus().error( errorMessage );
			setExpressPaymentError( errorMessage );
			setActivePaymentMethod( previousActivePaymentMethod.current );
			if ( previousPaymentMethodData.current.isSavedToken ) {
				setPaymentStatus().started( previousPaymentMethodData.current );
			}
		},
		[ setActivePaymentMethod, setPaymentStatus, setExpressPaymentError ]
	);

	/**
	 * Calling setExpressPaymentError directly is deprecated.
	 */
	const deprecatedSetExpressPaymentError = useCallback(
		( errorMessage = '' ) => {
			deprecated(
				'Express Payment Methods should use the provided onError handler instead.',
				{
					alternative: 'onError',
					plugin: 'woocommerce-gutenberg-products-block',
					link:
						'https://github.com/woocommerce/woocommerce-gutenberg-products-block/pull/4228',
				}
			);
			if ( errorMessage ) {
				onExpressPaymentError( errorMessage );
			} else {
				setExpressPaymentError( '' );
			}
		},
		[ setExpressPaymentError, onExpressPaymentError ]
	);

	/**
	 * @todo Find a way to Memoize Express Payment Method Content
	 *
	 * Payment method content could potentially become a bottleneck if lots of logic is ran in the content component. It
	 * Currently re-renders excessively but is not easy to useMemo because paymentMethodInterface could become stale.
	 * paymentMethodInterface itself also updates on most renders.
	 */
	const entries = Object.entries( paymentMethods );
	const content =
		entries.length > 0 ? (
			entries.map( ( [ id, paymentMethod ] ) => {
				const expressPaymentMethod = isEditor
					? paymentMethod.edit
					: paymentMethod.content;
				return isValidElement( expressPaymentMethod ) ? (
					<li key={ id } id={ `express-payment-method-${ id }` }>
						{ cloneElement( expressPaymentMethod, {
							...paymentMethodInterface,
							onClick: onExpressPaymentClick( id ),
							onClose: onExpressPaymentClose,
							onError: onExpressPaymentError,
							setExpressPaymentError: deprecatedSetExpressPaymentError,
						} ) }
					</li>
				) : null;
			} )
		) : (
			<li key="noneRegistered">
				{ __(
					'No registered Payment Methods',
					'woocommerce'
				) }
			</li>
		);

	return (
		<PaymentMethodErrorBoundary isEditor={ isEditor }>
			<ul className="wc-block-components-express-payment__event-buttons">
				{ content }
			</ul>
		</PaymentMethodErrorBoundary>
	);
};

export default ExpressPaymentMethods;
