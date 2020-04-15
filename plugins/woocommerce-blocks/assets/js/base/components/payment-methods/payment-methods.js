/**
 * External dependencies
 */
import {
	usePaymentMethods,
	usePaymentMethodInterface,
} from '@woocommerce/base-hooks';
import {
	useCallback,
	cloneElement,
	useRef,
	useEffect,
	useState,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	useEditorContext,
	usePaymentMethodDataContext,
} from '@woocommerce/base-context';

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
	} = usePaymentMethodDataContext();
	const { isInitialized, paymentMethods } = usePaymentMethods();
	const currentPaymentMethods = useRef( paymentMethods );
	const {
		activePaymentMethod,
		...paymentMethodInterface
	} = usePaymentMethodInterface();
	const currentPaymentMethodInterface = useRef( paymentMethodInterface );
	const [ selectedToken, setSelectedToken ] = useState( 0 );

	// update ref on change.
	useEffect( () => {
		currentPaymentMethods.current = paymentMethods;
		currentPaymentMethodInterface.current = paymentMethodInterface;
	}, [ paymentMethods, paymentMethodInterface, activePaymentMethod ] );

	const getRenderedTab = useCallback(
		() => ( selectedTab ) => {
			const paymentMethod = getPaymentMethod(
				selectedTab,
				currentPaymentMethods.current,
				isEditor
			);
			return paymentMethod ? (
				<PaymentMethodErrorBoundary isEditor={ isEditor }>
					{ cloneElement( paymentMethod, {
						activePaymentMethod,
						...currentPaymentMethodInterface.current,
					} ) }
				</PaymentMethodErrorBoundary>
			) : null;
		},
		[ isEditor, activePaymentMethod ]
	);

	if (
		! isInitialized ||
		Object.keys( currentPaymentMethods.current ).length === 0
	) {
		return <NoPaymentMethods />;
	}
	const renderedTabs = (
		<Tabs
			className="wc-block-components-checkout-payment-methods"
			onSelect={ ( tabName ) => setActivePaymentMethod( tabName ) }
			tabs={ Object.keys( currentPaymentMethods.current ).map(
				( name ) => {
					const { label, ariaLabel } = currentPaymentMethods.current[
						name
					];
					return {
						name,
						title: () => label,
						ariaLabel,
					};
				}
			) }
			initialTabName={ activePaymentMethod }
			ariaLabel={ __(
				'Payment Methods',
				'woo-gutenberg-products-block'
			) }
			id="wc-block-payment-methods"
		>
			{ getRenderedTab() }
		</Tabs>
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
		selectedToken !== 0
		? renderedSavedPaymentOptions
		: renderedTabsAndSavedPaymentOptions;
};

export default PaymentMethods;
