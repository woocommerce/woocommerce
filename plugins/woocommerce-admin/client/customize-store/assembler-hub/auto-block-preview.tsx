// Reference: https://github.com/WordPress/gutenberg/blob/release/16.4/packages/block-editor/src/components/block-preview/auto.js

/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { useResizeObserver, pure } from '@wordpress/compose';
import { useContext, useEffect, useMemo, useState } from '@wordpress/element';
import { Disabled, Popover } from '@wordpress/components';
import {
	__unstableEditorStyles as EditorStyles,
	__unstableIframe as Iframe,
	privateApis as blockEditorPrivateApis,
	BlockList,
	store as blockEditorStore,
	// @ts-ignore No types for this exist yet.
} from '@wordpress/block-editor';
// @ts-ignore No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';

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
import { selectBlockOnHover } from './utils/select-block-on-hover';
import { useDispatch, useSelect } from '@wordpress/data';
import { PopoverStatus, usePopoverHandler } from './hooks/use-popover-handler';
import { noop } from 'lodash';
import { useAddAutoBlockPreviewEventListenersAndObservers } from './hooks/auto-block-preview-event-listener';
import { IsResizingContext } from './resizable-frame';
import { __ } from '@wordpress/i18n';
import { useQuery } from '@woocommerce/navigation';
import clsx from 'clsx';
import { SelectedBlockContext } from './context/selected-block-ref-context';
import { isFullComposabilityFeatureAndAPIAvailable } from './utils/is-full-composability-enabled';
import { useInsertPatternByName } from './hooks/use-insert-pattern-by-name';

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
	isScrollable?: boolean;
	autoScale?: boolean;
	setLogoBlockContext?: boolean;
	CustomIframeComponent?: React.ComponentType<
		Parameters< typeof Iframe >[ 0 ]
	>;
	isPatternPreview: boolean;
};

function ScaledBlockPreview( {
	viewportWidth,
	containerWidth,
	settings,
	additionalStyles,
	isScrollable = true,
	autoScale = true,
	isPatternPreview,
	CustomIframeComponent = Iframe,
}: ScaledBlockPreviewProps ) {
	const [ contentHeight, setContentHeight ] = useState< number | null >(
		null
	);
	const { setLogoBlockIds, logoBlockIds } = useContext( LogoBlockContext );
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

	const [ iframeRef, setIframeRef ] = useState< HTMLElement | null >( null );

	const [
		popoverStatus,
		virtualElement,
		updatePopoverPosition,
		hidePopover,
	] = usePopoverHandler();

	// @ts-expect-error No types for this exist yet.
	const { selectBlock, setBlockEditingMode } =
		useDispatch( blockEditorStore );

	// @ts-expect-error No types for this exist yet.
	const { getBlockParents } = useSelect( blockEditorStore );

	const { setSelectedBlockRef } = useContext( SelectedBlockContext );

	const selectedBlockClientId = useSelect( ( select ) => {
		const block = select( 'core/block-editor' ).getSelectedBlock();

		// @ts-expect-error No types for this exist yet.
		return block?.clientId;
	} );

	useEffect( () => {
		if ( selectedBlockClientId && iframeRef ) {
			const el = iframeRef.querySelector(
				`#block-${ selectedBlockClientId }`
			) as HTMLElement;

			if ( ! el ) {
				return;
			}

			const observer = new MutationObserver( () => {
				setSelectedBlockRef( el );
			} );

			observer.observe( el, {
				attributes: true,
			} );

			return () => {
				observer.disconnect();
			};
		}
	}, [ iframeRef, selectedBlockClientId, setSelectedBlockRef ] );

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

	const isResizing = useContext( IsResizingContext );
	const query = useQuery();

	const { insertPatternByName } = useInsertPatternByName();

	useAddAutoBlockPreviewEventListenersAndObservers(
		{
			documentElement: iframeRef,
			autoScale,
			isPatternPreview,
			contentHeight,
			logoBlockIds,
			query,
		},
		{
			hidePopover,
			selectBlockOnHover,
			selectBlock,
			getBlockParents,
			setBlockEditingMode,
			updatePopoverPosition,
			setLogoBlockIds,
			setContentHeight,
			insertPatternByName,
		}
	);

	return (
		<>
			{ ! isPatternPreview &&
				virtualElement &&
				popoverStatus === PopoverStatus.VISIBLE &&
				! isResizing && (
					<Popover
						// @ts-ignore No types for this exist yet.
						anchor={ virtualElement }
						as="div"
						variant="unstyled"
						className="components-tooltip woocommerce-customize-store_popover-tooltip"
					>
						<span>
							{ __(
								'You can edit your content later in the Editor',
								'woocommerce'
							) }
						</span>
					</Popover>
				) }
			<DisabledProvider value={ true }>
				<div
					className={ clsx( 'block-editor-block-preview__content', {
						'woocommerce-customize-store-assembler':
							! isPatternPreview,
					} ) }
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
						readonly={
							! isFullComposabilityFeatureAndAPIAvailable()
						}
						style={
							autoScale
								? {
										position: 'absolute',
										width: viewportWidth,
										pointerEvents: 'none',
										height: contentHeight,
										// This is a catch-all max-height for patterns.
										// See: https://github.com/WordPress/gutenberg/pull/38175.
										maxHeight: MAX_HEIGHT,
								  }
								: {}
						}
						contentRef={ ( bodyElement: HTMLElement ) => {
							if ( ! bodyElement || iframeRef !== null ) {
								return;
							}

							const documentElement =
								bodyElement.ownerDocument.documentElement;

							setIframeRef( documentElement );
						} }
					>
						<EditorStyles styles={ editorStyles } />
						<style>
							{ `
						.block-editor-block-list__block::before,
						.has-child-selected > .is-selected::after,
						.is-hovered:not(.is-selected.is-hovered)::after,
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

						.components-resizable-box__handle {
							display: none !important;
						}

						footer.is-selected::after,
						header.is-selected::after {
							outline-color: var(--wp-admin-theme-color) !important;
						}

						header.is-selected::after {
						    border-top-left-radius: 20px;
					    }

						footer.is-selected::after {
						    border-bottom-left-radius: 20px;
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
		</>
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
