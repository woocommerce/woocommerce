/**
 * External dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * The block is rendered server-side, but the inner blocks (default
 * `core/heading`) must be serialized to `post_content` so user edits
 * persist — returning `null` would drop them. `<InnerBlocks.Content />`
 * emits only the inner blocks' static markup.
 */
const Save = (): JSX.Element => <InnerBlocks.Content />;

export default Save;
