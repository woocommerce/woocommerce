/**
 * Internal dependencies
 */
import './marketplace.scss';
import { MarketplaceContextProvider } from './contexts/marketplace-context';
import Header from './components/header/header';
import Content from './components/content/content';
import Footer from './components/footer/footer';
import FeedbackModal from './components/feedback-modal/feedback-modal';

export default function Marketplace() {
	return (
		<MarketplaceContextProvider>
			<div className="woocommerce-marketplace">
				<Header />
				<Content />
				<FeedbackModal />
				<Footer />
			</div>
		</MarketplaceContextProvider>
	);
}
