/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import './style.scss';

const Edit = () => {
	return <div { ...useBlockProps() }>These are chips.</div>;
};

export default Edit;
