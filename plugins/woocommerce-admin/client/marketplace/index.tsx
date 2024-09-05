/**
 * External dependencies
 */
import { useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './marketplace.scss';
import {
	MarketplaceContextProvider,
	MarketplaceContext,
} from './contexts/marketplace-context';
import Header from './components/header/header';
import Content from './components/content/content';
import Footer from './components/footer/footer';

function MarketplaceComponents() {
	const { selectedTab } = useContext( MarketplaceContext );

	const classNames =
		'woocommerce-marketplace' +
		( selectedTab ? ' woocommerce-marketplace--' + selectedTab : '' );

	return (
		<div className={ classNames }>
			<Header />
			<Content />
			<Footer />
		</div>
	);
}

export default function Marketplace() {
	return (
		<MarketplaceContextProvider>
			<MarketplaceComponents />
		</MarketplaceContextProvider>
	);
}
