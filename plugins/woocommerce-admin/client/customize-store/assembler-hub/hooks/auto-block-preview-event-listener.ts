/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { isFullComposabilityFeatureAndAPIAvailable } from '../utils/is-full-composability-enabled';
import { PopoverStatus } from './use-popover-handler';

const setStyle = ( documentElement: HTMLElement ) => {
	const element = documentElement.ownerDocument.documentElement;
	element.classList.add( 'block-editor-block-preview__content-iframe' );
	element.style.position = 'absolute';
	element.style.width = '100%';

	// Necessary for contentResizeListener to work.
	documentElement.style.boxSizing = 'border-box';
	documentElement.style.position = 'absolute';
	documentElement.style.width = '100%';
};

/**
 * Sets the height of the iframe to the height of the root container
 */
const setContentHeightPatternPreview = (
	documentElement: HTMLElement,
	autoScale: boolean,
	{
		setContentHeight,
	}: Pick< useAutoBlockPreviewEventListenersCallbacks, 'setContentHeight' >
) => {
	const onChange = () => {
		if ( autoScale ) {
			const rootContainer =
				documentElement.ownerDocument.body.querySelector(
					'.is-root-container'
				);
			setContentHeight(
				rootContainer ? rootContainer.clientHeight : null
			);
		}
	};

	const observer = new window.MutationObserver( onChange );
	observer.observe( documentElement, {
		attributes: true,
		characterData: false,
		subtree: true,
		childList: true,
	} );
	return observer;
};

const findAndSetLogoBlock = (
	{
		documentElement,
	}: Pick< useAutoBlockPreviewEventListenersValues, 'autoScale' > & {
		documentElement: HTMLElement;
	},

	{
		setLogoBlockIds,
	}: Pick< useAutoBlockPreviewEventListenersCallbacks, 'setLogoBlockIds' >
) => {
	// Get the current logo block client ID from DOM and set it in the logo block context. This is used for the logo settings. See: ./sidebar/sidebar-navigation-screen-logo.tsx
	// Ideally, we should be able to get the logo block client ID from the block editor store but it is not available.
	// We should update this code once the there is a selector in the block editor store that can be used to get the logo block client ID.
	const siteLogos = documentElement.querySelectorAll( '.wp-block-site-logo' );

	const logoBlockIds = Array.from( siteLogos )
		.map( ( siteLogo ) => {
			return siteLogo.getAttribute( 'data-block' );
		} )
		.filter( Boolean ) as string[];
	setLogoBlockIds( logoBlockIds );
};

/**
 * Adds inert attribute to all inner blocks to prevent them from being focused or clicked.
 */
const addInertToAllInnerBlocks = ( documentElement: HTMLElement ) => {
	const body = documentElement.ownerDocument.body;
	const observerChildList = new window.MutationObserver( () => {
		const parentBlocks = body.getElementsByClassName(
			'block-editor-block-list__layout'
		)[ 0 ].children;

		for ( const parentBlock of parentBlocks ) {
			parentBlock.setAttribute( 'data-is-parent-block', 'true' );
		}

		for ( const disableClick of documentElement.querySelectorAll(
			"[data-is-parent-block='true'] *, header *, footer *"
		) ) {
			disableClick.setAttribute( 'inert', 'true' );
		}
	} );

	observerChildList.observe( body, {
		childList: true,
	} );

	return observerChildList;
};

const updateSelectedBlock = (
	documentElement: HTMLElement,
	{
		selectBlock,
		selectBlockOnHover,
		getBlockParents,
		setBlockEditingMode,
		updatePopoverPosition,
	}: Pick<
		useAutoBlockPreviewEventListenersCallbacks,
		| 'selectBlockOnHover'
		| 'selectBlock'
		| 'getBlockParents'
		| 'setBlockEditingMode'
		| 'updatePopoverPosition'
	>
) => {
	const body = documentElement.ownerDocument.body;

	const onClickListener = ( event: MouseEvent ) => {
		const clickedBlockClientId = selectBlockOnHover( event, {
			selectBlockByClientId: selectBlock,
			getBlockParents,
			setBlockEditingMode,
		} );

		updatePopoverPosition( {
			mainBodyWidth: window.document.body.clientWidth,
			iframeWidth: body.clientWidth,
			event,
			hoveredBlockClientId: null,
			clickedBlockClientId: clickedBlockClientId as string,
		} );
	};

	const onMouseMoveListener = ( event: MouseEvent ) => {
		const selectedBlockClientId = selectBlockOnHover( event, {
			selectBlockByClientId: selectBlock,
			getBlockParents,
			setBlockEditingMode: () => void 0,
		} );

		if ( selectedBlockClientId ) {
			updatePopoverPosition( {
				mainBodyWidth: window.document.body.clientWidth,
				iframeWidth: body.clientWidth,
				event,
				hoveredBlockClientId: selectedBlockClientId,
				clickedBlockClientId: null,
			} );
		}
	};

	body.addEventListener( 'click', onClickListener );
	body.addEventListener( 'mousemove', onMouseMoveListener );

	return () => {
		body.removeEventListener( 'click', onClickListener );
		body.removeEventListener( 'mousemove', onMouseMoveListener );
	};
};

export const hidePopoverWhenMouseLeaveIframe = (
	iframeRef: HTMLElement,
	setPopoverStatus: ( popoverStatus: PopoverStatus ) => void
) => {
	const handleMouseEnter = () => {
		setPopoverStatus( PopoverStatus.VISIBLE );
	};

	const handleMouseLeave = () => {
		setPopoverStatus( PopoverStatus.HIDDEN );
	};

	if ( iframeRef ) {
		iframeRef.addEventListener( 'mouseenter', handleMouseEnter );
		iframeRef.addEventListener( 'mouseleave', handleMouseLeave );
	}

	return () => {
		if ( iframeRef ) {
			iframeRef.removeEventListener( 'mouseenter', handleMouseEnter );
			iframeRef.removeEventListener( 'mouseleave', handleMouseLeave );
		}
	};
};

type useAutoBlockPreviewEventListenersValues = {
	documentElement: HTMLElement | null;
	autoScale: boolean;
	isPatternPreview: boolean;
	contentHeight: number | null;
};

type useAutoBlockPreviewEventListenersCallbacks = {
	selectBlockOnHover: (
		event: MouseEvent,
		options: {
			selectBlockByClientId: (
				clientId: string,
				initialPosition: 0 | -1 | null
			) => void;
			getBlockParents: ( clientId: string ) => string[];
			setBlockEditingMode?: ( clientId: string ) => void;
		}
	) => string | undefined;
	selectBlock: ( clientId: string ) => void;
	getBlockParents: ( clientId: string ) => string[];
	setBlockEditingMode: ( clientId: string ) => void;
	updatePopoverPosition: ( options: {
		mainBodyWidth: number;
		iframeWidth: number;
		event: MouseEvent;
		hoveredBlockClientId: string | null;
		clickedBlockClientId: string | null;
	} ) => void;
	setLogoBlockIds: ( logoBlockIds: string[] ) => void;
	setContentHeight: ( contentHeight: number | null ) => void;
	setPopoverStatus: ( popoverStatus: PopoverStatus ) => void;
};

/**
 * Adds event listeners and observers to the auto block preview iframe.
 *
 */
export const useAddAutoBlockPreviewEventListenersAndObservers = (
	{
		documentElement,
		autoScale,
		isPatternPreview,
	}: useAutoBlockPreviewEventListenersValues,
	{
		selectBlockOnHover,
		selectBlock,
		getBlockParents,
		setBlockEditingMode,
		updatePopoverPosition,
		setLogoBlockIds,
		setContentHeight,
		setPopoverStatus,
	}: useAutoBlockPreviewEventListenersCallbacks
) => {
	useEffect( () => {
		const observers: Array< MutationObserver > = [];
		const unsubscribeCallbacks: Array< () => void > = [];

		if ( ! documentElement ) {
			return;
		}

		// Set the height of the iframe to the height of the root container only when the block preview is used to preview a pattern.
		if ( isPatternPreview ) {
			const heightObserver = setContentHeightPatternPreview(
				documentElement,
				autoScale,
				{
					setContentHeight,
				}
			);
			findAndSetLogoBlock(
				{ autoScale, documentElement },
				{
					setLogoBlockIds,
				}
			);

			observers.push( heightObserver );
		}

		setStyle( documentElement );

		if (
			isFullComposabilityFeatureAndAPIAvailable() &&
			! isPatternPreview
		) {
			const removeEventListenerHidePopover =
				hidePopoverWhenMouseLeaveIframe(
					documentElement,
					setPopoverStatus
				);
			const removeEventListenersSelectedBlock = updateSelectedBlock(
				documentElement,
				{
					selectBlock,
					selectBlockOnHover,
					getBlockParents,
					setBlockEditingMode,
					updatePopoverPosition,
				}
			);
			const inertObserver = addInertToAllInnerBlocks( documentElement );
			observers.push( inertObserver );
			unsubscribeCallbacks.push( removeEventListenersSelectedBlock );
			unsubscribeCallbacks.push( removeEventListenerHidePopover );
		}

		return () => {
			observers.forEach( ( observer ) => observer.disconnect() );
			unsubscribeCallbacks.forEach( ( callback ) => callback() );
		};

		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ documentElement ] );
};
