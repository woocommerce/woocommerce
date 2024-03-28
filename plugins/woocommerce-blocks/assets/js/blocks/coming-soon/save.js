/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

export default function Save( { attributes } ) {
	const blockProps = useBlockProps.save();

	return (
		<div { ...blockProps }>
			<div
				style={ { height: '100px' } }
				aria-hidden="true"
				className="wp-block-spacer"
			></div>
			<h1 className="wp-block-heading has-text-align-center">header</h1>
			<div
				style={ { height: '10px' } }
				aria-hidden="true"
				className="wp-block-spacer"
			></div>
			<p className="has-text-align-center">subheader</p>
			<div
				style={ { height: '100px' } }
				aria-hidden="true"
				className="wp-block-spacer"
			></div>
		</div>
	);
}
