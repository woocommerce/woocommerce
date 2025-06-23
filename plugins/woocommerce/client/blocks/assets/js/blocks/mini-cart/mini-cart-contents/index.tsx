/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import edit, { Save as save } from './edit';
import { blockName, attributes } from './attributes';
import './inner-blocks';
import { isExperimentalMiniCartEnabled } from '../../../settings/blocks';
import { metadata } from './metadata';

const settings = {
	...metadata,
	attributes,
	edit,
	save,
};

if ( isExperimentalMiniCartEnabled() ) {
	settings.save = () => {
		return <InnerBlocks.Content />;
	};
}

registerBlockType( blockName, settings );
