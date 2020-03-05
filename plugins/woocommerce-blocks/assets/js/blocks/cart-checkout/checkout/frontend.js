/**
 * External dependencies
 */
import { withRestApiHydration } from '@woocommerce/block-hocs';

/**
 * Internal dependencies
 */
import Block from './block.js';
import blockAttributes from './attributes';
import renderFrontend from '../../../utils/render-frontend.js';

const getProps = ( el ) => {
	const attributes = {};

	Object.keys( blockAttributes ).forEach( ( key ) => {
		if ( typeof el.dataset[ key ] !== 'undefined' ) {
			if (
				el.dataset[ key ] === 'true' ||
				el.dataset[ key ] === 'false'
			) {
				attributes[ key ] = el.dataset[ key ] !== 'false';
			} else {
				attributes[ key ] = el.dataset[ key ];
			}
		}
	} );

	return {
		attributes,
	};
};

renderFrontend(
	'.wp-block-woocommerce-checkout',
	withRestApiHydration( Block ),
	getProps
);
