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
	return <div { ...useBlockProps() }>These are chips.</div>;
};

export default Edit;
