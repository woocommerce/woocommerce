/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { isFullComposabilityFeatureAndAPIAvailable } from '../utils/is-full-composability-enabled';

export const DISABLE_CLICK_CLASS = 'disable-click';

export const ENABLE_CLICK_CLASS = 'enable-click';

const setStyle = ( documentElement: HTMLElement ) => {
	const element = documentElement.ownerDocument.documentElement;
	element.classList.add( 'block-editor-block-preview__content-iframe' );
	element.style.position = 'absolute';
	element.style.width = '100%';

	// Necessary for us to prevent the block editor from showing the focus outline on blocks that we've enabled interaction on.
	const styleBlockId = 'enable-click-styles';
	if (
		! documentElement.ownerDocument.head.querySelector(
			`#${ styleBlockId }`
		)
	) {
		const styleBlock =
			documentElement.ownerDocument.createElement( 'style' );
		styleBlock.setAttribute( 'type', 'text/css' );
		styleBlock.setAttribute( 'id', styleBlockId );
		styleBlock.innerHTML = `
			.${ ENABLE_CLICK_CLASS }[data-type="core/button"]:hover {
				cursor: pointer;
			}
			.${ ENABLE_CLICK_CLASS }:focus::after,
			.${ ENABLE_CLICK_CLASS }.is-selected::after {
				content: none !important;
			}
		`;
		documentElement.ownerDocument.head.appendChild( styleBlock );
	}

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
	const observer = new window.MutationObserver( () => {
		// Get the current logo block client ID from DOM and set it in the logo block context. This is used for the logo settings. See: ./sidebar/sidebar-navigation-screen-logo.tsx
		// Ideally, we should be able to get the logo block client ID from the block editor store but it is not available.
		// We should update this code once the there is a selector in the block editor store that can be used to get the logo block client ID.
		const siteLogos = documentElement.querySelectorAll(
			'.wp-block-site-logo'
		);

		const logoBlockIds = Array.from( siteLogos )
			.map( ( siteLogo ) => {
				return siteLogo.getAttribute( 'data-block' );
			} )
			.filter( Boolean ) as string[];
		setLogoBlockIds( logoBlockIds );
	} );

	observer.observe( documentElement, {
		subtree: true,
		childList: true,
	} );

	return observer;
};

const makeInert = ( element: Element ) => {
	element.setAttribute( 'inert', 'true' );
};

const makeInteractive = ( element: Element ) => {
	element.removeAttribute( 'inert' );
};

const addInertToAssemblerPatterns = (
	documentElement: HTMLElement,
	page: string
) => {
	const body = documentElement.ownerDocument.body;

	const interactiveBlocks: Record< string, string > = {
		'/customize-store/assembler-hub/header': `header[data-type='core/template-part']`,
		'/customize-store/assembler-hub/footer': `footer[data-type='core/template-part']`,
		'/customize-store/assembler-hub/homepage': `[data-is-parent-block='true']:not([data-type='core/template-part']):not(.${ DISABLE_CLICK_CLASS })`,
	};

	const pathInteractiveBlocks = page.includes(
		'/customize-store/assembler-hub/homepage'
	)
		? interactiveBlocks[ '/customize-store/assembler-hub/homepage' ]
		: interactiveBlocks[ page ];

	const addInertToTemplateParts = () => {
		for ( const disableClick of documentElement.querySelectorAll(
			`[data-is-parent-block='true']`
		) ) {
			if ( ! disableClick.classList.contains( ENABLE_CLICK_CLASS ) ) {
				makeInert( disableClick );
			}
		}

		for ( const element of documentElement.querySelectorAll(
			pathInteractiveBlocks
		) ) {
			makeInteractive( element );
		}
	};

	addInertToTemplateParts();

	const observerChildList = new window.MutationObserver(
		addInertToTemplateParts
	);

	observerChildList.observe( body, {
		childList: true,
	} );

	return observerChildList;
};

/**
 * Adds an 'inert' attribute to all inner blocks and blocks with the class "disable-click" within the provided document element.
 * The 'inert' attribute makes the blocks non-interactive, preventing them from receiving focus or being clicked.
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
			`[data-is-parent-block='true'] *`
		) ) {
			if ( ! disableClick.classList.contains( ENABLE_CLICK_CLASS ) ) {
				makeInert( disableClick );
			}
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

	const handleOnClick = ( event: MouseEvent ) => {
		const clickedBlockClientId = selectBlockOnHover( event, {
			selectBlockByClientId: selectBlock,
			getBlockParents,
			setBlockEditingMode,
		} );

		updatePopoverPosition( {
			event,
			hoveredBlockClientId: null,
			clickedBlockClientId: clickedBlockClientId as string,
		} );
		( event.target as HTMLElement ).focus();
	};

	const handleMouseMove = ( event: MouseEvent ) => {
		const selectedBlockClientId = selectBlockOnHover( event, {
			selectBlockByClientId: selectBlock,
			getBlockParents,
			setBlockEditingMode: () => void 0,
		} );

		if ( selectedBlockClientId ) {
			updatePopoverPosition( {
				event,
				hoveredBlockClientId: selectedBlockClientId,
				clickedBlockClientId: null,
			} );
		}
	};

	body.addEventListener( 'click', handleOnClick );
	body.addEventListener( 'mousemove', handleMouseMove );

	return () => {
		body.removeEventListener( 'click', handleOnClick );
		body.removeEventListener( 'mousemove', handleMouseMove );
	};
};

export const hidePopoverWhenMouseLeaveIframe = (
	iframeRef: HTMLElement,
	{
		hidePopover,
		selectBlock,
	}: Pick<
		useAutoBlockPreviewEventListenersCallbacks,
		'hidePopover' | 'selectBlock'
	>
) => {
	const handleMouseLeave = ( event: MouseEvent ) => {
		/// Deselect the block if the mouse exits the iframe unless it's moving towards the Block Toolbar.
		if ( event.clientX < 0 || event.clientY < 0 ) {
			selectBlock( '' );
		}
		hidePopover();
	};

	if ( iframeRef ) {
		iframeRef.addEventListener( 'mouseleave', handleMouseLeave );
	}

	return () => {
		if ( iframeRef ) {
			iframeRef.removeEventListener( 'mouseleave', handleMouseLeave );
		}
	};
};

const addPatternButtonClickListener = (
	documentElement: HTMLElement,
	insertPatternByName: ( pattern: string ) => void
) => {
	const DEFAULT_PATTTERN_NAME =
		'woocommerce-blocks/centered-content-with-image-below';
	const handlePatternButtonClick = () => {
		insertPatternByName( DEFAULT_PATTTERN_NAME );
	};

	const patternButton = documentElement.querySelector(
		'.no-blocks-insert-pattern-button'
	);
	if ( patternButton ) {
		patternButton.addEventListener( 'click', handlePatternButtonClick );
	}

	return () => {
		if ( patternButton ) {
			patternButton.removeEventListener(
				'click',
				handlePatternButtonClick
			);
		}
	};
};

type useAutoBlockPreviewEventListenersValues = {
	documentElement: HTMLElement | null;
	autoScale: boolean;
	isPatternPreview: boolean;
	contentHeight: number | null;
	logoBlockIds: string[];
	query: Record< string, string >;
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
		event: MouseEvent;
		hoveredBlockClientId: string | null;
		clickedBlockClientId: string | null;
	} ) => void;
	setLogoBlockIds: ( logoBlockIds: string[] ) => void;
	setContentHeight: ( contentHeight: number | null ) => void;
	hidePopover: () => void;
	insertPatternByName: ( pattern: string ) => void;
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
		logoBlockIds,
		query,
	}: useAutoBlockPreviewEventListenersValues,
	{
		selectBlockOnHover,
		selectBlock,
		getBlockParents,
		setBlockEditingMode,
		updatePopoverPosition,
		setLogoBlockIds,
		setContentHeight,
		hidePopover,
		insertPatternByName,
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

			observers.push( heightObserver );
		}

		setStyle( documentElement );

		const logoObserver = findAndSetLogoBlock(
			{ autoScale, documentElement },
			{
				setLogoBlockIds,
			}
		);

		observers.push( logoObserver );

		if (
			isFullComposabilityFeatureAndAPIAvailable() &&
			! isPatternPreview
		) {
			const removeEventListenerHidePopover =
				hidePopoverWhenMouseLeaveIframe( documentElement, {
					hidePopover,
					selectBlock,
				} );

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

			const inertInnerBlockObserver =
				addInertToAllInnerBlocks( documentElement );
			observers.push( inertInnerBlockObserver );

			const inertAssemblerPatternObserver = addInertToAssemblerPatterns(
				documentElement,
				query?.path
			);
			observers.push( inertAssemblerPatternObserver );

			unsubscribeCallbacks.push( removeEventListenersSelectedBlock );
			unsubscribeCallbacks.push( removeEventListenerHidePopover );
		}

		// Add event listner to the button which will insert a default pattern
		// when there are no patterns inserted in the block preview.
		const removePatternButtonClickListener = addPatternButtonClickListener(
			documentElement,
			insertPatternByName
		);
		unsubscribeCallbacks.push( removePatternButtonClickListener );

		return () => {
			observers.forEach( ( observer ) => observer.disconnect() );
			unsubscribeCallbacks.forEach( ( callback ) => callback() );
		};

		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ documentElement, logoBlockIds, query ] );
};
