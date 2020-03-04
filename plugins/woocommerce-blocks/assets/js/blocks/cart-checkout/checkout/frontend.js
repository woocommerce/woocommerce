/**
 * External dependencies
 */
import { withRestApiHydration } from '@woocommerce/block-hocs';

/**
 * Internal dependencies
 */
import Block from './block.js';
import renderFrontend from '../../../utils/render-frontend.js';

const getProps = ( el ) => {
	return {
		attributes: {
			useShippingAsBilling: el.dataset.useShippingAsBilling !== 'false',
		},
	};
};

renderFrontend(
	'.wp-block-woocommerce-checkout',
	withRestApiHydration( Block ),
	getProps
);
