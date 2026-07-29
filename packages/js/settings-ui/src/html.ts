/**
 * External dependencies
 */
import { sanitizeHTML } from '@woocommerce/sanitize';

export const sanitizeSettingsHtml = ( html?: string ) => sanitizeHTML( html );
