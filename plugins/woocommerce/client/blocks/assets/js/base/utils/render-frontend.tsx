/**
 * External dependencies
 */
import { createRoot, useEffect, Suspense } from '@wordpress/element';
import BlockErrorBoundary from '@woocommerce/base-components/block-error-boundary';
import type { Root } from 'react-dom/client';

// Some blocks take care of rendering their inner blocks automatically. For
// example, the empty cart. In those cases, we don't want to trigger the render
// function of inner components on load. Instead, the wrapper block can trigger
// the event `wc-blocks_render_blocks_frontend` to render its inner blocks.
const selectorsToSkipOnLoad = [ '.wp-block-woocommerce-cart' ];

type BlockProps<
	TProps extends Record< string, unknown >,
	TAttribute extends Record< string, unknown >
> = TProps & {
	attributes?: TAttribute;
};

type BlockType<
	TProps extends Record< string, unknown >,
	TAttribute extends Record< string, unknown >
> = ( props: BlockProps< TProps, TAttribute > ) => JSX.Element | null;

export type GetPropsFn<
	TProps extends Record< string, unknown >,
	TAttributes extends Record< string, unknown >
> = ( el: HTMLElement, i: number ) => BlockProps< TProps, TAttributes >;

export type ReactRootWithContainer = {
	container: HTMLElement;
	root: Root;
};

interface RenderBlockParams<
	TProps extends Record< string, unknown >,
	TAttributes extends Record< string, unknown >
> {
	// React component to use as a replacement.
	Block: BlockType< TProps, TAttributes > | null;
	// Container to replace with the Block component.
	container: HTMLElement;
	// Attributes object for the block.
	attributes: TAttributes;
	// Props object for the block.
	props: BlockProps< TProps, TAttributes >;
	// Props object for the error boundary.
	errorBoundaryProps?: Record< string, unknown >;
}

/**
 * Renders a block component in a single `container` node.
 */
export const renderBlock = <
	TProps extends Record< string, unknown >,
	TAttributes extends Record< string, unknown >
>( {
	Block,
	container,
	attributes = {} as TAttributes,
	props = {} as BlockProps< TProps, TAttributes >,
	errorBoundaryProps = {},
}: RenderBlockParams< TProps, TAttributes > ): Root => {
	const BlockWrapper = () => {
		useEffect( () => {
			if ( container.classList ) {
				container.classList.remove( 'is-loading' );
			}
		}, [] );

		const isCheckoutBlock = container.classList.contains(
			'wp-block-woocommerce-checkout'
		);
		const isCartBlock = container.classList.contains(
			'wp-block-woocommerce-cart'
		);

		// These blocks have progressive loading enabled
		if ( isCheckoutBlock || isCartBlock ) {
			return (
				<BlockErrorBoundary { ...errorBoundaryProps }>
					<Block { ...props } attributes={ attributes } />
				</BlockErrorBoundary>
			);
		}

		// For all other blocks, use Suspense
		return (
			<BlockErrorBoundary { ...errorBoundaryProps }>
				<Suspense
					fallback={
						<div className="wc-block-placeholder">Loading...</div>
					}
				>
					{ Block && (
						<Block { ...props } attributes={ attributes } />
					) }
				</Suspense>
			</BlockErrorBoundary>
		);
	};

	const root = createRoot( container );
	root.render( <BlockWrapper /> );
	return root;
};

interface RenderBlockInContainersParams<
	TProps extends Record< string, unknown >,
	TAttributes extends Record< string, unknown >
> {
	// React component to use as a replacement.
	Block: BlockType< TProps, TAttributes > | null;
	// Containers to replace with the Block component.
	containers: HTMLElement[];
	// Function to generate the props object for the block.
	getProps?: GetPropsFn< TProps, TAttributes >;
	// Function to generate the props object for the error boundary.
	getErrorBoundaryProps?: (
		el: HTMLElement,
		i: number
	) => Record< string, unknown >;
}

/**
 * Renders a block component in each `containers` node.
 */
const renderBlockInContainers = <
	TProps extends Record< string, unknown >,
	TAttributes extends Record< string, unknown >
>( {
	Block,
	containers,
	getProps = () => ( {} as BlockProps< TProps, TAttributes > ),
	getErrorBoundaryProps = () => ( {} ),
}: RenderBlockInContainersParams<
	TProps,
	TAttributes
> ): ReactRootWithContainer[] => {
	if ( containers.length === 0 ) {
		return [];
	}
	const roots: ReactRootWithContainer[] = [];

	containers.forEach( ( el, i ) => {
		const props = getProps( el, i );

		const errorBoundaryProps = getErrorBoundaryProps( el, i );
		const attributes = {
			...el.dataset,
			...( props.attributes || ( {} as TAttributes ) ),
		};

		roots.push( {
			container: el,
			root: renderBlock( {
				Block,
				container: el,
				props,
				attributes,
				errorBoundaryProps,
			} ),
		} );
	} );

	return roots;
};

// Given an element and a list of wrappers, check if the element is inside at
// least one of the wrappers.
const isElementInsideWrappers = (
	el: HTMLElement,
	wrappers: HTMLElement[]
) => {
	return wrappers.some(
		( wrapper ) => wrapper.contains( el ) && ! wrapper.isSameNode( el )
	);
};

interface RenderBlockOutsideWrappersParams<
	TProps extends Record< string, unknown >,
	TAttributes extends Record< string, unknown >
> extends RenderFrontendParams< TProps, TAttributes > {
	// All elements matched by the selector which are inside the wrapper will be ignored.
	wrappers?: HTMLElement[];
}

/**
 * Renders the block frontend in the elements matched by the selector which are
 * outside the wrapper elements.
 */
const renderBlockOutsideWrappers = <
	TProps extends Record< string, unknown >,
	TAttributes extends Record< string, unknown >
>( {
	Block,
	getProps,
	getErrorBoundaryProps,
	selector,
	wrappers,
	options,
}: RenderBlockOutsideWrappersParams<
	TProps,
	TAttributes
> ): ReactRootWithContainer[] => {
	let containers: HTMLElement[] = Array.from(
		document.body.querySelectorAll( selector )
	);

	// Filter out blocks inside the wrappers.
	if ( wrappers && wrappers.length > 0 ) {
		containers = containers.filter( ( el ) => {
			return ! isElementInsideWrappers( el, wrappers );
		} );
	}

	// Limit to first element if multiple option is false
	if ( options?.multiple === false ) {
		containers = containers.slice( 0, 1 );
	}

	return renderBlockInContainers( {
		Block,
		containers,
		getProps,
		getErrorBoundaryProps,
	} );
};

interface RenderBlockInsideWrapperParams<
	TProps extends Record< string, unknown >,
	TAttributes extends Record< string, unknown >
> extends RenderFrontendParams< TProps, TAttributes > {
	// Wrapper element to query the selector inside.
	wrapper: HTMLElement;
}

/**
 * Renders the block frontend in the elements matched by the selector inside the
 * wrapper element.
 */
const renderBlockInsideWrapper = <
	TProps extends Record< string, unknown >,
	TAttributes extends Record< string, unknown >
>( {
	Block,
	getProps,
	getErrorBoundaryProps,
	selector,
	wrapper,
	options,
}: RenderBlockInsideWrapperParams< TProps, TAttributes > ): void => {
	let containers: HTMLElement[] = Array.from(
		wrapper.querySelectorAll( selector )
	);

	// Limit to first element if multiple option is false
	if ( options?.multiple === false ) {
		containers = containers.slice( 0, 1 );
	}

	renderBlockInContainers( {
		Block,
		containers,
		getProps,
		getErrorBoundaryProps,
	} );
};

export interface RenderFrontendOptions {
	// Whether to match multiple elements or just the first one found
	multiple: boolean;
}

interface RenderFrontendParams<
	TProps extends Record< string, unknown >,
	TAttributes extends Record< string, unknown >
> {
	// React component to use as a replacement.
	Block: BlockType< TProps, TAttributes > | null;
	// CSS selector to match the elements to replace.
	selector: string;
	// Function to generate the props object for the block.
	getProps?: GetPropsFn< TProps, TAttributes >;
	// Function to generate the props object for the error boundary.
	getErrorBoundaryProps?: (
		el: HTMLElement,
		i: number
	) => Record< string, unknown >;
	// Options to control rendering behavior
	options?: RenderFrontendOptions;
}

/**
 * Renders the block frontend on page load. If the block is contained inside a
 * wrapper element that should be excluded from initial load, it adds the
 * appropriate event listeners to render the block when the
 * `wc-blocks_render_blocks_frontend` event is triggered.
 */
export const renderFrontend = <
	TProps extends Record< string, unknown >,
	TAttributes extends Record< string, unknown >
>(
	props:
		| RenderBlockOutsideWrappersParams< TProps, TAttributes >
		| RenderBlockInsideWrapperParams< TProps, TAttributes >
): ReactRootWithContainer[] => {
	const wrappersToSkipOnLoad: HTMLElement[] = Array.from(
		document.body.querySelectorAll( selectorsToSkipOnLoad.join( ',' ) )
	);

	const {
		Block,
		getProps,
		getErrorBoundaryProps,
		selector,
		options = { multiple: true },
	} = props;

	const roots = renderBlockOutsideWrappers( {
		Block,
		getProps,
		getErrorBoundaryProps,
		selector,
		options,
		wrappers: wrappersToSkipOnLoad,
	} );

	// For each wrapper, add an event listener to render the inner blocks when
	// `wc-blocks_render_blocks_frontend` event is triggered.
	wrappersToSkipOnLoad.forEach( ( wrapper ) => {
		wrapper.addEventListener( 'wc-blocks_render_blocks_frontend', () => {
			renderBlockInsideWrapper( { ...props, wrapper } );
		} );
	} );

	return roots;
};

export default renderFrontend;
