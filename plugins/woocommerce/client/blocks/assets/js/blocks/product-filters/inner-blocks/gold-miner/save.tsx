/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

const Save = () => {
	const blockProps = useBlockProps.save( {
		className: 'wc-block-product-filter-gold-miner',
	} );

	return <div { ...blockProps } />;
};

export default Save;
