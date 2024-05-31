/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { isFullComposabilityFeatureAndAPIAvailable } from './is-full-composability-enabled';

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
	body.addEventListener( 'click', ( event ) => {
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
	} );

	body.addEventListener(
		'mousemove',
		( event ) => {
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
		},
		true
	);
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
};

export const useAutoBlockPreviewEventListener = (
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
	}: useAutoBlockPreviewEventListenersCallbacks
) => {
	useEffect( () => {
		const observers: Array< MutationObserver > = [];

		if ( ! documentElement ) {
			return;
		}

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

		if ( isFullComposabilityFeatureAndAPIAvailable() ) {
			updateSelectedBlock( documentElement, {
				selectBlock,
				selectBlockOnHover,
				getBlockParents,
				setBlockEditingMode,
				updatePopoverPosition,
			} );
			const inertObserver = addInertToAllInnerBlocks( documentElement );
			observers.push( inertObserver );
		} else {
		}

		return () => {
			observers.forEach( ( observer ) => observer.disconnect() );
		};

		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ documentElement ] );
};
