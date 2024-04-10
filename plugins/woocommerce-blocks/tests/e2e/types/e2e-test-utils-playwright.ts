/**
 * Missing or not exported types from @wordpress/e2e-test-utils-playwright
 */

// import { type Template} from '@wordpress/e2e-test-utils-playwright/build-types/request-utils/templates
export interface Template {
	wp_id: number;
	id: string;
}
export type ExtendedTemplate = Template & { link: string };
