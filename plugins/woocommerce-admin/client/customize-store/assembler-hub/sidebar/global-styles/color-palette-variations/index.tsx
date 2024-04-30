// Reference: https://github.com/WordPress/gutenberg/blob/d5ab7238e53d0947d4bb0853464b1c58325b6130/packages/edit-site/src/components/global-styles/style-variations-container.js
// @ts-expect-error -- No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { __experimentalGrid as Grid, Spinner } from '@wordpress/components';

/**
 * External dependencies
 */
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { store as coreStore } from '@wordpress/core-data';

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

	const variations = useSelect( ( select ) => {
		return select(
			coreStore
			// @ts-ignore
		).__experimentalGetCurrentThemeGlobalStylesVariations();
	}, [] );

	useEffect( () => {
		if ( ! isLoading ) {
			if ( false ) {
			} else {
				// seems that aiSuggestions weren't correctly populated, we'll just use the first 9
				setColorPalettes(
					variations !== undefined
						? variations
						: DEFAULT_COLOR_PALETTES
				);
			}
		}
	}, [ isLoading, variations, colorPalettes ] );

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
