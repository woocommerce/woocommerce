// Reference: https://github.com/WordPress/gutenberg/blob/release/16.4/packages/block-editor/src/components/block-preview/auto.js

/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { useResizeObserver, pure, useRefEffect } from '@wordpress/compose';
import { useMemo } from '@wordpress/element';
import { Disabled } from '@wordpress/components';
import {
	__unstableEditorStyles as EditorStyles,
	__unstableIframe as Iframe,
	BlockList,
	MemoizedBlockList,
	// @ts-ignore No types for this exist yet.
} from '@wordpress/block-editor';

const MAX_HEIGHT = 2000;
// @ts-ignore No types for this exist yet.
const { Provider: DisabledProvider } = Disabled.Context;

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
};

function ScaledBlockPreview( {
	viewportWidth,
	containerWidth,
	minHeight,
	settings,
	additionalStyles,
	onClickNavigationItem,
}: ScaledBlockPreviewProps ) {
	if ( ! viewportWidth ) {
		viewportWidth = containerWidth;
	}

	const [ contentResizeListener, { height: contentHeight } ] =
		useResizeObserver();

	// Avoid scrollbars for pattern previews.
	const editorStyles = useMemo( () => {
		return [
			{
				css: 'body{height:auto;overflow:hidden;border:none;padding:0;}',
				__unstableType: 'presets',
			},
			...settings.styles,
		];
	}, [ settings.styles ] );

	// Initialize on render instead of module top level, to avoid circular dependency issues.
	const RenderedBlockList = MemoizedBlockList || pure( BlockList );
	const scale = containerWidth / viewportWidth;

	return (
		<DisabledProvider value={ true }>
			<Iframe
				contentRef={ useRefEffect( ( bodyElement: HTMLBodyElement ) => {
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

					const onChange = () => {
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

					// Stop mousemove event listener to disable block tool insertion feature.
					bodyElement.addEventListener(
						'mousemove',
						onMouseMove,
						true
					);

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
					};
				}, [] ) }
				aria-hidden
				tabIndex={ -1 }
				style={ {
					width: viewportWidth,
					height: contentHeight,
					// This is a catch-all max-height for patterns.
					// Reference: https://github.com/WordPress/gutenberg/pull/38175.
					maxHeight: MAX_HEIGHT,
					minHeight:
						scale !== 0 && scale < 1 && minHeight
							? minHeight / scale
							: minHeight,
				} }
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

						.wp-block-navigation .wp-block-pages-list__item__link {
							pointer-events: all !important;
							cursor: pointer !important;
						}

						${ additionalStyles }
					` }
				</style>
				{ contentResizeListener }
				<RenderedBlockList renderAppender={ false } />
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
