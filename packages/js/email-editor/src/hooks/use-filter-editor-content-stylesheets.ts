/**
 * External dependencies
 */
import {
	useCallback,
	useEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import { Block } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { getAllowedBlocks } from '../blocks';

export const useFilterEditorContentStylesheets = () => {
	const contentRef = useRef( null );
	const [ , forceUpdate ] = useState( 0 );

	const handleRefChange = useCallback(
		( ref: Element ) => {
			contentRef.current = ref;
			forceUpdate( ( i ) => ++i );
		},
		[ contentRef, forceUpdate ]
	);

	const allowedBlocks = getAllowedBlocks();
	const allowedEmailBlocksStylesheetHandles = useMemo( () => {
		return allowedBlocks.reduce( ( acc: string[], block: Block ) => {
			// @ts-expect-error: 'email' is a custom property
			if ( ! block?.supports?.email ) {
				return acc;
			}
			const handleBase = ( block?.name ?? '' ).replace( '/', '-' );

			return acc.concat(
				`${ handleBase }-style-css`,
				`${ handleBase }-editor-style-css`
			);
		}, [] );
	}, [ allowedBlocks ] );

	useEffect( () => {
		if ( ! contentRef.current ) {
			return;
		}

		const { ownerDocument } = contentRef.current;
		const stylesheets = Array.from( document.styleSheets );
		const stylesheetIds = stylesheets
			.filter( ( stylesheet ) => {
				const stylesheetId = (
					stylesheet?.ownerNode as Element
				 )?.getAttribute( 'id' );

				const shouldRemove =
					stylesheetId &&
					// We assume that all stylesheets with a 'wp-' prefix are part of the core and should not be removed.
					! stylesheetId.startsWith( 'wp-' ) &&
					// Blocks with email support should not be removed.
					! allowedEmailBlocksStylesheetHandles.includes(
						stylesheetId
					);

				return applyFilters(
					'woocommerce_email_editor_iframe_stylesheet_should_remove',
					shouldRemove,
					stylesheet
				);
			} )
			.map( ( stylesheet ) =>
				( stylesheet?.ownerNode as Element )?.getAttribute( 'id' )
			);

		stylesheetIds.forEach( ( id ) => {
			const existingStyle = ownerDocument.getElementById( id );

			if ( existingStyle ) {
				existingStyle.remove();
			}

			// Create a placeholder style element to ensure the stylesheet will not be cloned over to the iframe by Gutenberg's style compatibility feature.
			// See https://github.com/WordPress/gutenberg/blob/48ccf3317ef0f18f8ff38e8da748aa62ca3f11cb/packages/block-editor/src/components/iframe/index.js#L184-L186.
			const stylePlaceholder = ownerDocument.createElement( 'stlye' );
			stylePlaceholder.id = id;
			ownerDocument.head.appendChild( stylePlaceholder );
		} );
	}, [ allowedEmailBlocksStylesheetHandles ] );

	return handleRefChange;
};
