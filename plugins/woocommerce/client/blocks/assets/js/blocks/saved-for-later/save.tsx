/**
 * External dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * The block is rendered server-side, but its inner blocks must be serialized
 * to `post_content` so user edits persist. `<InnerBlocks.Content />` emits
 * only the inner blocks' static markup.
 */
const Save = (): JSX.Element => <InnerBlocks.Content />;

export default Save;
