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
	useEffect( () => {
		const query = qs.parse( window.location.search.slice( 1 ) );
		if ( query && query.tutorial === 'true' ) {
			// Delay tour show up because Task Reminder Bar changes the product name input position after this component is first rendered
			// TODO: use a better way to handle this.
			setTimeout( () => setShowTour( true ), 1500 );
		}

		const enableGuideModeBtn = Array.from(
			window.document.querySelectorAll( '.page-title-action' )
		).find( ( el ) => el.textContent === 'Enable guided mode' );

		if ( enableGuideModeBtn ) {
			enableGuideModeBtn.addEventListener( 'click', ( e ) => {
				e.preventDefault();
				setShowTour( true );
			} );
		}
	}, [] );

	const config: TourKitTypes.WooConfig = {
		options: {
			effects: {
				spotlight: {},
				arrowIndicator: true,
				autoScroll: {
					behavior: 'smooth',
					block: 'center',
				},
			},
		},
		steps: [
			{
				referenceElements: {
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

	if ( ! showTour ) {
		return null;
	}

	return <TourKit config={ config } />;
};

render( <ProductTour />, document.body.appendChild( root ) );
