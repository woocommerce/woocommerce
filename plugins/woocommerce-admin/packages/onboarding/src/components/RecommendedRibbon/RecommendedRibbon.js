/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

const localPartners = [ 'mercadopago' ];

export const RecommendedRibbon = ( { methodKey } ) => {
	const text = localPartners.includes( methodKey )
		? __( 'Local Partner', 'woocommerce-admin' )
		: __( 'Recommended', 'woocommerce-admin' );

	return (
		<div className={ 'woocommerce-task-payment__recommended-ribbon' }>
			<span>{ text }</span>
		</div>
	);
};
