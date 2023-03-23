/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { TourKit } from '@woocommerce/components';

export const VariableProductTour = () => {
	const [ isTourOpen, setIsTourOpen ] = useState( true );

	const config = {
		steps: [
			{
				referenceElements: {
					desktop: '.attribute_tab',
				},
				focusElement: {
					desktop: '.attribute_tab',
				},
				meta: {
					name: 'attributes',
					heading: __( 'Start by adding attributes', 'woocommerce' ),
					descriptions: {
						desktop: __(
							'Add attributes like size and color for customers to choose from on the product page. We will use them to generate product variations.',
							'woocommerce'
						),
					},
					primaryButton: {
						text: __( 'Got it', 'woocommerce' ),
					},
				},
			},
		],
		closeHandler: () => setIsTourOpen( false ),
	};

	if ( ! isTourOpen ) {
		return null;
	}

	return <TourKit config={ config } />;
};
