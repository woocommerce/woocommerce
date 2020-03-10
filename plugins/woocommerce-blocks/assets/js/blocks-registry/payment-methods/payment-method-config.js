/**
 * Internal dependencies
 */
import { assertConfigHasProperties, assertValidElement } from './assertions';

export default class PaymentMethodConfig {
	constructor( config ) {
		// validate config
		PaymentMethodConfig.assertValidConfig( config );
		this.id = config.id;
		this.label = config.label;
		this.ariaLabel = config.ariaLabel;
		this.activeContent = config.activeContent;
		this.edit = config.edit;
		this.canMakePayment = config.canMakePayment;
	}

	static assertValidConfig = ( config ) => {
		assertConfigHasProperties( config, [
			'id',
			'label',
			'stepContent',
			'ariaLabel',
			'activeContent',
			'edit',
			'canMakePayment',
		] );
		if ( typeof config.id !== 'string' ) {
			throw new Error( 'The id for the payment method must be a string' );
		}
		assertValidElement( config.label, 'label' );
		assertValidElement( config.stepContent, 'stepContent' );
		assertValidElement( config.activeContent, 'activeContent' );
		assertValidElement( config.edit, 'edit' );
		if ( typeof config.ariaLabel !== 'string' ) {
			throw new TypeError(
				'The ariaLabel for the payment method must be a string'
			);
		}
		if ( ! ( config.canMakePayment instanceof Promise ) ) {
			throw new TypeError(
				'The canMakePayment property for the payment method must be a promise.'
			);
		}
	};
}
