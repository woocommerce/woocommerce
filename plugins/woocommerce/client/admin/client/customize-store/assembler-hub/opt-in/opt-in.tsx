/**
 * External dependencies
 */
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import apiFetch from '@wordpress/api-fetch';
import { dispatch, resolveSelect, select, useSelect } from '@wordpress/data';
import { useContext, useEffect } from '@wordpress/element';
// @ts-expect-error No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { store as coreStore } from '@wordpress/core-data';
// @ts-expect-error No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { privateApis as blockEditorPrivateApis } from '@wordpress/block-editor';
// @ts-expect-error No types for this exist yet.
// eslint-disable-next-line @woocommerce/dependency-group
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';

/**
 * Internal dependencies
 */
import { FontFamily, FontFace } from '../../types/font';
import { installFontFamilies } from '../utils/fonts';
import { FONT_FAMILIES_TO_INSTALL } from '../sidebar/global-styles/font-pairing-variations/constants';
import { OptInContext, OPTIN_FLOW_STATUS } from './context';

type FontFamilies = {
	custom: Array< FontFamily >;
	theme: Array< FontFamily >;
};

const { useGlobalSetting } = unlock( blockEditorPrivateApis );

async function installPatterns() {
	await apiFetch( {
		path: '/wc/private/patterns',
		method: 'POST',
	} );

	// @ts-expect-error -- No types for this exist yet.
	await dispatch( coreStore ).invalidateResolutionForStoreSelector(
		'getBlockPatterns'
	);
}

async function installFonts(
	enabledFontFamilies: FontFamilies
): Promise< FontFamilies > {
	await installFontFamilies();

	const globalStylesId =
		// @ts-expect-error No types for this exist yet.
		select( coreStore ).__experimentalGetCurrentGlobalStylesId();

	const installedFontFamilies = ( await resolveSelect(
		coreStore
		// @ts-expect-error No types for this exist yet.
	).getEntityRecords( 'postType', 'wp_font_family', {
		_embed: true,
		per_page: -1,
	} ) ) as Array< {
		id: number;
		font_family_settings: FontFamily;
		_embedded: {
			font_faces: Array< {
				font_face_settings: FontFace;
			} >;
		};
	} >;

	const parsedInstalledFontFamilies = ( installedFontFamilies || [] ).map(
		( fontFamilyPost ) => {
			return {
				id: fontFamilyPost.id,
				...fontFamilyPost.font_family_settings,
				fontFace:
					fontFamilyPost?._embedded?.font_faces.map(
						( face ) => face.font_face_settings
					) || [],
			};
		}
	);

	const { custom } = enabledFontFamilies;

	const enabledFontSlugs = [
		...( custom ? custom.map( ( font ) => font.slug ) : [] ),
	];

	const fontFamiliesToEnable = parsedInstalledFontFamilies.reduce(
		( acc, font ) => {
			if (
				enabledFontSlugs.includes( font.slug ) ||
				FONT_FAMILIES_TO_INSTALL[ font.slug ] === undefined
			) {
				return acc;
			}

			return [ ...acc, { ...font } ];
		},
		[] as Array< FontFamily >
	);

	const {
		// @ts-expect-error No types for this exist yet.
		__experimentalSaveSpecifiedEntityEdits: saveSpecifiedEntityEdits,
	} = dispatch( coreStore );

	saveSpecifiedEntityEdits( 'root', 'globalStyles', globalStylesId, [
		'settings.typography.fontFamilies',
	] );

	return {
		...enabledFontFamilies,
		custom: [
			...( enabledFontFamilies.custom ?? [] ),
			...( fontFamiliesToEnable ?? [] ),
		],
	};
}

export const OptInSubscribe = () => {
	const { setOptInFlowStatus } = useContext( OptInContext );

	const [ enabledFontFamilies, setFontFamilies ]: [
		FontFamilies,
		( font: FontFamilies ) => void
	] = useGlobalSetting( 'typography.fontFamilies' );

	const isOptedIn = useSelect( ( selectStore ) => {
		const allowTracking = selectStore( OPTIONS_STORE_NAME ).getOption(
			'woocommerce_allow_tracking'
		);
		return allowTracking === 'yes';
	}, [] );

	useEffect(
		function optedInListener() {
			if ( ! isOptedIn ) return;

			async function installPatternsAndFonts() {
				await installPatterns();
				const fontFamilies = await installFonts( enabledFontFamilies );
				setFontFamilies( fontFamilies );
			}

			setOptInFlowStatus( OPTIN_FLOW_STATUS.LOADING );
			installPatternsAndFonts().finally( () => {
				setOptInFlowStatus( OPTIN_FLOW_STATUS.DONE );
			} );
		},
		// We don't want to run this effect on every render, only when `woocommerce_allow_tracking` changes.
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[ isOptedIn ]
	);

	return null;
};
