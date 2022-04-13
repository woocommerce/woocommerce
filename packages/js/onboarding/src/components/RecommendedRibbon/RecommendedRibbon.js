/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createElement } from '@wordpress/element';

export const RecommendedRibbon = ( { isLocalPartner = false } ) => {
	const text = isLocalPartner
		? __( 'Local Partner', 'woocommerce' )
		: __( 'Recommended', 'woocommerce' );

	return (
		<div className={ 'woocommerce-task-payment__recommended-ribbon' }>
			<span>{ text }</span>
		</div>
	);
};
