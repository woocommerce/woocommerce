// Reference: https://github.com/WordPress/gutenberg/blob/d5ab7238e53d0947d4bb0853464b1c58325b6130/packages/edit-site/src/components/global-styles/style-variations-container.js

/**
 * External dependencies
 */
import { __experimentalGrid as Grid } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { VariationContainer } from '../variation-container';
import { DEFAULT_COLOR_PALETTES } from './constants';
import { ColorPaletteVariationPreview } from './preview';

export const ColorPalette = () => {
	return (
		<Grid
			columns={ 3 }
			className="woocommerce-customize-store_color-palette-container"
		>
			{ DEFAULT_COLOR_PALETTES?.map( ( variation, index ) => (
				<VariationContainer key={ index } variation={ variation }>
					<ColorPaletteVariationPreview title={ variation?.title } />
				</VariationContainer>
			) ) }
		</Grid>
	);
};
