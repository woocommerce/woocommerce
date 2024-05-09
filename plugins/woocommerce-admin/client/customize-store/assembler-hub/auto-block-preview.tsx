// Reference: https://github.com/WordPress/gutenberg/blob/release/16.4/packages/block-editor/src/components/block-preview/auto.js

/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { useResizeObserver, pure, useRefEffect } from '@wordpress/compose';
import { useContext, useMemo, useState } from '@wordpress/element';
import { Disabled } from '@wordpress/components';
import {
	__unstableEditorStyles as EditorStyles,
	__unstableIframe as Iframe,
	privateApis as blockEditorPrivateApis,
	BlockList,
	// @ts-ignore No types for this exist yet.
} from '@wordpress/block-editor';
// @ts-ignore No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
import { noop } from 'lodash';

/**
 * Internal dependencies
 */
import { LogoBlockContext } from './logo-block-context';
import { SYSTEM_FONT_SLUG } from './sidebar/global-styles/font-pairing-variations/constants';
import { PreloadFonts } from './preload-fonts';
import { FontFamily } from '../types/font';
import { FontFamiliesLoaderDotCom } from './sidebar/global-styles/font-pairing-variations/font-families-loader-dot-com';
import { CustomizeStoreContext } from '.';
import { isAIFlow } from '../guards';
import { Toolbar } from './toolbar/toolbar';

// @ts-ignore No types for this exist yet.
const { Provider: DisabledProvider } = Disabled.Context;

// This is used to avoid rendering the block list if the sizes change.
let MemoizedBlockList: typeof BlockList | undefined;

const { useGlobalSetting } = unlock( blockEditorPrivateApis );
const MAX_HEIGHT = 2000;

export type ScaledBlockPreviewProps = {
	viewportWidth?: number;
	containerWidth: number;
	minHeight?: number;
	settings: {
		styles: string[];
		[ key: string ]: unknown;
	};
	additionalStyles: string;
	onClickNavigationItem: ( event: MouseEvent ) => void;
	isNavigable?: boolean;
	isScrollable?: boolean;
	autoScale?: boolean;
	setLogoBlockContext?: boolean;
	CustomIframeComponent?: React.ComponentType<
		Parameters< typeof Iframe >[ 0 ]
	>;
};

function ScaledBlockPreview( {
	viewportWidth,
	containerWidth,
	settings,
	additionalStyles,
	onClickNavigationItem,
	isNavigable = false,
	isScrollable = true,
	autoScale = true,
	setLogoBlockContext = false,
	CustomIframeComponent = Iframe,
}: ScaledBlockPreviewProps ) {
	const [ contentHeight, setContentHeight ] = useState< number | null >(
		null
	);
	const { setLogoBlockIds } = useContext( LogoBlockContext );
	const [ fontFamilies ] = useGlobalSetting(
		'typography.fontFamilies.theme'
	) as [ FontFamily[] ];
	const externalFontFamilies = fontFamilies.filter(
		( { slug } ) => slug !== SYSTEM_FONT_SLUG
	);

	const { context } = useContext( CustomizeStoreContext );

	if ( ! viewportWidth ) {
		viewportWidth = containerWidth;
	}

	// Avoid scrollbars for pattern previews.
	const editorStyles = useMemo( () => {
		if ( ! isScrollable && settings.styles ) {
			return [
				...settings.styles,
				{
					css: 'body{height:auto;overflow:hidden;border:none;padding:0;}',
					__unstableType: 'presets',
				},
			];
		}

		return settings.styles;
	}, [ settings.styles, isScrollable ] );

	const scale = containerWidth / viewportWidth;
	const aspectRatio = contentHeight
		? containerWidth / ( contentHeight * scale )
		: 0;
	// Initialize on render instead of module top level, to avoid circular dependency issues.
	MemoizedBlockList = MemoizedBlockList || pure( BlockList );

	const updateIframeContent = ( bodyElement: HTMLBodyElement ) => {
		let navigationContainers: NodeListOf< HTMLDivElement >;
		let siteTitles: NodeListOf< HTMLAnchorElement >;

		const onMouseMove = ( event: MouseEvent ) => {
			event.stopImmediatePropagation();
		};

		const onClickNavigation = ( event: MouseEvent ) => {
			event.preventDefault();
			onClickNavigationItem( event );
		};

		const possiblyRemoveAllListeners = () => {
			bodyElement.removeEventListener( 'mousemove', onMouseMove, false );
			if ( navigationContainers ) {
				navigationContainers.forEach( ( element ) => {
					element.removeEventListener( 'click', onClickNavigation );
				} );
			}

			if ( siteTitles ) {
				siteTitles.forEach( ( element ) => {
					element.removeEventListener( 'click', onClickNavigation );
				} );
			}
		};

		const enableNavigation = () => {
			// Remove contenteditable and inert attributes from editable elements so that users can click on navigation links.
			bodyElement
				.querySelectorAll(
					'.block-editor-rich-text__editable[contenteditable="true"]'
				)
				.forEach( ( element ) => {
					element.removeAttribute( 'contenteditable' );
				} );

			bodyElement
				.querySelectorAll( '*[inert="true"]' )
				.forEach( ( element ) => {
					element.removeAttribute( 'inert' );
				} );

			possiblyRemoveAllListeners();
			navigationContainers = bodyElement.querySelectorAll(
				'.wp-block-navigation__container'
			);
			navigationContainers.forEach( ( element ) => {
				element.addEventListener( 'click', onClickNavigation, true );
			} );

			siteTitles = bodyElement.querySelectorAll(
				'.wp-block-site-title a'
			);
			siteTitles.forEach( ( element ) => {
				element.addEventListener( 'click', onClickNavigation, true );
			} );
		};

		const findAndSetLogoBlock = () => {
			// Get the current logo block client ID from DOM and set it in the logo block context. This is used for the logo settings. See: ./sidebar/sidebar-navigation-screen-logo.tsx
			// Ideally, we should be able to get the logo block client ID from the block editor store but it is not available.
			// We should update this code once the there is a selector in the block editor store that can be used to get the logo block client ID.
			const siteLogos = bodyElement.querySelectorAll(
				'.wp-block-site-logo'
			);

			const logoBlockIds = Array.from( siteLogos )
				.map( ( siteLogo ) => {
					return siteLogo.getAttribute( 'data-block' );
				} )
				.filter( Boolean ) as string[];
			setLogoBlockIds( logoBlockIds );
		};

		const onChange = () => {
			if ( autoScale ) {
				const rootContainer =
					bodyElement.querySelector( '.is-root-container' );

				setContentHeight(
					rootContainer ? rootContainer.clientHeight : null
				);
			}

			if ( isNavigable ) {
				enableNavigation();
			}

			if ( setLogoBlockContext ) {
				findAndSetLogoBlock();
			}
		};

		// Stop mousemove event listener to disable block tool insertion feature.
		bodyElement.addEventListener( 'mousemove', onMouseMove, true );

		const observer = new window.MutationObserver( onChange );
		observer.observe( bodyElement, {
			attributes: true,
			characterData: false,
			subtree: true,
			childList: true,
		} );

		return () => {
			observer.disconnect();
			possiblyRemoveAllListeners();

			if ( setLogoBlockContext ) {
				setLogoBlockIds( [] );
			}
		};
	};

	return (
		<DisabledProvider value={ true }>
			<div
				className="block-editor-block-preview__content"
				style={
					autoScale
						? {
								transform: `scale(${ scale })`,
								// Using width + aspect-ratio instead of height here triggers browsers' native
								// handling of scrollbar's visibility. It prevents the flickering issue seen
								// in https://github.com/WordPress/gutenberg/issues/52027.
								// See https://github.com/WordPress/gutenberg/pull/52921 for more info.
								aspectRatio,
								maxHeight:
									contentHeight !== null &&
									contentHeight > MAX_HEIGHT
										? MAX_HEIGHT * scale
										: undefined,
						  }
						: {}
				}
			>
				<CustomIframeComponent
					aria-hidden
					// eslint-disable-next-line @typescript-eslint/ban-ts-comment
					// @ts-ignore disabled prop exists
					scrolling={ isScrollable ? 'yes' : 'no' }
					tabIndex={ -1 }
					readonly={ ! isNavigable }
					style={
						autoScale
							? {
									position: 'absolute',
									width: viewportWidth,
									height: contentHeight,
									pointerEvents: 'none',
									// This is a catch-all max-height for patterns.
									// See: https://github.com/WordPress/gutenberg/pull/38175.
									maxHeight: MAX_HEIGHT,
							  }
							: {}
					}
					contentRef={ useRefEffect(
						( bodyElement: HTMLBodyElement ) => {
							const {
								ownerDocument: { documentElement },
							} = bodyElement;

							documentElement.classList.add(
								'block-editor-block-preview__content-iframe'
							);
							documentElement.style.position = 'absolute';
							documentElement.style.width = '100%';

							// Necessary for contentResizeListener to work.
							bodyElement.style.boxSizing = 'border-box';
							bodyElement.style.position = 'absolute';
							bodyElement.style.width = '100%';

							const cleanup = updateIframeContent( bodyElement );
							return () => {
								cleanup();
								setContentHeight( null );
							};
						},
						[ isNavigable ]
					) }
				>
					<EditorStyles styles={ editorStyles } />
					<style>
						{ `
						.block-editor-block-list__block::before,
						.is-selected::after,
						.is-hovered::after,
						.block-list-appender {
							display: none !important;
						}

						.block-editor-block-list__block.is-selected {
							box-shadow: none !important;
						}

						.block-editor-rich-text__editable {
							pointer-events: none !important;
						}

						.wp-block-site-title .block-editor-rich-text__editable {
							pointer-events: all !important;
						}

						.wp-block-navigation-item .wp-block-navigation-item__content,
						.wp-block-navigation .wp-block-pages-list__item__link {
							pointer-events: all !important;
							cursor: pointer !important;
						}

						${ additionalStyles }
					` }
					</style>
					<MemoizedBlockList renderAppender={ false } />
					<PreloadFonts />
					{ isAIFlow( context.flowType ) && (
						<FontFamiliesLoaderDotCom
							fontFamilies={ externalFontFamilies }
							onLoad={ noop }
						/>
					) }
				</CustomIframeComponent>
			</div>
		</DisabledProvider>
	);
}

export const AutoHeightBlockPreview = (
	props: Omit< ScaledBlockPreviewProps, 'containerWidth' >
) => {
	const [ containerResizeListener, { width: containerWidth } ] =
		useResizeObserver();

	return (
		<>
			<div style={ { position: 'relative', width: '100%', height: 0 } }>
				{ containerResizeListener }
			</div>
			<div className="auto-block-preview__container">
				{ !! containerWidth && (
					<ScaledBlockPreview
						{ ...props }
						containerWidth={ containerWidth }
					/>
				) }
			</div>
		</>
	);
};
