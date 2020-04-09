/**
 * Internal dependencies
 */
import { assertConfigHasProperties, assertValidElement } from './assertions';

export default class PaymentMethodConfig {
	constructor( config ) {
		// validate config
		PaymentMethodConfig.assertValidConfig( config );
		this.name = config.name;
		this.label = config.label;
		this.ariaLabel = config.ariaLabel;
		this.content = config.content;
		this.edit = config.edit;
		this.canMakePayment = config.canMakePayment;
		this.paymentMethodId = config.paymentMethodId || this.name;
	}

	static assertValidConfig = ( config ) => {
		assertConfigHasProperties( config, [
			'name',
			'label',
			'ariaLabel',
			'content',
			'edit',
			'canMakePayment',
		] );
		if ( typeof config.name !== 'string' ) {
			throw new Error(
				'The name property for the payment method must be a string'
			);
		}
		if (
			typeof config.paymentMethodId !== 'string' &&
			typeof config.paymentMethodId !== 'undefined'
		) {
			throw new Error(
				'The paymentMethodId property for the payment method must be a string or undefined (in which case it will be the value of the name property).'
			);
		}
		assertValidElement( config.label, 'label' );
		assertValidElement( config.content, 'content' );
		assertValidElement( config.edit, 'edit' );
		if ( typeof config.ariaLabel !== 'string' ) {
			throw new TypeError(
				'The ariaLabel property for the payment method must be a string'
			);
		}
		if ( typeof config.canMakePayment !== 'function' ) {
			throw new TypeError(
				'The canMakePayment property for the payment method must be a function.'
			);
		}
	};
}
