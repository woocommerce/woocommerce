/**
 * Internal dependencies
 */
import metadata from './block.json';
import Save from './save';
import { getDefaultAttributeId } from './utils';

/**
 * Blocks used to be registered with the store's default attribute as the
 * `attributeId` default, so Gutenberg omitted the ID from the saved markup
 * whenever it matched the default. Pin those blocks to the current default.
 */
const deprecated = [
	{
		apiVersion: metadata.apiVersion,
		attributes: metadata.attributes,
		supports: metadata.supports,
		save: Save,
		isEligible( attributes: { attributeId?: number } ) {
			return ! attributes.attributeId && getDefaultAttributeId() > 0;
		},
		migrate( attributes: Record< string, unknown > ) {
			return { ...attributes, attributeId: getDefaultAttributeId() };
		},
	},
];

export default deprecated;
