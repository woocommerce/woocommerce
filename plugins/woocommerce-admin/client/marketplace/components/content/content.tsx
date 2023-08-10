/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import './content.scss';
import Discover from '../discover/discover';
import Extensions from '../extensions/extensions';
import Footer from '../footer/footer';
import FeedbackModal from '../feedback-modal/feedback-modal';

export interface ContentProps {
	selectedTab?: string | undefined;
}

const renderContent = ( selectedTab?: string ): JSX.Element => {
	switch ( selectedTab ) {
		case 'extensions':
			return <Extensions />;
		default:
			return <Discover />;
	}
};

export default function Content( props: ContentProps ): JSX.Element {
	const { selectedTab } = props;
	return (
		<>
			<div className="woocommerce-marketplace__content">
				{ renderContent( selectedTab ) }
			</div>
			<Footer />
			<FeedbackModal />
		</>
	);
}
