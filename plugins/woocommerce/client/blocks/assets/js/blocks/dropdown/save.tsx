/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

const Save = () => {
	const blockProps = useBlockProps.save( {
		className: 'wc-block-dropdown',
	} );

	return <div { ...blockProps } />;
};

export default Save;
