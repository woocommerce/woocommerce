/**
 * External dependencies
 */
import { __experimentalGrid as Grid, Spinner } from '@wordpress/components';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { COLOR_PALETTES, DEFAULT_COLOR_PALETTES } from './constants';
import { VariationContainer } from '../variation-container';
import { ColorPaletteVariationPreview } from './preview';
import { ColorPaletteResponse } from '~/customize-store/design-with-ai/types';

export const ColorPalette = () => {
	const { aiSuggestions, isLoading } = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );
		return {
			aiSuggestions: getOption(
				'woocommerce_customize_store_ai_suggestions'
			) as { defaultColorPalette: ColorPaletteResponse },
			isLoading: ! hasFinishedResolution( 'getOption', [
				'woocommerce_customize_store_ai_suggestions',
			] ),
		};
	} );

	const [ colorPalettes, setColorPalettes ] = useState(
		[] as typeof COLOR_PALETTES
	);

	useEffect( () => {
		if ( ! isLoading ) {
			if (
				aiSuggestions?.defaultColorPalette?.bestColors?.length > 0 &&
				aiSuggestions?.defaultColorPalette?.default
			) {
				setColorPalettes(
					COLOR_PALETTES.filter(
						( palette ) =>
							aiSuggestions.defaultColorPalette?.bestColors.includes(
								palette.title
							) ||
							aiSuggestions.defaultColorPalette.default ===
								palette.title
					)
				);
			} else {
				// seems that aiSuggestions weren't correctly populated, we'll just use the first 9
				setColorPalettes(
					DEFAULT_COLOR_PALETTES as typeof COLOR_PALETTES
				);
			}
		}
	}, [ isLoading, aiSuggestions?.defaultColorPalette ] );

	if ( isLoading ) {
		return (
			<div className="woocommerce-customize-store_color-palette-spinner-container">
				<Spinner />
			</div>
		);
	}

	return (
		<Grid
			columns={ 3 }
			className="woocommerce-customize-store_color-palette-container"
		>
			{ colorPalettes?.map( ( variation, index ) => (
				<VariationContainer key={ index } variation={ variation }>
					<ColorPaletteVariationPreview title={ variation?.title } />
				</VariationContainer>
			) ) }
		</Grid>
	);
};
