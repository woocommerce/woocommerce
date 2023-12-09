/**
 * External dependencies
 */
import type { BlockEditProps } from '@wordpress/blocks';
import { useMemo, useState } from '@wordpress/element';
import { BlockControls } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { setQueryAttribute } from '../../utils';
import { ProductCollectionAttributes } from '../../types';
import DisplaySettingsToolbar from './display-settings-toolbar';
import DisplayLayoutToolbar from './display-layout-toolbar';
import PatternChooserToolbar from './pattern-chooser-toolbar';
import PatternSelectionModal from './pattern-selection-modal';

export default function ToolbarControls(
	props: BlockEditProps< ProductCollectionAttributes >
) {
	const [ isPatternSelectionModalOpen, setIsPatternSelectionModalOpen ] =
		useState( false );

	const { attributes, clientId, setAttributes } = props;
	const { query, displayLayout } = attributes;

	const setQueryAttributeBind = useMemo(
		() => setQueryAttribute.bind( null, props ),
		[ props ]
	);

	return (
		<BlockControls>
			<PatternChooserToolbar
				openPatternSelectionModal={ () =>
					setIsPatternSelectionModalOpen( true )
				}
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
			{ isPatternSelectionModalOpen && (
				<PatternSelectionModal
					clientId={ clientId }
					query={ query }
					closePatternSelectionModal={ () =>
						setIsPatternSelectionModalOpen( false )
					}
				/>
			) }
		</BlockControls>
	);
}
