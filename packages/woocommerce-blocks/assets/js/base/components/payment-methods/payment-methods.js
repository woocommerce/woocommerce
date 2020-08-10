/**
 * External dependencies
 */
import {
	usePaymentMethods,
	usePaymentMethodInterface,
	useStoreNotices,
	useEmitResponse,
} from '@woocommerce/base-hooks';
import {
	cloneElement,
	useRef,
	useEffect,
	useState,
	useCallback,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	useCheckoutContext,
	useEditorContext,
	usePaymentMethodDataContext,
} from '@woocommerce/base-context';
import CheckboxControl from '@woocommerce/base-components/checkbox-control';

/**
 * Internal dependencies
 */
import Tabs from '../tabs';
import NoPaymentMethods from './no-payment-methods';
import SavedPaymentMethodOptions from './saved-payment-method-options';
import PaymentMethodErrorBoundary from './payment-method-error-boundary';

/**
 * Returns a payment method for the given context.
 *
 * @param {string} name The payment method slug to return.
 * @param {Object} paymentMethods The current registered payment methods
 * @param {boolean} isEditor Whether in the editor context (true) or not (false).
 *
 * @return {Object} The payment method matching the name for the given context.
 */
const getPaymentMethod = ( name, paymentMethods, isEditor ) => {
	let paymentMethod = paymentMethods[ name ] || null;
	if ( paymentMethod ) {
		paymentMethod = isEditor ? paymentMethod.edit : paymentMethod.content;
	}
	return paymentMethod;
};

/**
 * PaymentMethods component.
 *
 * @return {*} The rendered component.
 */
const PaymentMethods = () => {
	const { isEditor } = useEditorContext();
	const {
		customerPaymentMethods = {},
		setActivePaymentMethod,
		shouldSavePayment,
		setShouldSavePayment,
	} = usePaymentMethodDataContext();
	const { isInitialized, paymentMethods } = usePaymentMethods();
	const currentPaymentMethods = useRef( paymentMethods );
	const {
		activePaymentMethod,
		...paymentMethodInterface
	} = usePaymentMethodInterface();
	const currentPaymentMethodInterface = useRef( paymentMethodInterface );
	const [ selectedToken, setSelectedToken ] = useState( '0' );
	const { noticeContexts } = useEmitResponse();
	const { removeNotice } = useStoreNotices();
	const { customerId } = useCheckoutContext();

	// update ref on change.
	useEffect( () => {
		currentPaymentMethods.current = paymentMethods;
		currentPaymentMethodInterface.current = paymentMethodInterface;
	}, [ paymentMethods, paymentMethodInterface, activePaymentMethod ] );

	const getRenderedTab = useCallback(
		( selectedTab ) => {
			const paymentMethod = getPaymentMethod(
				selectedTab,
				currentPaymentMethods.current,
				isEditor
			);
			const { supports = {} } =
				paymentMethod &&
				currentPaymentMethods.current[ activePaymentMethod ]
					? currentPaymentMethods.current[ activePaymentMethod ]
					: {};
			return paymentMethod && activePaymentMethod ? (
				<PaymentMethodErrorBoundary isEditor={ isEditor }>
					{ cloneElement( paymentMethod, {
						activePaymentMethod,
						...currentPaymentMethodInterface.current,
					} ) }
					{ customerId > 0 && supports.savePaymentInfo && (
						<CheckboxControl
							className="wc-block-checkout__save-card-info"
							label={ __(
								'Save payment information to my account for future purchases.',
								'woocommerce'
							) }
							checked={ shouldSavePayment }
							onChange={ () =>
								setShouldSavePayment( ! shouldSavePayment )
							}
						/>
					) }
				</PaymentMethodErrorBoundary>
			) : null;
		},
		[
			isEditor,
			activePaymentMethod,
			shouldSavePayment,
			setShouldSavePayment,
			customerId,
		]
	);
	if (
		isInitialized &&
		Object.keys( currentPaymentMethods.current ).length === 0
	) {
		return <NoPaymentMethods />;
	}
	const renderedTabs = (
		<Tabs
			className="wc-block-components-checkout-payment-methods"
			onSelect={ ( tabName ) => {
				setActivePaymentMethod( tabName );
				removeNotice( 'wc-payment-error', noticeContexts.PAYMENTS );
			} }
			tabs={ Object.keys( paymentMethods ).map( ( name ) => {
				const { label, ariaLabel } = paymentMethods[ name ];
				return {
					name,
					title:
						typeof label === 'string'
							? label
							: cloneElement( label, {
									components:
										currentPaymentMethodInterface.current
											.components,
							  } ),
					ariaLabel,
					content: getRenderedTab( name ),
				};
			} ) }
			initialTabName={ activePaymentMethod }
			ariaLabel={ __(
				'Payment Methods',
				'woocommerce'
			) }
			id="wc-block-payment-methods"
		/>
	);

	const renderedSavedPaymentOptions = (
		<SavedPaymentMethodOptions onSelect={ setSelectedToken } />
	);

	const renderedTabsAndSavedPaymentOptions = (
		<>
			{ renderedSavedPaymentOptions }
			{ renderedTabs }
		</>
	);

	return Object.keys( customerPaymentMethods ).length > 0 &&
		selectedToken !== '0'
		? renderedSavedPaymentOptions
		: renderedTabsAndSavedPaymentOptions;
};

export default PaymentMethods;
