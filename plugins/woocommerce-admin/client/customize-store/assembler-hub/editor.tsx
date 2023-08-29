// Reference: https://github.com/WordPress/gutenberg/tree/v16.4.0/packages/edit-site/src/components/editor/index.js
/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import classnames from 'classnames';
import { useMemo } from '@wordpress/element';
// @ts-ignore No types for this exist yet.
import { EntityProvider } from '@wordpress/core-data';
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
import useEditedEntityRecord from '@wordpress/edit-site/build-module/components/use-edited-entity-record';
// @ts-ignore No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
// @ts-ignore No types for this exist yet.
import { GlobalStylesRenderer } from '@wordpress/edit-site/build-module/components/global-styles-renderer';

/**
 * Internal dependencies
 */
import { BlockEditor } from './block-editor';

export const Editor = ( { isLoading }: { isLoading: boolean } ) => {
	const { record: template } = useEditedEntityRecord();
	const { id: templateId, type: templateType } = template;
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

	return (
		<>
			{ isLoading ? <CanvasSpinner /> : null }
			<EntityProvider kind="root" type="site">
				<EntityProvider
					kind="postType"
					type={ templateType }
					id={ templateId }
				>
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
									<BlockEditor />
								</>
							}
						/>
					</BlockContextProvider>
				</EntityProvider>
			</EntityProvider>
		</>
	);
};
