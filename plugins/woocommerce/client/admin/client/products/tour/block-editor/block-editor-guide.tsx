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
	isNewUser?: boolean;
	onCloseGuide: ( currentPage: number, origin: 'close' | 'finish' ) => void;
}

const BlockEditorGuide = ( { onCloseGuide }: BlockEditorGuideProps ) => {
	const pagesConfig = [
		{
			heading: __( 'Fresh and modern interface', 'woocommerce' ),
			text: __(
				'Everything you need to create and sell your products, all in one place. From photos and descriptions to pricing and inventory, all of your product settings can be found here.',
				'woocommerce'
			),
		},
		{
			heading: __( 'Content-rich product descriptions', 'woocommerce' ),
			text: __(
				"Show off what's great about your products and engage your customers with content-rich product descriptions. Add images, videos, and any other content they might need to make a purchase.",
				'woocommerce'
			),
		},
		{
			heading: __( 'Lightning fast performance ', 'woocommerce' ),
			text: __(
				'Get your products listed and available for purchase in no time! Our modern technology ensures a reliable and streamlined experience.',
				'woocommerce'
			),
		},
		{
			heading: __( 'More features are on the way', 'woocommerce' ),
			text: __(
				"We're actively working on adding more features to the product form, including the ability to add digital products, variations, and more. Watch this space!",
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
