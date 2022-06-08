/**
 * External dependencies
 */
import { render, useEffect, useState } from '@wordpress/element';
import { TourKit, TourKitTypes } from '@woocommerce/components';
import qs from 'qs';
import { __ } from '@wordpress/i18n';

const root = document.createElement( 'div' );
root.setAttribute( 'id', 'product-tour-root' );

const ProductTour = () => {
	const [ showTour, setShowTour ] = useState< boolean >( false );

	const config: TourKitTypes.WooConfig = {
		placement: 'bottom-start',
		options: {
			effects: {
				spotlight: {
					interactivity: {
						enabled: true,
						rootElementSelector: '#wpwrap',
					},
				},
				arrowIndicator: true,
				autoScroll: {
					behavior: 'auto',
					block: 'center',
				},
			},
			popperModifiers: [
				{
					name: 'arrow',
					options: {
						padding: ( {
							popper,
						}: {
							popper: { width: number };
						} ) => {
							return {
								// Align the arrow to the left of the popper.
								right: popper.width - 34,
							};
						},
					},
				},
			],
		},
		steps: [
			{
				referenceElements: {
					desktop: '#title',
				},
				focusElement: {
					desktop: '#title',
				},
				meta: {
					heading: __( 'Product name', 'woocommerce' ),
					descriptions: {
						desktop: __(
							'Start typing your new product name here. This will be what your customers will see in your store.',
							'woocommerce'
						),
					},
				},
			},
			{
				referenceElements: {
					desktop: '#wp-content-editor-container',
				},
				focusElement: {
					iframe: '#content_ifr',
					desktop: '#tinymce',
				},
				meta: {
					heading: __(
						'Add your product description',
						'woocommerce'
					),
					descriptions: {
						desktop: __(
							'Start typing your new product name here. Add your full product description here. Describe your product in detail.',
							'woocommerce'
						),
					},
				},
			},
			{
				referenceElements: {
					desktop: '#woocommerce-product-data',
				},
				focusElement: {
					desktop: '#_regular_price',
				},
				meta: {
					heading: __( 'Add your product data', 'woocommerce' ),
					descriptions: {
						desktop: __(
							'Use the tabs to switch between sections and insert product details. Start by adding your product price.',
							'woocommerce'
						),
					},
				},
			},
			{
				referenceElements: {
					desktop: '#postexcerpt',
				},
				focusElement: {
					iframe: '#excerpt_ifr',
					desktop: '#tinymce',
				},
				meta: {
					heading: __(
						'Add your short product description',
						'woocommerce'
					),
					descriptions: {
						desktop: __(
							'Type a quick summary for your product here. This will appear on the product page right under the product name.',
							'woocommerce'
						),
					},
				},
			},
			{
				referenceElements: {
					desktop: '#postimagediv',
				},
				focusElement: {
					desktop: '#set-post-thumbnail',
				},
				meta: {
					heading: __( 'Add your product image', 'woocommerce' ),
					descriptions: {
						desktop: __(
							'Upload an image to your product here. Ideally a JPEG or PNG about 600 px wide or bigger. This image will be shown in your store’s catalog.',
							'woocommerce'
						),
					},
				},
			},
			{
				referenceElements: {
					desktop: '#tagsdiv-product_tag',
				},
				focusElement: {
					desktop: '#new-tag-product_tag',
				},
				meta: {
					heading: __( 'Add your product tags', 'woocommerce' ),
					descriptions: {
						desktop: __(
							'Add your product tags here. Tags are a method of labeling your products to make them easier for customers to find. For example, if you sell clothing, and you have a lot of cat prints, you could make a tag for “cat.”',
							'woocommerce'
						),
					},
				},
			},
			{
				referenceElements: {
					desktop: '#product_catdiv',
				},
				meta: {
					heading: __( 'Add your product categories', 'woocommerce' ),
					descriptions: {
						desktop: __(
							'Add your product categories here. Assign categories to your products to make them easier to browse through and find in your store.',
							'woocommerce'
						),
					},
				},
			},
			{
				referenceElements: {
					desktop: '#submitdiv',
				},
				focusElement: {
					desktop: '#submitdiv',
				},
				meta: {
					heading: __( 'Publish your product 🎉', 'woocommerce' ),
					descriptions: {
						desktop: __(
							'Good work! Now you can publish your product to your store by hitting the “Publish” button or keep editing it.',
							'woocommerce'
						),
					},
					primaryButton: {
						text: __( 'Keep editing', 'woocommerce' ),
					},
				},
			},
		],
		closeHandler: () => setShowTour( false ),
	};

	useEffect( () => {
		let intervalId: NodeJS.Timeout;

		const bindEnableGuideModeBtnEvent = () => {
			// Overwrite the default behavior of the "Enable guided mode" button when a user clicks it.
			const enableGuideModeBtn = Array.from(
				window.document.querySelectorAll( '.page-title-action' )
			).find( ( el ) => el.textContent === 'Enable guided mode' );

			if ( enableGuideModeBtn ) {
				enableGuideModeBtn.addEventListener( 'click', ( e ) => {
					e.preventDefault();
					setShowTour( true );
				} );
			}
		};

		const waitInitialElementReadyAndShowTour = () => {
			// Wait until the initial element position is ready and then show the tour.
			const initialElement = document.querySelector(
				config.steps[ 0 ].referenceElements?.desktop || ''
			);
			let lastInitialElementTop = initialElement?.getBoundingClientRect()
				.top;

			intervalId = setInterval( () => {
				const top = initialElement?.getBoundingClientRect().top;
				if ( lastInitialElementTop === top ) {
					setShowTour( true );
					if ( intervalId ) {
						clearInterval( intervalId );
					}
				}
				lastInitialElementTop = top;
			}, 500 );
		};

		const query = qs.parse( window.location.search.slice( 1 ) );
		if ( query && query.tutorial === 'true' ) {
			waitInitialElementReadyAndShowTour();
		}

		bindEnableGuideModeBtnEvent();

		return () => clearInterval( intervalId );
		// only run once
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	// Add a focus class to product description & short description when editor is focused.
	useEffect( () => {
		if ( ! showTour ) {
			return;
		}
		const addClassToIframeWhenChildFocus = (
			iframeSelector: string,
			childSelector: string
		) => {
			const iframe = document.querySelector< HTMLIFrameElement >(
				iframeSelector
			);
			const innerDoc =
				iframe?.contentDocument ||
				( iframe?.contentWindow && iframe?.contentWindow.document );

			if ( innerDoc ) {
				const child = innerDoc.querySelector< HTMLElement >(
					childSelector
				);

				const onFocus = () => {
					iframe?.classList.add( 'focus-within' );
				};
				const onBlur = () => iframe?.classList.remove( 'focus-within' );

				child?.addEventListener( 'focus', onFocus );
				child?.addEventListener( 'blur', onBlur );

				return () => {
					child?.removeEventListener( 'focus', onFocus );
					child?.removeEventListener( 'blur', onBlur );
				};
			}
			return () => ( {} );
		};

		const clearContentIFrameEvent = addClassToIframeWhenChildFocus(
			'#content_ifr',
			'#tinymce'
		);
		const clearExcerptIFrameEvent = addClassToIframeWhenChildFocus(
			'#excerpt_ifr',
			'#tinymce'
		);

		return () => {
			clearContentIFrameEvent();
			clearExcerptIFrameEvent();
		};
	}, [ showTour ] );

	if ( ! showTour ) {
		return null;
	}

	return (
		<>
			<style>
				{ `
					#content_ifr.focus-within, #excerpt_ifr.focus-within {
						border: 1.5px solid #007CBA;
					}
				` }
			</style>
			<TourKit config={ config } />
		</>
	);
};

render( <ProductTour />, document.body.appendChild( root ) );
