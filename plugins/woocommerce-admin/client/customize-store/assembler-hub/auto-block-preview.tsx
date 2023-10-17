// Reference: https://github.com/WordPress/gutenberg/blob/release/16.4/packages/block-editor/src/components/block-preview/auto.js

/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { useResizeObserver, pure, useRefEffect } from '@wordpress/compose';
import { useContext } from '@wordpress/element';
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
import {
	FontFamiliesLoader,
	FontFamily,
} from './sidebar/global-styles/font-pairing-variations/font-families-loader';
import { SYSTEM_FONT_SLUG } from './sidebar/global-styles/font-pairing-variations/constants';

// @ts-ignore No types for this exist yet.
const { Provider: DisabledProvider } = Disabled.Context;

// This is used to avoid rendering the block list if the sizes change.
let MemoizedBlockList: typeof BlockList | undefined;

const { useGlobalSetting } = unlock( blockEditorPrivateApis );

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
};

function ScaledBlockPreview( {
	viewportWidth,
	containerWidth,
	settings,
	additionalStyles,
	onClickNavigationItem,
	isNavigable = false,
	isScrollable = true,
}: ScaledBlockPreviewProps ) {
	const { setLogoBlock } = useContext( LogoBlockContext );
	const [ fontFamilies ] = useGlobalSetting(
		'typography.fontFamilies.theme'
	) as [ FontFamily[] ];

	const externalFontFamilies = fontFamilies.filter(
		( { slug } ) => slug !== SYSTEM_FONT_SLUG
	);

	if ( ! viewportWidth ) {
		viewportWidth = containerWidth;
	}

	// Initialize on render instead of module top level, to avoid circular dependency issues.
	MemoizedBlockList = MemoizedBlockList || pure( BlockList );

	return (
		<DisabledProvider value={ true }>
			<Iframe
				aria-hidden
				scrolling={ isScrollable ? 'yes' : 'no' }
				tabIndex={ -1 }
				readonly={ ! isNavigable }
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

						let navigationContainers: NodeListOf< HTMLDivElement >;
						let siteTitles: NodeListOf< HTMLAnchorElement >;
						const onClickNavigation = ( event: MouseEvent ) => {
							event.preventDefault();
							onClickNavigationItem( event );
						};

						const onMouseMove = ( event: MouseEvent ) => {
							event.stopImmediatePropagation();
						};

						const possiblyRemoveAllListeners = () => {
							bodyElement.removeEventListener(
								'mousemove',
								onMouseMove,
								false
							);
							if ( navigationContainers ) {
								navigationContainers.forEach( ( element ) => {
									element.removeEventListener(
										'click',
										onClickNavigation
									);
								} );
							}

							if ( siteTitles ) {
								siteTitles.forEach( ( element ) => {
									element.removeEventListener(
										'click',
										onClickNavigation
									);
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
									element.removeAttribute(
										'contenteditable'
									);
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
								element.addEventListener(
									'click',
									onClickNavigation,
									true
								);
							} );

							siteTitles = bodyElement.querySelectorAll(
								'.wp-block-site-title a'
							);
							siteTitles.forEach( ( element ) => {
								element.addEventListener(
									'click',
									onClickNavigation,
									true
								);
							} );
						};

						const onChange = () => {
							// Get the current logo block client ID from DOM and set it in the logo block context. This is used for the logo settings. See: ./sidebar/sidebar-navigation-screen-logo.tsx
							// Ideally, we should be able to get the logo block client ID from the block editor store but it is not available.
							// We should update this code once the there is a selector in the block editor store that can be used to get the logo block client ID.
							const siteLogo = bodyElement.querySelector(
								'.wp-block-site-logo'
							);

							const blockClientId = siteLogo
								? siteLogo.getAttribute( 'data-block' )
								: null;

							setLogoBlock( {
								clientId: blockClientId,
								isLoading: false,
							} );

							if ( isNavigable ) {
								enableNavigation();
							}
						};

						// Stop mousemove event listener to disable block tool insertion feature.
						bodyElement.addEventListener(
							'mousemove',
							onMouseMove,
							true
						);

						const observer = new window.MutationObserver(
							onChange
						);

						observer.observe( bodyElement, {
							attributes: true,
							characterData: false,
							subtree: true,
							childList: true,
						} );

						return () => {
							observer.disconnect();
							possiblyRemoveAllListeners();
							setLogoBlock( {
								clientId: null,
								isLoading: true,
							} );
						};
					},
					[ isNavigable ]
				) }
			>
				<EditorStyles styles={ settings.styles } />
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
				{ /* Only load font families when there are two font families (font-paring selection). Otherwise, it is not needed. */ }
				{ externalFontFamilies.length === 2 && (
					<FontFamiliesLoader
						fontFamilies={ externalFontFamilies }
						onLoad={ noop }
					/>
				) }
			</Iframe>
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
