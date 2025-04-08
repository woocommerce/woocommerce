/**
 * External dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

export default function AttributeNameSave() {
	const blockProps = useBlockProps.save();
	const innerBlocksProps = useInnerBlocksProps.save( {
		...blockProps,
	} );
	// eslint-disable-next-line jsx-a11y/label-has-associated-control
	return <label { ...innerBlocksProps } />;
}
