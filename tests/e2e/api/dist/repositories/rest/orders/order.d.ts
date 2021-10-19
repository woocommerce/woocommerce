import { HTTPClient } from '../../../http';
import { ListsOrders, ReadsOrders, UpdatesOrders, CreatesOrders, DeletesOrders } from '../../../models';
/**
 *
 * @param {HTTPClient} httpClient The HTTP client for the REST requests to be made using.
 */
export default function orderRESTRepository(httpClient: HTTPClient): CreatesOrders & ListsOrders & ReadsOrders & UpdatesOrders & DeletesOrders;
//# sourceMappingURL=order.d.ts.map