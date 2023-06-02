/**
 * External dependencies
 */

import { TourKit } from '@woocommerce/components';
import { Guide } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useBlockEditorTourOptions } from './use-block-editor-tour-options';

/*
 * Internal dependencies
 */

import './style.scss';

interface Props {
	isTourOpen: boolean;
	dismissModal: () => void;
	openGuide: () => void;
	isGuideOpen: boolean;
}

const BlockEditorTour = ( {
	isTourOpen,
	dismissModal,
	openGuide,
	isGuideOpen,
}: Props ) => {
	if ( isGuideOpen ) {
		return (
			<Guide
				className="woocommerce-block-editor-guide"
				finishButtonText={ __( 'Close', 'woocommerce' ) }
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
	} else if ( isTourOpen ) {
		return (
			<TourKit
				config={ {
					steps: [
						{
							meta: {
								name: 'woocommerce-block-editor-tour',
								primaryButton: {
									text: __(
										'View highlights',
										'woocommerce'
									),
								},
								descriptions: {
									desktop: __(
										"We designed a brand new product editing experience to let you focus on what's important.",
										'woocommerce'
									),
								},
								heading: __(
									'Meet a streamlined product form',
									'woocommerce'
								),
							},
							referenceElements: {
								desktop: '#adminmenuback',
							},
						},
					],
					closeHandler: ( _, __, source ) => {
						dismissModal();
						if ( source === 'done-btn' ) {
							openGuide();
						}
					},
					options: {
						effects: {
							arrowIndicator: false,
							overlay: false,
							liveResize: {
								rootElementSelector: '#adminmenuback',
								resize: true,
							},
						},
						portalParentElement: document.getElementById(
							'wpbody'
						),
						popperModifiers: [
							{
								name: 'bottom-left',
								enabled: true,
								phase: 'beforeWrite',
								requires: [ 'computeStyles' ],
								fn: ( { state } ) => {
									state.styles.popper.top = 'auto';
									state.styles.popper.left = 'auto';
									state.styles.popper.bottom = '10px';
									state.styles.popper.transform =
										'translate3d(10px, 0px, 0px)';
								},
							},
						],
					},
				} }
			/>
		);
	}
	return null;
};

export default BlockEditorTour;
