/**
 * External dependencies
 */

import { TourKit } from '@woocommerce/components';
import { Guide } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useBlockEditorTour } from './use-block-editor-tour';

/*
 * Internal dependencies
 */

import './style.scss';

const BlockEditorTour = () => {
	const {
		isTourOpen,
		dismissModal,
		openGuide,
		isGuideOpen,
	} = useBlockEditorTour();

	if ( isGuideOpen ) {
		return (
			<Guide
				className='woocommerce-block-editor-guide'
				pages={ [
					{
						content: (
							<>
								<h1>
									{ __(
										'Refreshed, streamlined interface',
										'woocommerce'
									) }
								</h1>
								<p>
									{ __(
										'Experience a simpler, more focused interface with a modern design that enhances usability.',
										'woocommerce'
									) }
								</p>
							</>
						),
					},
					{
						content: (
							<>
								<h1>
									{ __(
										'Content-rich product descriptions',
										'woocommerce'
									) }
								</h1>
								<p>
									{ __(
										'Create compelling product pages with blocks, media, images, videos, and any content you desire to engage customers.',
										'woocommerce'
									) }
								</p>
							</>
						),
					},
					{
						content: (
							<>
								<h1>
									{ __(
										'Improved speed & performance',
										'woocommerce'
									) }
								</h1>
								<p>
									{ __(
										'Enjoy a seamless experience without page reloads. Our modern technology ensures reliability and lightning-fast performance.',
										'woocommerce'
									) }
								</p>
							</>
						),
					},
					{
						content: (
							<>
								<h1>
									{ __(
										'More features are on the way',
										'woocommerce'
									) }
								</h1>
								<p>
									{ __(
										'While we currently support physical products, exciting updates are coming to accommodate more types, like digital products, variations, and more. Stay tuned!',
										'woocommerce'
									) }
								</p>
							</>
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
