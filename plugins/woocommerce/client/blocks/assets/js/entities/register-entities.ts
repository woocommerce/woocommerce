/**
 * External dependencies
 */
import deprecated from '@wordpress/deprecated';

/**
 * Internal dependencies
 */
import {
	registerProductEntity as registerProductEntityInternal,
	registerSettingsEntity as registerSettingsEntityInternal,
} from '../entity-registration/register-entities';

const deprecationNoticesShown = new Set< string >();

const showDeprecationNotice = ( functionName: string ) => {
	if ( deprecationNoticesShown.has( functionName ) ) {
		return;
	}

	deprecated( `${ functionName }()`, {
		since: '11.1.0',
		alternative: 'automatic entity registration',
		plugin: 'WooCommerce',
		hint: 'Entities are registered automatically by the wc-entities script. Remove this call.',
	} );
	deprecationNoticesShown.add( functionName );
};

/**
 * @deprecated Since WooCommerce 11.1.0. Entities are registered automatically
 * by the wc-entities script.
 */
export const registerProductEntity = () => {
	showDeprecationNotice( 'registerProductEntity' );
	return registerProductEntityInternal();
};

/**
 * @deprecated Since WooCommerce 11.1.0. Entities are registered automatically
 * by the wc-entities script.
 */
export const registerSettingsEntity = () => {
	showDeprecationNotice( 'registerSettingsEntity' );
	return registerSettingsEntityInternal();
};
