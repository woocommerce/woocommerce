/**
 * External dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';

/**
 * Heading text is stored as inner block content (core/heading), so save
 * serializes that. The count suffix is rendered server-side and live-
 * updated via iAPI; it has no editor representation.
 */
const Save = (): JSX.Element => <InnerBlocks.Content />;

export default Save;
