/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

export const recordTracksFactory = (
	feature: string,
	propertiesCallback: () => Record< string, string | number > = () => ( {} )
) => {
	return ( name: string, properties?: Record< string, string | number > ) =>
		recordEvent( `woo_ai_product_${ feature }_${ name }`, {
			...propertiesCallback(),
			...properties,
		} );
};
