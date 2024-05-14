/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { BlockControls } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { setQueryAttribute } from '../../utils';
import DisplaySettingsToolbar from './display-settings-toolbar';
import TemplateLayoutToolbar from './template-layout-toolbar';
import CollectionChooserToolbar from './collection-chooser-toolbar';
import type { ProductCollectionEditComponentProps } from '../../types';

export default function ToolbarControls(
	props: ProductCollectionEditComponentProps
) {
	const { attributes, openCollectionSelectionModal, setAttributes } = props;
	const { query, templateLayout } = attributes;

	const setQueryAttributeBind = useMemo(
		() => setQueryAttribute.bind( null, props ),
		[ props ]
	);

	return (
		<BlockControls>
			<CollectionChooserToolbar
				openCollectionSelectionModal={ openCollectionSelectionModal }
			/>
			{ ! query.inherit && (
				<>
					<DisplaySettingsToolbar
						query={ query }
						setQueryAttribute={ setQueryAttributeBind }
					/>
					<TemplateLayoutToolbar
						templateLayout={ templateLayout }
						setAttributes={ setAttributes }
					/>
				</>
			) }
		</BlockControls>
	);
}
