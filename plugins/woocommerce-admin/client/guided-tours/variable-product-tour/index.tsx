/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { TourKit } from '@woocommerce/components';
import { useUserPreferences } from '@woocommerce/data';

export const VariableProductTour = () => {
	const [ isTourOpen, setIsTourOpen ] = useState( false );

	const { updateUserPreferences, variable_product_tour_shown: hasShownTour } =
		useUserPreferences();

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
		closeHandler: () => {
			updateUserPreferences( { variable_product_tour_shown: 'yes' } );
			setIsTourOpen( false );
		},
	};

	// show the tour when the product type is changed to variable
	useEffect( () => {
		const productTypeSelect = document.querySelector(
			'#product-type'
		) as HTMLSelectElement;

		if ( hasShownTour === 'yes' || ! productTypeSelect ) {
			return;
		}

		function handleProductTypeChange() {
			if ( productTypeSelect.value === 'variable' ) {
				setIsTourOpen( true );
			}
		}

		productTypeSelect.addEventListener( 'change', handleProductTypeChange );

		return () => {
			productTypeSelect.removeEventListener(
				'change',
				handleProductTypeChange
			);
		};
	} );

	if ( ! isTourOpen ) {
		return null;
	}

	return <TourKit config={ config } />;
};
