/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

const localPartners = [ 'mercadopago' ];

export const RecommendedRibbon = ( { methodKey, isPill = false } ) => {
	const classes = isPill
		? 'woocommerce-task-payment__recommended-pill'
		: 'woocommerce-task-payment__recommended-ribbon';

	const text = localPartners.includes( methodKey )
		? __( 'Local Partner', 'woocommerce-admin' )
		: __( 'Recommended', 'woocommerce-admin' );

	return (
		<div className={ classes }>
			<span>{ text }</span>
		</div>
	);
};
