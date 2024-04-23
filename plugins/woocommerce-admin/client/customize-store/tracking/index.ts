/**
 * External dependencies
 */
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import {
	ENTREPRENEUR_FLOW_QUERY_PARAM_VALUE,
	isEntrepreneurFlow,
} from '~/customize-store/design-with-ai/entrepreneur-flow';
import { isWooExpress } from '~/utils/is-woo-express';

export const trackEvent = (
	eventName: string,
	properties?: Record< string, unknown >
) => {
	if ( isWooExpress() && isEntrepreneurFlow() ) {
		recordEvent( eventName, {
			...properties,
			ref: ENTREPRENEUR_FLOW_QUERY_PARAM_VALUE,
		} );
		return;
	}

	if ( properties ) {
		recordEvent( eventName, properties );
	} else {
		recordEvent( eventName );
	}
};
