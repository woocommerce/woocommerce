/**
 * External dependencies
 */
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import apiFetch from '@wordpress/api-fetch';
import { resolveSelect, select, subscribe, useDispatch } from '@wordpress/data';
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
import { usePatterns } from '../hooks/use-patterns';
import { installFontFamilies } from '../utils/fonts';
import { FONT_FAMILIES_TO_INSTALL } from '../sidebar/global-styles/font-pairing-variations/constants';
import { OptInContext, OPTIN_FLOW_STATUS } from './context';

const { useGlobalSetting } = unlock( blockEditorPrivateApis );

export const OptInSubscribe = () => {
	const { setOptInFlowStatus } = useContext( OptInContext );

	const [ enabledFontFamilies, setFontFamilies ]: [
		{
			custom: Array< FontFamily >;
			theme: Array< FontFamily >;
		},
		( font: {
			custom: Array< FontFamily >;
			theme: Array< FontFamily >;
		} ) => void
	] = useGlobalSetting( 'typography.fontFamilies' );

	const {
		// @ts-expect-error No types for this exist yet.
		__experimentalSaveSpecifiedEntityEdits: saveSpecifiedEntityEdits,
	} = useDispatch( coreStore );

	const installPatterns = async () => {
		await apiFetch< {
			success: boolean;
		} >( {
			path: '/wc-admin/patterns',
			method: 'POST',
		} );
	};

	const installFonts = async () => {
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

				return [
					...acc,
					{
						...font,
					},
				];
			},
			[] as Array< FontFamily >
		);

		setFontFamilies( {
			...enabledFontFamilies,
			custom: [
				...( enabledFontFamilies.custom ?? [] ),
				...( fontFamiliesToEnable ?? [] ),
			],
		} );

		saveSpecifiedEntityEdits( 'root', 'globalStyles', globalStylesId, [
			'settings.typography.fontFamilies',
		] );
	};

	const { invalidateCache } = usePatterns();

	useEffect( () => {
		const unsubscribe = subscribe( async () => {
			const isOptedIn =
				select( OPTIONS_STORE_NAME ).getOption(
					'woocommerce_allow_tracking'
				) === 'yes';

			if ( isOptedIn ) {
				setOptInFlowStatus( OPTIN_FLOW_STATUS.LOADING );
				await installPatterns();
				invalidateCache();
				await installFonts();

				setOptInFlowStatus( OPTIN_FLOW_STATUS.DONE );

				unsubscribe();
			}
			// @ts-expect-error The type is not updated.
		}, OPTIONS_STORE_NAME );

		return () => {
			unsubscribe();
		};
		// We don't want to run this effect on every render, only once because it is a subscription.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	return null;
};
