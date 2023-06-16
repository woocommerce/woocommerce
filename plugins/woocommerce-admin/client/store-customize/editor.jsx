// Ref: https://github.com/WordPress/gutenberg/blob/trunk/packages/edit-site/src/components/editor/index.js

/**
 * External dependencies
 */
import classnames from 'classnames';
import { EntityProvider } from '@wordpress/core-data';
import { GlobalStylesRenderer } from '@wordpress/edit-site/build-module/components/global-styles-renderer';
import { InterfaceSkeleton } from '@wordpress/interface';
import useEditedEntityRecord from '@wordpress/edit-site/build-module/components/use-edited-entity-record';

/**
 * Internal dependencies
 */
import BlockEditor from './block-editor';

export const Editor = ( { blocks } ) => {
	const { record: template } = useEditedEntityRecord();
	const { id: templateId, type: templateType } = template;

	return (
		<EntityProvider kind="root" type="site">
			<EntityProvider
				kind="postType"
				type={ templateType }
				id={ templateId }
			>
				<InterfaceSkeleton
					enableRegionNavigation={ false }
					className={ classnames(
						'edit-site-editor__interface-skeleton',
						{
							'show-icon-labels': false,
							'is-loading': false,
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
			</EntityProvider>
		</EntityProvider>
	);
};
