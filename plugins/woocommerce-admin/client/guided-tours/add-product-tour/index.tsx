/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { TourKit, TourKitTypes } from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { useTmceIframeFocusStyle } from './use-tmce-iframe-focus-style';
import { useActiveEditorType } from './use-active-editor-type';
import {
	bindEnableGuideModeClickEvent,
	waitUntilElementTopNotChange,
} from '../utils';
import {
	ProductTourStepName,
	useProductStepChange,
} from './use-product-step-change';
import { useTrackPublishButton } from './use-track-publish-button';

const getTourConfig = ( {
	isExcerptEditorTmceActive,
	isContentEditorTmceActive,
	closeHandler,
	onNextStepHandler,
}: {
	isExcerptEditorTmceActive: boolean;
	isContentEditorTmceActive: boolean;
	closeHandler: TourKitTypes.CloseHandler;
	onNextStepHandler: ( currentStepIndex: number ) => void;
} ): TourKitTypes.WooConfig => {
	const urlParams = new URLSearchParams( window.location.search );
	const defaultSteps: TourKitTypes.WooStep[] = [
		{
			referenceElements: {
				desktop: '#title',
			},
			focusElement: {
				desktop: '#title',
			},
			meta: {
				name: 'product-name',
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
				desktop: '#postdivrich',
			},
			focusElement: {
				iframe: isContentEditorTmceActive ? '#content_ifr' : undefined,
				desktop: isContentEditorTmceActive
					? '#tinymce'
					: '#wp-content-editor-container > .wp-editor-area',
			},
			meta: {
				name: 'product-description',
				heading: __( 'Add your product description', 'woocommerce' ),
				descriptions: {
					desktop: __(
						'Add your full product description here. Describe your product in detail.',
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
				name: 'product-data',
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
				iframe: isExcerptEditorTmceActive ? '#excerpt_ifr' : undefined,
				desktop: isExcerptEditorTmceActive
					? '#tinymce'
					: '#wp-excerpt-editor-container > .wp-editor-area',
			},
			meta: {
				name: 'product-short-description',
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
				name: 'product-image',
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
				name: 'product-tags',
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
				name: 'product-categories',
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
				name: 'publish',
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
	];

	/**
	 * Experimental: Filter for manipulating the product tour.
	 *
	 * @filter experimental_woocommerce_admin_product_tour_steps
	 * @param {Object} WooStep Array of Woo tour guide steps.
	 * @param          string  tutorialType The type of tutorial to display.
	 */
	const steps: TourKitTypes.WooStep[] = applyFilters(
		'experimental_woocommerce_admin_product_tour_steps',
		defaultSteps,
		urlParams.get( 'tutorial_type' )
	) as TourKitTypes.WooStep[];

	if ( ! Array.isArray( steps ) ) {
		throw new Error( 'Tour guide steps must be an array.' );
	}

	return {
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
				liveResize: {
					mutation: true,
					resize: true,
					rootElementSelector: '#wpwrap',
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
			callbacks: {
				onNextStep: onNextStepHandler,
			},
		},
		steps,
		closeHandler,
	};
};

export const ProductTour = () => {
	const [ showTour, setShowTour ] = useState< boolean >( false );
	const { setIsLoaded, hasUpdatedInfo } = useProductStepChange();

	const { isTmce: isContentEditorTmceActive } = useActiveEditorType( {
		editorWrapSelector: '#wp-content-wrap',
	} );
	const { isTmce: isExcerptEditorTmceActive } = useActiveEditorType( {
		editorWrapSelector: '#wp-excerpt-wrap',
	} );

	const { style: contentTmceIframeFocusStyle } = useTmceIframeFocusStyle( {
		isActive: showTour && isContentEditorTmceActive,
		iframeSelector: '#content_ifr',
	} );
	const { style: excerptTmceIframeFocusStyle } = useTmceIframeFocusStyle( {
		isActive: showTour && isExcerptEditorTmceActive,
		iframeSelector: '#excerpt_ifr',
	} );

	const tourConfig = getTourConfig( {
		isContentEditorTmceActive,
		isExcerptEditorTmceActive,
		closeHandler: ( steps, stepIndex ) => {
			setShowTour( false );
			if ( steps.length - 1 === stepIndex ) {
				recordEvent( 'walkthrough_product_completed' );
			} else {
				recordEvent( 'walkthrough_product_dismissed', {
					step_name: steps[ stepIndex ].meta.name,
				} );
			}
		},
		onNextStepHandler: ( newStepIndex ) => {
			const stepName = tourConfig.steps[ newStepIndex - 1 ].meta.name;

			// This records all "next" steps and ignores the final "publish" step.
			recordEvent( 'walkthrough_product_step_completed', {
				step_name: stepName,
				added_info: hasUpdatedInfo( stepName as ProductTourStepName )
					? 'yes'
					: 'no',
			} );
		},
	} );

	useEffect( () => {
		bindEnableGuideModeClickEvent( ( e ) => {
			e.preventDefault();
			setShowTour( true );
			recordEvent( 'walkthrough_product_enable_button_click' );
		} );

		const query = new URLSearchParams( window.location.search );
		if (
			query.get( 'tutorial' ) === 'true' &&
			tourConfig.steps?.length > 0
		) {
			const intervalId = waitUntilElementTopNotChange(
				tourConfig.steps[ 0 ].referenceElements?.desktop || '',
				() => {
					setShowTour( true );
					recordEvent( 'walkthrough_product_view', {
						spotlight: 'yes',
						product_template: 'physical',
					} );
					setIsLoaded( true );
				},
				500
			);
			return () => clearInterval( intervalId );
		}
		// only run once
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	useTrackPublishButton( showTour );

	if ( ! showTour ) {
		return null;
	}

	return (
		<>
			<style>
				{ contentTmceIframeFocusStyle }
				{ excerptTmceIframeFocusStyle }
				{ `.wp-editor-area:focus {
						border: 1.5px solid #007CBA;
					}` }
			</style>
			<TourKit config={ tourConfig } />
		</>
	);
};
