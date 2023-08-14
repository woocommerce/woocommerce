/**
 * Internal dependencies
 */
import { ResourceState } from '../crud/reducer';
import { IdQuery } from '../crud/types';
import { getRequestIdentifier, getUrlParameters, parseId } from '../crud/utils';
import { WC_PRODUCT_VARIATIONS_NAMESPACE } from './constants';
import CRUD_ACTIONS from './crud-actions';

export const isGeneratingVariations = (
	state: ResourceState,
	idQuery: IdQuery
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
