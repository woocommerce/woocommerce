/**
 * External dependencies
 */
import { __experimentalText } from '@wordpress/components'; // eslint-disable-line @wordpress/no-unsafe-wp-apis

// Preserve permissive prop types of the original JS barrel shim.
export const Text = __experimentalText as any; // eslint-disable-line @typescript-eslint/no-explicit-any
