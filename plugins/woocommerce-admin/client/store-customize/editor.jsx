// Ref: https://github.com/WordPress/gutenberg/blob/release/16.0/packages/edit-site/src/components/editor/index.js
/**
 * External dependencies
 */
import classnames from 'classnames';
import { useMemo } from '@wordpress/element';
import { EntityProvider } from '@wordpress/core-data';
import { GlobalStylesRenderer } from '@wordpress/edit-site/build-module/components/global-styles-renderer';
import { InterfaceSkeleton } from '@wordpress/interface';
import useEditedEntityRecord from '@wordpress/edit-site/build-module/components/use-edited-entity-record';
import CanvasSpinner from '@wordpress/edit-site/build-module/components/canvas-spinner';
import { unlock } from '@wordpress/edit-site/build-module/private-apis';
import { store as editSiteStore } from '@wordpress/edit-site/build-module/store';
import { useSelect, useDispatch } from '@wordpress/data';
import { BlockContextProvider } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import BlockEditor from './block-editor';

export const Editor = ( { blocks, isLoading } ) => {
	const { record: template } = useEditedEntityRecord();
	const { id: templateId, type: templateType } = template;

	const { context, hasPageContentLock } = useSelect( ( select ) => {
		const {
			getEditedPostContext,
			hasPageContentLock: _hasPageContentLock,
		} = unlock( select( editSiteStore ) );

		// The currently selected entity to display.
		// Typically template or template part in the site editor.
		return {
			context: getEditedPostContext(),
			hasPageContentLock: _hasPageContentLock,
		};
	}, [] );
	const { setEditedPostContext } = useDispatch( editSiteStore );
	const blockContext = useMemo( () => {
		const { postType, postId, ...nonPostFields } = context ?? {};
		return {
			...( hasPageContentLock ? context : nonPostFields ),
			queryContext: [
				context?.queryContext || { page: 1 },
				( newQueryContext ) =>
					setEditedPostContext( {
						...context,
						queryContext: {
							...context?.queryContext,
							...newQueryContext,
						},
					} ),
			],
		};
	}, [ context, hasPageContentLock, setEditedPostContext ] );

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
								'edit-site-editor__interface-skeleton',
								{
									'show-icon-labels': false,
									'is-loading': isLoading,
								}
							) }
							content={
								<>
									<GlobalStylesRenderer />
									<BlockEditor
										blocks={ blocks }
										template={ template }
									/>
								</>
							}
						/>
					</BlockContextProvider>
				</EntityProvider>
			</EntityProvider>
		</>
	);
};
