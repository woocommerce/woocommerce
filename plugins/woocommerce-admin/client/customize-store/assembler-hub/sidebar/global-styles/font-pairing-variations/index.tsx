/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
// @ts-ignore No types for this exist yet.
import { __experimentalGrid as Grid, Spinner } from '@wordpress/components';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';
import { useContext, useMemo } from '@wordpress/element';
import {
	privateApis as blockEditorPrivateApis,
	// @ts-ignore no types exist yet.
} from '@wordpress/block-editor';
// @ts-expect-error no types exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';

/**
 * Internal dependencies
 */
import {
	FONT_PAIRINGS,
	FONT_PAIRINGS_WHEN_AI_IS_OFFLINE,
	FONT_PAIRINGS_WHEN_USER_DID_NOT_ALLOW_TRACKING,
} from './constants';
import { VariationContainer } from '../variation-container';
import { FontPairingVariationPreview } from './preview';
import { Look } from '~/customize-store/design-with-ai/types';
import { CustomizeStoreContext } from '~/customize-store/assembler-hub';
import { FlowType } from '~/customize-store/types';
import { FontFamily } from './font-families-loader-dot-com';
import { isAIFlow } from '~/customize-store/guards';

export const FontPairing = () => {
	const { aiSuggestions, isLoading } = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );
		return {
			aiSuggestions: getOption(
				'woocommerce_customize_store_ai_suggestions'
			) as { lookAndFeel: Look },
			isLoading: ! hasFinishedResolution( 'getOption', [
				'woocommerce_customize_store_ai_suggestions',
			] ),
		};
	} );

	const { useGlobalSetting } = unlock( blockEditorPrivateApis );

	const [ custom ] = useGlobalSetting( 'typography.fontFamilies.custom' ) as [
		Array< FontFamily >
	];

	// theme.json file font families
	const [ baseFontFamilies ] = useGlobalSetting(
		'typography.fontFamilies',
		undefined,
		'base'
	) as [
		{
			theme: Array< FontFamily >;
		}
	];

	const { context } = useContext( CustomizeStoreContext );
	const aiOnline = context.flowType === FlowType.AIOnline;
	const isFontLibraryAvailable = context.isFontLibraryAvailable;
	const trackingAllowed = useSelect(
		( select ) =>
			select( OPTIONS_STORE_NAME ).getOption(
				'woocommerce_allow_tracking'
			) === 'yes'
	);

	const fontPairings = useMemo( () => {
		if ( isAIFlow( context.flowType ) ) {
			return aiOnline && aiSuggestions?.lookAndFeel
				? FONT_PAIRINGS.filter( ( font ) =>
						font.lookAndFeel.includes( aiSuggestions?.lookAndFeel )
				  )
				: FONT_PAIRINGS_WHEN_AI_IS_OFFLINE;
		}

		const defaultFonts = FONT_PAIRINGS_WHEN_USER_DID_NOT_ALLOW_TRACKING.map(
			( pair ) => {
				const fontFamilies = pair.settings.typography.fontFamilies;

				const fonts = baseFontFamilies.theme.filter(
					( baseFontFamily ) =>
						fontFamilies.theme.some(
							( themeFont ) =>
								themeFont.fontFamily === baseFontFamily.name
						)
				);

				return {
					...pair,
					settings: {
						typography: {
							fontFamilies: {
								theme: fonts,
							},
						},
					},
				};
			}
		);

		if ( ! trackingAllowed || ! isFontLibraryAvailable ) {
			return defaultFonts;
		}

		const customFonts = FONT_PAIRINGS_WHEN_AI_IS_OFFLINE.map( ( pair ) => {
			const fontFamilies = pair.settings.typography.fontFamilies;
			const fonts = custom.filter( ( customFont ) =>
				fontFamilies.theme.some(
					( themeFont ) => themeFont.slug === customFont.slug
				)
			);

			return {
				...pair,
				settings: {
					typography: {
						fontFamilies: {
							theme: fonts,
						},
					},
				},
			};
		} );

		return [ ...defaultFonts, ...customFonts ];
	}, [
		aiOnline,
		aiSuggestions?.lookAndFeel,
		baseFontFamilies,
		context.flowType,
		custom,
		isFontLibraryAvailable,
		trackingAllowed,
	] );

	if ( isLoading ) {
		return (
			<div className="woocommerce-customize-store_font-pairing-spinner-container">
				<Spinner />
			</div>
		);
	}

	return (
		<Grid
			columns={ 2 }
			gap={ 3 }
			className="woocommerce-customize-store_font-pairing-container"
			style={ {
				opacity: 0,
				animation: 'containerFadeIn 300ms ease-in-out forwards',
			} }
		>
			{ fontPairings.map( ( variation, index ) => (
				<VariationContainer key={ index } variation={ variation }>
					<FontPairingVariationPreview />
				</VariationContainer>
			) ) }
		</Grid>
	);
};
