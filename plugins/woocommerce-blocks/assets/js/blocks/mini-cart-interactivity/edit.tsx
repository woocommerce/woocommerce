/**
 * External dependencies
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import './editor.scss';

export const Edit = (): JSX.Element => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<div className="wc-block-mini-cart-interactivity">
				<p>HELLO WORLD</p>
			</div>
		</div>
	);
};

export const Save = (): JSX.Element => {
	return (
		<div { ...useBlockProps.save() }>
			<InnerBlocks.Content />
		</div>
	);
};
