/**
 * External dependencies
 */
import { Pill, TourKit } from '@woocommerce/components';
import { Guide } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */

import './style.scss';
import BlockEditorGuide from './block-editor-guide';

interface Props {
	shouldTourBeShown: boolean;
	dismissModal: () => void;
}

const BlockEditorTour = ( { shouldTourBeShown, dismissModal }: Props ) => {
	useEffect( () => {
		if ( shouldTourBeShown ) {
			recordEvent( 'block_product_editor_spotlight_view' );
		}
	}, [ shouldTourBeShown ] );

	const [ isGuideOpen, setIsGuideOpen ] = useState( false );

	const openGuide = () => {
		setIsGuideOpen( true );
	};

	const closeGuide = () => {
		recordEvent( 'block_product_editor_spotlight_completed' );
		setIsGuideOpen( false );
	};

	if ( isGuideOpen ) {
		return <BlockEditorGuide onCloseGuide={ closeGuide } />;
	} else if ( shouldTourBeShown ) {
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
								heading: (
									<>
										<span>
											{ __(
												'Meet a streamlined product form',
												'woocommerce'
											) }
										</span>{ ' ' }
										<Pill className="woocommerce-block-editor-guide__pill">
											{ __( 'Beta', 'woocommerce' ) }
										</Pill>
									</>
								),
							},
							referenceElements: {
								desktop: '#adminmenuback',
							},
						},
					],
					closeHandler: ( _steps, _currentStepIndex, source ) => {
						dismissModal();
						if ( source === 'done-btn' ) {
							recordEvent(
								'block_product_editor_spotlight_view_highlights'
							);
							openGuide();
						} else {
							recordEvent(
								'block_product_editor_spotlight_dismissed'
							);
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
