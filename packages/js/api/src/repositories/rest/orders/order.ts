import { HTTPClient } from '../../../http';
import {
	ModelRepository,
} from '../../../framework';
import {
	ModelID,
	Order,
	OrderRepositoryParams,
	ListsOrders,
	ReadsOrders,
	UpdatesOrders,
	CreatesOrders,
	DeletesOrders,
} from '../../../models';

import {
	restList,
	restCreate,
	restRead,
	restUpdate,
	restDelete,
} from '../shared';

import { createOrderTransformer } from './transformer';
/**
 *
 * @param {HTTPClient} httpClient The HTTP client for the REST requests to be made using.
 */
export default function orderRESTRepository( httpClient: HTTPClient ): CreatesOrders
& ListsOrders
& ReadsOrders
& UpdatesOrders
& DeletesOrders {
	const buildURL = ( id: ModelID ) => '/wc/v3/orders/' + id;
	// Using `?force=true` permanently deletes the order
	const buildDeleteUrl = ( id: ModelID ) => `/wc/v3/orders/${ id }?force=true`;

	const transformer = createOrderTransformer();

	return new ModelRepository(
		restList< OrderRepositoryParams >( () => '/wc/v3/orders', Order, httpClient, transformer ),
		restCreate< OrderRepositoryParams >( () => '/wc/v3/orders', Order, httpClient, transformer ),
		restRead< OrderRepositoryParams >( buildURL, Order, httpClient, transformer ),
		restUpdate< OrderRepositoryParams >( buildURL, Order, httpClient, transformer ),
		restDelete< OrderRepositoryParams >( buildDeleteUrl, httpClient ),
	);
}
