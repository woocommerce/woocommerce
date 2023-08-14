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
} from '@wordpress/block-editor';

const MAX_HEIGHT = 2000;
const { Provider: DisabledProvider } = Disabled.Context;

function ScaledBlockPreview( {
	viewportWidth,
	containerWidth,
	minHeight,
	settings,
	additionalStyles,
	onClickNavigationItem,
} ) {
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
	MemoizedBlockList = MemoizedBlockList || pure( BlockList );

	const scale = containerWidth / viewportWidth;
	return (
		<DisabledProvider value={ true }>
			<div
				className="block-editor-block-preview__content"
				style={ {
					transform: `scale(${ scale })`,
					height: contentHeight * scale,
					maxHeight:
						contentHeight > MAX_HEIGHT
							? MAX_HEIGHT * scale
							: undefined,
					minHeight,
				} }
			>
				<Iframe
					contentRef={ useRefEffect( ( bodyElement ) => {
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

						const rootContainer =
							bodyElement.querySelector( '.is-root-container' );

						if ( rootContainer ) {
							// Remove mousemove event listener from root container to disable block tool insertion feature.
							rootContainer.addEventListener(
								'mousemove',
								( event ) => {
									event.stopImmediatePropagation();
								},
								true
							);
						}

						let navigationContainers;
						let siteTitles;
						const onClickNavigation = ( event ) => {
							event.preventDefault();
							if ( event.target.href ) {
								onClickNavigationItem( event );
							}
						};

						const possiblyRemoveAllListeners = () => {
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
						};
					}, [] ) }
					aria-hidden
					tabIndex={ -1 }
					style={ {
						position: 'absolute',
						width: viewportWidth,
						height: contentHeight,
						// This is a catch-all max-height for patterns.
						// See: https://github.com/WordPress/gutenberg/pull/38175.
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
						.block-list-appender {
							display: none !important;
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
					<MemoizedBlockList renderAppender={ false } />
				</Iframe>
			</div>
		</DisabledProvider>
	);
}

export default function AutoBlockPreview( props ) {
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
}
