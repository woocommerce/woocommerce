/**
 * External dependencies
 */
import {
	usePaymentMethodInterface,
	useStoreEvents,
} from '@woocommerce/base-context/hooks';
import { cloneElement, useCallback } from '@wordpress/element';
import {
	useEditorContext,
	usePaymentMethodDataContext,
} from '@woocommerce/base-context';
import classNames from 'classnames';
import RadioControlAccordion from '@woocommerce/base-components/radio-control-accordion';
import { useDispatch, useSelect } from '@wordpress/data';
import { getPaymentMethods } from '@woocommerce/blocks-registry';

/**
 * Internal dependencies
 */
import PaymentMethodCard from './payment-method-card';
import { noticeContexts } from '../../../base/context/event-emit';
import { STORE_KEY as PAYMENT_METHOD_DATA_STORE_KEY } from '../../../data/payment-methods/constants';

/**
 * Component used to render all non-saved payment method options.
 *
 * @return {*} The rendered component.
 */
const PaymentMethodOptions = () => {
	const { savedPaymentMethods } = usePaymentMethodDataContext(); //TODO: Move this state from the context file

	const {
		activeSavedToken,
		activePaymentMethod,
		isExpressPaymentMethodActive,
	} = useSelect( ( select ) => {
		const store = select( PAYMENT_METHOD_DATA_STORE_KEY );
		return {
			activeSavedToken: store.getActiveSavedToken(),
			activePaymentMethod: store.getActivePaymentMethod(),
			isExpressPaymentMethodActive: store.isExpressPaymentMethodActive(),
		};
	} );
	const { setActivePaymentMethod } = useDispatch(
		PAYMENT_METHOD_DATA_STORE_KEY
	);
	const paymentMethods = getPaymentMethods();
	const { availablePaymentMethods } = useSelect( ( select ) => {
		const store = select( PAYMENT_METHOD_DATA_STORE_KEY );
		return {
			availablePaymentMethods: store.getAvailablePaymentMethods(),
		};
	} );
	const { ...paymentMethodInterface } = usePaymentMethodInterface();
	const { removeNotice } = useDispatch( 'core/notices' );
	const { dispatchCheckoutEvent } = useStoreEvents();
	const { isEditor } = useEditorContext();

	const options = Object.keys( availablePaymentMethods ).map( ( name ) => {
		const { edit, content, label, supports } = paymentMethods[ name ];
		const component = isEditor ? edit : content;
		return {
			value: name,
			label:
				typeof label === 'string'
					? label
					: cloneElement( label, {
							components: paymentMethodInterface.components,
					  } ),
			name: `wc-saved-payment-method-token-${ name }`,
			content: (
				<PaymentMethodCard showSaveOption={ supports.showSaveOption }>
					{ cloneElement( component, {
						activePaymentMethod,
						...paymentMethodInterface,
					} ) }
				</PaymentMethodCard>
			),
		};
	} );

	const onChange = useCallback(
		( value ) => {
			setActivePaymentMethod( value );
			removeNotice( 'wc-payment-error', noticeContexts.PAYMENTS );
			dispatchCheckoutEvent( 'set-active-payment-method', {
				value,
			} );
		},
		[ dispatchCheckoutEvent, removeNotice, setActivePaymentMethod ]
	);

	const isSinglePaymentMethod =
		Object.keys( savedPaymentMethods ).length === 0 &&
		Object.keys( paymentMethods ).length === 1;

	const singleOptionClass = classNames( {
		'disable-radio-control': isSinglePaymentMethod,
	} );
	return isExpressPaymentMethodActive ? null : (
		<RadioControlAccordion
			id={ 'wc-payment-method-options' }
			className={ singleOptionClass }
			selected={ activeSavedToken ? null : activePaymentMethod }
			onChange={ onChange }
			options={ options }
		/>
	);
};

export default PaymentMethodOptions;
