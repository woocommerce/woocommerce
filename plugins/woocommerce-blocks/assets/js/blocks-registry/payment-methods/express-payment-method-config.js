/**
 * Internal dependencies
 */
import { assertConfigHasProperties, assertValidElement } from './assertions';

export default class ExpressPaymentMethodConfig {
	constructor( config ) {
		// validate config
		ExpressPaymentMethodConfig.assertValidConfig( config );
		this.id = config.id;
		this.activeContent = config.activeContent;
		this.edit = config.edit;
		this.canMakePayment = config.canMakePayment;
	}

	static assertValidConfig = ( config ) => {
		assertConfigHasProperties( config, [ 'id', 'activeContent', 'edit' ] );
		if ( typeof config.id !== 'string' ) {
			throw new TypeError(
				'The id for the express payment method must be a string'
			);
		}
		assertValidElement( config.activeContent, 'activeContent' );
		assertValidElement( config.edit, 'edit' );
		if ( ! ( config.canMakePayment instanceof Promise ) ) {
			throw new TypeError(
				'The canMakePayment property for the express payment method must be a promise.'
			);
		}
	};
}
