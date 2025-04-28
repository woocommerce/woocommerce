// Reference: https://github.com/WordPress/gutenberg/blob/d5ab7238e53d0947d4bb0853464b1c58325b6130/packages/edit-site/src/components/global-styles/style-variations-container.js

/**
 * External dependencies
 */
import { __experimentalGrid as Grid } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { VariationContainer } from '../variation-container';
import { COLOR_PALETTES, DEFAULT_COLOR_PALETTES } from './constants';
import { ColorPaletteVariationPreview } from './preview';

export const ColorPalette = () => {
	const [ colorPalettes, setColorPalettes ] = useState(
		[] as typeof COLOR_PALETTES
	);

	useEffect( () => {
		// seems that aiSuggestions weren't correctly populated, we'll just use the first 9
		setColorPalettes( DEFAULT_COLOR_PALETTES as typeof COLOR_PALETTES );
	}, [] );

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
