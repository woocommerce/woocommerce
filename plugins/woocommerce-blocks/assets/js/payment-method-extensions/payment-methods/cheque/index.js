/**
 * External dependencies
 */
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { __ } from '@wordpress/i18n';
import { getSetting } from '@woocommerce/settings';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import { PAYMENT_METHOD_NAME } from './constants';
import { defaultPromise } from '../../utils';

const settings = getSetting( 'cheque_data', {} );

const EditPlaceHolder = () => <div>TODO: Edit preview soon...</div>;

const Content = ( props ) => {
	const { activePaymentMethod } = props;

	return activePaymentMethod === PAYMENT_METHOD_NAME ? (
		<div>{ decodeEntities( settings.description || '' ) }</div>
	) : null;
};

const offlineChequePaymentMethod = {
	id: PAYMENT_METHOD_NAME,
	label: (
		<strong>
			{ decodeEntities(
				settings.title ||
					__( 'Check Payment', 'woo-gutenberg-products-block' )
			) }
		</strong>
	),
	content: <Content />,
	edit: <EditPlaceHolder />,
	canMakePayment: defaultPromise,
	ariaLabel: decodeEntities(
		settings.title || __( 'Check Payment', 'woo-gutenberg-products-block' )
	),
};

registerPaymentMethod( ( Config ) => new Config( offlineChequePaymentMethod ) );
