/**
 * External dependencies
 */
import { withRestApiHydration } from '@woocommerce/block-hocs';
import { StoreNoticesProvider } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import Block from './block';
import renderFrontend from '../../../utils/render-frontend.js';

/**
 * Wrapper component to supply the notice provider.
 *
 * @param {*} props
 */
const AllProductsFrontend = ( props ) => {
	return (
		<StoreNoticesProvider context="wc/all-products">
			<Block { ...props } />
		</StoreNoticesProvider>
	);
};

const getProps = ( el ) => ( {
	attributes: JSON.parse( el.dataset.attributes ),
} );

renderFrontend(
	'.wp-block-woocommerce-all-products',
	withRestApiHydration( AllProductsFrontend ),
	getProps
);
