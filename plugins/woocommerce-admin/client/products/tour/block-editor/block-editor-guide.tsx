/**
 * External dependencies
 */

import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Guide from '../components/guide';
import './style.scss';

const PageContent = ( {
	page,
}: {
	page: {
		heading: string;
		text: string;
	};
} ) => (
	<>
		<h1 className="woocommerce-block-editor-guide__heading">
			{ page.heading }
		</h1>
		<p className="woocommerce-block-editor-guide__text">{ page.text }</p>
	</>
);

const PageImage = ( {
	page,
}: {
	page: {
		index: number;
	};
} ) => (
	<div
		className={ `woocommerce-block-editor-guide__header woocommerce-block-editor-guide__header-${
			page.index + 1
		}` }
	></div>
);

interface BlockEditorGuideProps {
	onCloseGuide: ( currentPage: number, origin: 'close' | 'finish' ) => void;
}

const BlockEditorGuide = ( { onCloseGuide }: BlockEditorGuideProps ) => {
	const pagesConfig = [
		{
			heading: __( 'Refreshed, streamlined interface', 'woocommerce' ),
			text: __(
				'Experience a simpler, more focused interface with a modern design that enhances usability.',
				'woocommerce'
			),
		},
		{
			heading: __( 'Content-rich product descriptions', 'woocommerce' ),
			text: __(
				'Create compelling product pages with blocks, media, images, videos, and any content you desire to engage customers.',
				'woocommerce'
			),
		},
		{
			heading: __( 'Improved speed & performance', 'woocommerce' ),
			text: __(
				'Enjoy a seamless experience without page reloads. Our modern technology ensures reliability and lightning-fast performance.',
				'woocommerce'
			),
		},
		{
			heading: __( 'More features are on the way', 'woocommerce' ),
			text: __(
				'While we currently support physical products, exciting updates are coming to accommodate more types, like digital products, variations, and more. Stay tuned!',
				'woocommerce'
			),
		},
	];

	const pages = pagesConfig.map( ( page, index ) => ( {
		content: <PageContent page={ page } />,
		image: <PageImage page={ { ...page, index } } />,
	} ) );

	return (
		<Guide
			className="woocommerce-block-editor-guide"
			contentLabel=""
			finishButtonText={ __( 'Tell me more', 'woocommerce' ) }
			finishButtonLink="https://woocommerce.com/product-form-beta"
			onFinish={ onCloseGuide }
			pages={ pages }
		/>
	);
};

export default BlockEditorGuide;
