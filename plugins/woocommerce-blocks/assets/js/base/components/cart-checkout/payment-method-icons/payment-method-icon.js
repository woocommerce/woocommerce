/**
 * Get a class name for an icon.
 *
 * @param {string} id Icon ID.
 */
const getIconClassName = ( id ) => {
	return `wc-block-components-payment-method-icon wc-block-components-payment-method-icon--${ id }`;
};

/**
 * Return an element for an icon.
 */
const PaymentMethodIcon = ( { id, src = null, alt = '' } ) => {
	if ( ! src ) {
		return null;
	}
	return <img className={ getIconClassName( id ) } src={ src } alt={ alt } />;
};

export default PaymentMethodIcon;
