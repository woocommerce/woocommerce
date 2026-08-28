/**
 * The DataViews `/wp` export bundles the version-sensitive DataForm runtime
 * while leaving supported WordPress singleton dependencies external.
 *
 * This module is a private Settings UI runtime entry. Do not re-export it from
 * the package index. WOOPRD-3596 can import it directly when the renderer starts
 * to use DataForm.
 */
import type { DataForm as DataFormType } from '@wordpress/dataviews';
import { DataForm as DataFormRuntime } from '@wordpress/dataviews/wp';

// Type the runtime from the resolvable package root so the published declaration
// does not expose the `/wp` subpath to legacy TypeScript module resolution.
export const DataForm: typeof DataFormType = DataFormRuntime;
