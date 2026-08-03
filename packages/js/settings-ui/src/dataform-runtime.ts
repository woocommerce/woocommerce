/**
 * The DataViews `/wp` export bundles the version-sensitive DataForm runtime
 * while leaving supported WordPress singleton dependencies external.
 *
 * Keep this facade intentionally narrow. Add exports only when Settings UI
 * consumes them, and never expose DataViews' private APIs from this package.
 */
import type { DataForm as DataFormType } from '@wordpress/dataviews';
import { DataForm as DataFormRuntime } from '@wordpress/dataviews/wp';

// Type the runtime from the resolvable package root so the published declaration
// does not expose the `/wp` subpath to legacy TypeScript module resolution.
export const DataForm: typeof DataFormType = DataFormRuntime;
