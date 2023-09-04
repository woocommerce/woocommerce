/**
 * External dependencies
 */
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './marketplace.scss';
import { DEFAULT_TAB_KEY } from './components/constants';
import Header from './components/header/header';
import Content from './components/content/content';
import { ProductListContextProvider } from './contexts/product-list-context';

export default function Marketplace() {
	const [ selectedTab, setSelectedTab ] = useState( DEFAULT_TAB_KEY );

	return (
		<ProductListContextProvider>
			<div className="woocommerce-marketplace">
				<Header
					selectedTab={ selectedTab }
					setSelectedTab={ setSelectedTab }
				/>
				<Content selectedTab={ selectedTab } />
			</div>
		</ProductListContextProvider>
	);
}
