/**
 * External dependencies
 */
import { isObject } from '@woocommerce/types/type-guards';

export const isWidgetEditorPage = ( store: unknown ): boolean => {
	if ( isObject( store ) ) {
		const widgetAreas = (
			store as {
				getWidgetAreas: () => string;
			}
		 ).getWidgetAreas();

		return Array.isArray( widgetAreas ) && widgetAreas.length > 0;
	}

	return false;
};
