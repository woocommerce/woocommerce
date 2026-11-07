/**
 * External dependencies
 */
import {
	usePaymentMethodInterface,
	useStoreEvents,
} from '@woocommerce/base-context/hooks';
import { cloneElement, useCallback } from '@wordpress/element';
import { useEditorContext } from '@woocommerce/base-context';
import { RadioControlAccordion } from '@woocommerce/blocks-components';
import { useDispatch, useSelect } from '@wordpress/data';
import { getPaymentMethods } from '@woocommerce/blocks-registry';
import { paymentStore } from '@woocommerce/block-data';
import { CHECKOUT_SECTIONS } from '@woocommerce/base-utils/layout-transition';
import { usePendingLayoutChange } from '@woocommerce/base-utils/use-pending-layout-change';

/**
 * Internal dependencies
 */
import PaymentMethodCard from './payment-method-card';
import { noticeContexts } from '../../../base/context/event-emit';

/**
 * Component used to render all non-saved payment method options.
 *
 * @return {*} The rendered component.
 */
const PaymentMethodOptions = () => {
	const {
		activeSavedToken,
		activePaymentMethod,
		isExpressPaymentMethodActive,
		availablePaymentMethods,
	} = useSelect( ( select ) => {
		const store = select( paymentStore );
		return {
			activeSavedToken: store.getActiveSavedToken(),
			activePaymentMethod: store.getActivePaymentMethod(),
			isExpressPaymentMethodActive: store.isExpressPaymentMethodActive(),
			availablePaymentMethods: store.getAvailablePaymentMethods(),
		};
	} );
	const { __internalSetActivePaymentMethod } = useDispatch( paymentStore );
	const paymentMethods = getPaymentMethods();
	const { ...paymentMethodInterface } = usePaymentMethodInterface();
	const { removeNotice } = useDispatch( 'core/notices' );
	const { dispatchCheckoutEvent } = useStoreEvents();
	const { isEditor } = useEditorContext();
	const [ pendingMethod, withLayoutChange ] = usePendingLayoutChange(
		`${ CHECKOUT_SECTIONS }, .wc-block-checkout__payment-method .wc-block-components-radio-control-accordion-option`
	);

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
						__internalSetActivePaymentMethod,
						...paymentMethodInterface,
					} ) }
				</PaymentMethodCard>
			),
		};
	} );

	const onChange = useCallback(
		( value ) => {
			__internalSetActivePaymentMethod( value );
			removeNotice( 'wc-payment-error', noticeContexts.PAYMENTS );
			dispatchCheckoutEvent( 'set-active-payment-method', {
				paymentMethodSlug: value,
			} );
		},
		[
			dispatchCheckoutEvent,
			removeNotice,
			__internalSetActivePaymentMethod,
		]
	);

	const selected = activeSavedToken ? null : activePaymentMethod;

	return isExpressPaymentMethodActive ? null : (
		<RadioControlAccordion
			highlightChecked={ true }
			id={ 'wc-payment-method-options' }
			selected={ pendingMethod ?? selected }
			openValue={ selected }
			onChange={ withLayoutChange( onChange ) }
			options={ options }
		/>
	);
};

export default PaymentMethodOptions;
