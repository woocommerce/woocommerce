/**
 * External dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

export default function NoResultsSave() {
	// @ts-expect-error: `InnerBlocks.Content` is a component that is typed in WordPress core
	return <InnerBlocks.Content />;
}
