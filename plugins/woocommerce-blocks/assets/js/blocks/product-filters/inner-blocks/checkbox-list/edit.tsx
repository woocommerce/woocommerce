/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import './style.scss';
import { EditProps } from './types';

const Edit = ( props: EditProps ) => {
	return <div { ...useBlockProps() }>This is a checkbox list.</div>;
};

export default Edit;
