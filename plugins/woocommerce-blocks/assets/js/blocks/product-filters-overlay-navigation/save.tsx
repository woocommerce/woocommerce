/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import clsx from 'clsx';

export const Save = (): JSX.Element => {
	const blockProps = useBlockProps.save( {
		className: clsx( 'wc-block-' ),
	} );
	return <div { ...blockProps } />;
};
