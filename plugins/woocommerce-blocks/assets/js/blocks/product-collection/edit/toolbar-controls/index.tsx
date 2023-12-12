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
import DisplayLayoutToolbar from './display-layout-toolbar';
import PatternChooserToolbar from './coolection-chooser-toolbar';
import type { ProductCollectionEditComponentProps } from '../../types';

export default function ToolbarControls(
	props: ProductCollectionEditComponentProps
) {
	const { attributes, openPatternSelectionModal, setAttributes } = props;
	const { query, displayLayout } = attributes;

	const setQueryAttributeBind = useMemo(
		() => setQueryAttribute.bind( null, props ),
		[ props ]
	);

	return (
		<BlockControls>
			<PatternChooserToolbar
				openPatternSelectionModal={ openPatternSelectionModal }
			/>
			{ ! query.inherit && (
				<>
					<DisplaySettingsToolbar
						query={ query }
						setQueryAttribute={ setQueryAttributeBind }
					/>
					<DisplayLayoutToolbar
						displayLayout={ displayLayout }
						setAttributes={ setAttributes }
					/>
				</>
			) }
		</BlockControls>
	);
}
