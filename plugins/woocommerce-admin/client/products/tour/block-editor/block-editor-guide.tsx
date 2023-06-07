/**
 * External dependencies
 */

import { Guide } from '@woocommerce/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import './style.scss';

interface Props {
	onCloseGuide: ( source: 'finish-btn' | 'close-btn' ) => void;
}

const BlockEditorGuide = ( { onCloseGuide }: Props ) => {
	return (
		<Guide
			className="woocommerce-block-editor-guide"
			contentLabel=""
			finishButtonText={ __( 'Tell me more', 'woocommerce' ) }
			onFinish={ ( event ) => {
				if (
					event?.currentTarget?.classList?.contains(
						'components-guide__finish-button'
					)
				) {
					onCloseGuide( 'finish-btn' );
				} else {
					onCloseGuide( 'close-btn' );
				}
			} }
			pages={ [
				{
					content: (
						<>
							<h1 className="woocommerce-block-editor-guide__heading">
								{ __(
									'Refreshed, streamlined interface',
									'woocommerce'
								) }
							</h1>
							<p className="woocommerce-block-editor-guide__text">
								{ __(
									'Experience a simpler, more focused interface with a modern design that enhances usability.',
									'woocommerce'
								) }
							</p>
						</>
					),
					image: (
						<div className="woocommerce-block-editor-guide__background1"></div>
					),
				},
				{
					content: (
						<>
							<h1 className="woocommerce-block-editor-guide__heading">
								{ __(
									'Content-rich product descriptions',
									'woocommerce'
								) }
							</h1>
							<p className="woocommerce-block-editor-guide__text">
								{ __(
									'Create compelling product pages with blocks, media, images, videos, and any content you desire to engage customers.',
									'woocommerce'
								) }
							</p>
						</>
					),
					image: (
						<div className="woocommerce-block-editor-guide__background2"></div>
					),
				},
				{
					content: (
						<>
							<h1 className="woocommerce-block-editor-guide__heading">
								{ __(
									'Improved speed & performance',
									'woocommerce'
								) }
							</h1>
							<p className="woocommerce-block-editor-guide__text">
								{ __(
									'Enjoy a seamless experience without page reloads. Our modern technology ensures reliability and lightning-fast performance.',
									'woocommerce'
								) }
							</p>
						</>
					),
					image: (
						<div className="woocommerce-block-editor-guide__background3"></div>
					),
				},
				{
					content: (
						<>
							<h1 className="woocommerce-block-editor-guide__heading">
								{ __(
									'More features are on the way',
									'woocommerce'
								) }
							</h1>
							<p className="woocommerce-block-editor-guide__text">
								{ __(
									'While we currently support physical products, exciting updates are coming to accommodate more types, like digital products, variations, and more. Stay tuned!',
									'woocommerce'
								) }
							</p>
						</>
					),
					image: (
						<div className="woocommerce-block-editor-guide__background4"></div>
					),
				},
			] }
		/>
	);
};

export default BlockEditorGuide;
