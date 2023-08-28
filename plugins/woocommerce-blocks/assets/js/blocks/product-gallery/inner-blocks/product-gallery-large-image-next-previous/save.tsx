/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

export const Save = () => {
	return <div { ...useBlockProps.save() }></div>;
};
