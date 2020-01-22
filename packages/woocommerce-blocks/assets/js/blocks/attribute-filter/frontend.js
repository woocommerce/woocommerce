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
			attributeId: parseInt( el.dataset.attributeId || 0, 10 ),
			showCounts: el.dataset.showCounts === 'true',
			queryType: el.dataset.queryType,
			heading: el.dataset.heading,
			headingLevel: el.dataset.headingLevel || 3,
		},
	};
};

renderFrontend(
	'.wp-block-woocommerce-attribute-filter',
	withRestApiHydration( Block ),
	getProps
);
