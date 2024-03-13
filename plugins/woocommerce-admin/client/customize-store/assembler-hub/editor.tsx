// Reference: https://github.com/WordPress/gutenberg/tree/v16.4.0/packages/edit-site/src/components/editor/index.js
/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import classnames from 'classnames';
import { useEffect, useMemo } from '@wordpress/element';
// @ts-ignore No types for this exist yet.
import { InterfaceSkeleton } from '@wordpress/interface';
import { useSelect, useDispatch } from '@wordpress/data';
// @ts-ignore No types for this exist yet.
import { BlockContextProvider } from '@wordpress/block-editor';
// @ts-ignore No types for this exist yet.
import { store as editSiteStore } from '@wordpress/edit-site/build-module/store';
// @ts-ignore No types for this exist yet.
import CanvasSpinner from '@wordpress/edit-site/build-module/components/canvas-spinner';
// @ts-ignore No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
// @ts-ignore No types for this exist yet.
import { GlobalStylesRenderer } from '@wordpress/edit-site/build-module/components/global-styles-renderer';

/**
 * Internal dependencies
 */
import { editorIsLoaded } from '../utils';
import { BlockEditorContainer } from './block-editor-container';

export const Editor = ( { isLoading }: { isLoading: boolean } ) => {
	const { context, hasPageContentFocus } = useSelect( ( select ) => {
		const {
			getEditedPostContext,
			hasPageContentFocus: _hasPageContentFocus,
		} = unlock( select( editSiteStore ) );

		// The currently selected entity to display.
		// Typically template or template part in the site editor.
		return {
			context: getEditedPostContext(),
			hasPageContentFocus: _hasPageContentFocus,
		};
	}, [] );
	// @ts-ignore No types for this exist yet.
	const { setEditedPostContext } = useDispatch( editSiteStore );
	const blockContext = useMemo( () => {
		const { postType, postId, ...nonPostFields } = context ?? {};
		return {
			...( hasPageContentFocus ? context : nonPostFields ),
			queryContext: [
				context?.queryContext || { page: 1 },
				( newQueryContext: Record< string, unknown > ) =>
					setEditedPostContext( {
						...context,
						queryContext: {
							...context?.queryContext,
							...newQueryContext,
						},
					} ),
			],
		};
	}, [ hasPageContentFocus, context, setEditedPostContext ] );

	useEffect( () => {
		if ( ! isLoading ) {
			editorIsLoaded();
		}
	}, [ isLoading ] );

	return (
		<>
			{ isLoading ? <CanvasSpinner /> : null }

			<BlockContextProvider value={ blockContext }>
				<InterfaceSkeleton
					enableRegionNavigation={ false }
					className={ classnames(
						'woocommerce-customize-store__edit-site-editor',
						'edit-site-editor__interface-skeleton',
						{
							'show-icon-labels': false,
							'is-loading': isLoading,
						}
					) }
					content={
						<>
							<GlobalStylesRenderer />
							<BlockEditorContainer />
						</>
					}
				/>
			</BlockContextProvider>
		</>
	);
};
