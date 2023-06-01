/**
 * External dependencies
 */

import { TourKit } from '@woocommerce/components';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useBlockEditorTour } from './use-block-editor-tour';

const BlockEditorTour = () => {
	const { isClosed, dismissModal } = useBlockEditorTour();

	const [ height, setHeight ] = useState( window.innerHeight );
	useEffect( () => {
		function handleResize() {
			setHeight( window.innerHeight );
		}
		window.addEventListener('resize', handleResize);
		return () => {
			window.removeEventListener('resize', handleResize);
		}
	}, []);

	if ( isClosed ) {
		return null;
	}

	return (
		<TourKit
			config={ {
				steps: [
					{
						meta: {
							name: 'woocommerce-block-editor-tour',
							primaryButton: {
								text: __( 'View highlights', 'woocommerce' ),
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
				closeHandler: dismissModal,
				options: {
					effects: {
						arrowIndicator: false,
						overlay: false,
						liveResize: {
							rootElementSelector: '#adminmenuback',
							resize: true,
						},
					},
					popperModifiers: [
						{
							name: 'offset',
							options: {
								offset: [
									// 180 is the TourKitStep's height.
									height / 2 - 180,
									10,
								],
							},
						},
					],
				},
				placement: 'bottom-end',
			} }
		/>
	);
};

export default BlockEditorTour;
