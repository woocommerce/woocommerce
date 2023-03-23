/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { TourKit } from '@woocommerce/components';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

const VARIABLE_PRODUCT_TOUR_OPTION = 'woocommerce_variable_product_tour_shown';

function useHasShownVariableProductTour(): undefined | boolean {
	const { hasShownTour } = useSelect( ( select ) => {
		const { getOption } = select( OPTIONS_STORE_NAME );

		return {
			hasShownTour: getOption( VARIABLE_PRODUCT_TOUR_OPTION ) as
				| boolean
				| undefined,
		};
	} );

	return hasShownTour;
}

export const VariableProductTour = () => {
	const hasShownTour = useHasShownVariableProductTour();
	const [ isTourOpen, setIsTourOpen ] = useState( false );

	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );

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
			updateOptions( { [ VARIABLE_PRODUCT_TOUR_OPTION ]: true } );
			setIsTourOpen( false );
		},
	};

	// show the tour when the product type is changed to variable
	useEffect( () => {
		const productTypeSelect = document.querySelector(
			'#product-type'
		) as HTMLSelectElement;

		if ( hasShownTour || ! productTypeSelect ) {
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
