/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
// @ts-ignore No types for this exist yet.
import { __experimentalGrid as Grid } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { FONT_PAIRINGS } from './constants';
import { VariationContainer } from '../variation-container';
import { FontPairingVariationPreview } from './preview';

export const FontPairing = () => {
	return (
		<Grid
			columns={ 2 }
			gap={ 3 }
			className="woocommerce-customize-store_font-pairing-container"
			style={ {
				opacity: 0,
				animation: 'containerFadeIn 1000ms ease-in-out forwards',
			} }
		>
			{ FONT_PAIRINGS.map( ( variation, index ) => (
				<VariationContainer key={ index } variation={ variation }>
					<FontPairingVariationPreview />
				</VariationContainer>
			) ) }
		</Grid>
	);
};
