// Reference: https://github.com/Automattic/wp-calypso/blob/d3c9b16fb99ce242f61baa21119b7c20f8823be6/packages/global-styles/src/components/color-palette-variations/preview.tsx#L20
/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import {
	privateApis as blockEditorPrivateApis,
	// @ts-ignore no types exist yet.
} from '@wordpress/block-editor';
import {
	// @ts-ignore No types for this exist yet.
	__experimentalHStack as HStack,
	// @ts-ignore No types for this exist yet.
	__experimentalVStack as VStack,
} from '@wordpress/components';
import { useResizeObserver } from '@wordpress/compose';
// @ts-ignore No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { GlobalStylesVariationIframe } from '../global-styles-variation-iframe';

export interface Color {
	color: string;
	name: string;
	slug: string;
}

const STYLE_PREVIEW_HEIGHT = 44;
const STYLE_PREVIEW_COLOR_SWATCH_SIZE = 16;

const { useGlobalSetting, useGlobalStyle } = unlock( blockEditorPrivateApis );

interface Props {
	title?: string;
}

export const ColorPaletteVariationPreview = ( { title }: Props ) => {
	const [ fontWeight ] = useGlobalStyle( 'typography.fontWeight' );
	const [ fontFamily = 'serif' ] = useGlobalStyle( 'typography.fontFamily' );
	const [ headingFontFamily = fontFamily ] = useGlobalStyle(
		'elements.h1.typography.fontFamily'
	);
	const [ headingFontWeight = fontWeight ] = useGlobalStyle(
		'elements.h1.typography.fontWeight'
	);
	const [ textColor = 'black' ] = useGlobalStyle( 'color.text' );
	const [ headingColor = textColor ] = useGlobalStyle(
		'elements.h1.color.text'
	);
	const [ backgroundColor = 'white' ] = useGlobalStyle( 'color.background' );
	const [ gradientValue ] = useGlobalStyle( 'color.gradient' );
	const [ themeColors ] = useGlobalSetting( 'color.palette.theme' );
	const [ containerResizeListener, { width } ] = useResizeObserver();
	const normalizedHeight = STYLE_PREVIEW_HEIGHT;
	const normalizedSwatchSize = STYLE_PREVIEW_COLOR_SWATCH_SIZE;
	const uniqueColors = [
		...new Set< string >(
			themeColors.map( ( { color }: Color ) => color )
		),
	];
	const highlightedColors = uniqueColors
		.filter(
			// we exclude background color because it is already visible in the preview.
			( color ) => color !== backgroundColor
		)
		.slice( 0, 2 );

	return (
		<GlobalStylesVariationIframe
			width={ width }
			height={ normalizedHeight }
			containerResizeListener={ containerResizeListener }
		>
			<div
				style={ {
					// Apply the normalized height only when the width is available
					height: width ? normalizedHeight : 0,
					width: '100%',
					background: gradientValue ?? backgroundColor,
					cursor: 'pointer',
				} }
			>
				<div
					style={ {
						height: '100%',
						overflow: 'hidden',
					} }
				>
					{ title ? (
						<HStack
							spacing={ 1.8875 }
							justify="center"
							style={ {
								height: '100%',
								overflow: 'hidden',
							} }
						>
							{ highlightedColors.map( ( color, index ) => (
								<div
									key={ index }
									style={ {
										height: normalizedSwatchSize,
										width: normalizedSwatchSize,
										background: color,
										borderRadius: normalizedSwatchSize / 2,
									} }
								/>
							) ) }
						</HStack>
					) : (
						<VStack
							spacing={ 3 }
							justify="center"
							style={ {
								height: '100%',
								overflow: 'hidden',
								padding: 10,
								boxSizing: 'border-box',
							} }
						>
							<div
								style={ {
									fontSize: 40,
									fontFamily: headingFontFamily,
									color: headingColor,
									fontWeight: headingFontWeight,
									lineHeight: '1em',
									textAlign: 'center',
								} }
							>
								{ __( 'Default', 'woocommerce' ) }
							</div>
						</VStack>
					) }
				</div>
			</div>
		</GlobalStylesVariationIframe>
	);
};
