/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

const Save = () => {
	return <div { ...useBlockProps.save() } />;
};

export default Save;
