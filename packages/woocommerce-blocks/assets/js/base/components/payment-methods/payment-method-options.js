/**
 * External dependencies
 */
import {
	usePaymentMethods,
	usePaymentMethodInterface,
	useStoreNotices,
	useEmitResponse,
} from '@woocommerce/base-hooks';
import { cloneElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	useEditorContext,
	usePaymentMethodDataContext,
} from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import Tabs from '../tabs';
import PaymentMethodTab from './payment-method-tab';

/**
 * Component used to render all non-saved payment method options.
 *
 * @return {*} The rendered component.
 */
const PaymentMethodOptions = () => {
	const { setActivePaymentMethod } = usePaymentMethodDataContext();
	const { paymentMethods } = usePaymentMethods();
	const {
		activePaymentMethod,
		...paymentMethodInterface
	} = usePaymentMethodInterface();
	const { noticeContexts } = useEmitResponse();
	const { removeNotice } = useStoreNotices();
	const { isEditor } = useEditorContext();

	return (
		<Tabs
			className="wc-block-components-checkout-payment-methods"
			onSelect={ ( tabName ) => {
				setActivePaymentMethod( tabName );
				removeNotice( 'wc-payment-error', noticeContexts.PAYMENTS );
			} }
			tabs={ Object.keys( paymentMethods ).map( ( name ) => {
				const {
					ariaLabel,
					edit,
					content,
					label,
					supports,
				} = paymentMethods[ name ];
				const component = isEditor ? edit : content;
				return {
					name,
					title:
						typeof label === 'string'
							? label
							: cloneElement( label, {
									components:
										paymentMethodInterface.components,
							  } ),
					ariaLabel,
					content: (
						<PaymentMethodTab
							allowsSaving={ supports.savePaymentInfo }
						>
							{ cloneElement( component, {
								activePaymentMethod,
								...paymentMethodInterface,
							} ) }
						</PaymentMethodTab>
					),
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
};

export default PaymentMethodOptions;
