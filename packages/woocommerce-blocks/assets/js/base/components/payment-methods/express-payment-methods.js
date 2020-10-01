/**
 * External dependencies
 */
import {
	useExpressPaymentMethods,
	usePaymentMethodInterface,
} from '@woocommerce/base-hooks';
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

const ExpressPaymentMethods = () => {
	const { isEditor } = useEditorContext();
	const {
		setActivePaymentMethod,
		activePaymentMethod,
		setPaymentStatus,
	} = usePaymentMethodDataContext();
	const paymentMethodInterface = usePaymentMethodInterface();
	const { paymentMethods } = useExpressPaymentMethods();
	const previousActivePaymentMethod = useRef( activePaymentMethod );

	const onExpressPaymentClick = useCallback(
		( paymentMethodId ) => () => {
			previousActivePaymentMethod.current = activePaymentMethod;
			setPaymentStatus().started();
			setActivePaymentMethod( paymentMethodId );
		},
		[ setActivePaymentMethod, setPaymentStatus, activePaymentMethod ]
	);
	const onExpressPaymentClose = useCallback( () => {
		setActivePaymentMethod( previousActivePaymentMethod.current );
	}, [ setActivePaymentMethod ] );
	const paymentMethodIds = Object.keys( paymentMethods );
	const content =
		paymentMethodIds.length > 0 ? (
			paymentMethodIds.map( ( id ) => {
				const expressPaymentMethod = isEditor
					? paymentMethods[ id ].edit
					: paymentMethods[ id ].content;
				return isValidElement( expressPaymentMethod ) ? (
					<li key={ id } id={ `express-payment-method-${ id }` }>
						{ cloneElement( expressPaymentMethod, {
							...paymentMethodInterface,
							onClick: onExpressPaymentClick( id ),
							onClose: onExpressPaymentClose,
						} ) }
					</li>
				) : null;
			} )
		) : (
			<li key="noneRegistered">No registered Payment Methods</li>
		);
	return (
		<ul className="wc-block-components-express-payment__event-buttons">
			{ content }
		</ul>
	);
};

export default ExpressPaymentMethods;
