/**
 * Internal dependencies
 */
import { ResourceState } from '../crud/reducer';
import { IdQuery } from '../crud/types';
import { getRequestIdentifier, getUrlParameters, parseId } from '../crud/utils';
import { WPDataSelector, WPDataSelectors } from '../types';
import { WC_PRODUCT_VARIATIONS_NAMESPACE } from './constants';
import CRUD_ACTIONS from './crud-actions';
import { Selectors } from './types';

export const isGeneratingVariations = (
	state: ResourceState,
	idQuery: IdQuery & { product_id: number | string }
) => {
	const urlParameters = getUrlParameters(
		WC_PRODUCT_VARIATIONS_NAMESPACE,
		idQuery
	);
	const { key } = parseId( idQuery, urlParameters );
	const itemQuery = getRequestIdentifier(
		CRUD_ACTIONS.GENERATE_VARIATIONS,
		key
	);
	return state.requesting[ itemQuery ];
};

export type ProductVariationSelectors = {
	isGeneratingVariations: WPDataSelector< typeof isGeneratingVariations >;
} & WPDataSelectors &
	Selectors;
