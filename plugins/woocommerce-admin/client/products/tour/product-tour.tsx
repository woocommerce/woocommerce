/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { TourKit, TourKitTypes } from '@woocommerce/components';

type ProductTourProps = {
	onClose: () => void;
};

export const ProductTour: React.FC< ProductTourProps > = ( { onClose } ) => {
	const tourConfig: TourKitTypes.WooConfig = {
		placement: 'auto',
		options: {
			effects: {
				spotlight: {
					interactivity: {
						enabled: false,
					},
				},
				liveResize: {
					mutation: true,
					resize: true,
				},
			},
		},
		steps: [
			{
				referenceElements: {
					desktop: `.woocommerce-product-form-tab__general .woocommerce-form-section__content`,
				},
				meta: {
					name: 'story',
					heading: __(
						'📣 Tell a story about your product',
						'woocommerce'
					),
					descriptions: {
						desktop: __(
							'The product form will help you describe your product field by field—from basic details like name and description to attributes the customers can use to find it on your store.',
							'woocommerce'
						),
					},
				},
			},
			{
				referenceElements: {
					desktop: `#tab-panel-0-pricing`,
				},
				meta: {
					name: 'tabs',
					heading: __( '✍️ Set up pricing & more', 'woocommerce' ),
					descriptions: {
						desktop: __(
							'When done, use the tabs to switch between other details and settings. In the future, you’ll also find here extensions and plugins.',
							'woocommerce'
						),
					},
				},
			},
			{
				referenceElements: {
					desktop: `.woocommerce-product-form-actions`,
				},
				meta: {
					name: 'actions',
					heading: __( '🔍 Preview and publish', 'woocommerce' ),
					descriptions: {
						desktop: __(
							'With all the details in place, use the buttons at the top to easily preview and publish your product. Click the arrow button for more options.',
							'woocommerce'
						),
					},
				},
			},
			{
				referenceElements: {
					desktop: `.woocommerce-product-form-more-menu`,
				},
				meta: {
					name: 'more',
					heading: __( '⚙️ Looking for more?', 'woocommerce' ),
					descriptions: {
						desktop: __(
							'If the form doesn’t yet have all the feautures you need—it’s still in development—you can switch to the classic editor anytime.',
							'woocommerce'
						),
					},
				},
			},
		],
		closeHandler: onClose,
	};

	return <TourKit config={ tourConfig }></TourKit>;
};
