/**
 * External dependencies
 */
import { withRestApiHydration } from '@woocommerce/block-hocs';

/**
 * Internal dependencies
 */
import Block from './block.js';
import renderFrontend from '../../utils/render-frontend.js';

const getProps = ( el ) => {
	return {
		attributes: {
			displayStyle: el.dataset.displayStyle,
			heading: el.dataset.heading,
			headingLevel: el.dataset.headingLevel || 3,
		},
	};
};

renderFrontend(
	'.wp-block-woocommerce-active-filters',
	withRestApiHydration( Block ),
	getProps
);
