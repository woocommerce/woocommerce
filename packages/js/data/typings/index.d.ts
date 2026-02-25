/**
 * External dependencies
 */
import '@woocommerce/internal-ts-config/types/wordpress/data';
import '@woocommerce/internal-ts-config/types/wordpress/core-data';
import { Entity } from '@wordpress/core-data';

/**
 * Side-effect imports for type augmentations.
 * These must live in a .d.ts file (not a .ts source file) so they are
 * consumed by the compiler but NOT emitted into downstream declarations.
 */


declare module 'rememo';
