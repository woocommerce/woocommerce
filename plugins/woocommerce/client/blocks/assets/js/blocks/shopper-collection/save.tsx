/**
 * External dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * The block is rendered server-side. We serialize inner block content so
 * the server can pick up the header (and any future children) via
 * `$content` in the PHP render callback.
 */
const Save = (): JSX.Element => <InnerBlocks.Content />;

export default Save;
