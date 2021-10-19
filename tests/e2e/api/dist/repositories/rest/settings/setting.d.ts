import { HTTPClient } from '../../../http';
import { ListsSettings, ReadsSettings, UpdatesSettings } from '../../../models';
/**
 * Creates a new ModelRepository instance for interacting with models via the REST API.
 *
 * @param {HTTPClient} httpClient The HTTP client for the REST requests to be made using.
 * @return {ListsSettings|ReadsSettings|UpdatesSettings} The created repository.
 */
export default function settingRESTRepository(httpClient: HTTPClient): ListsSettings & ReadsSettings & UpdatesSettings;
//# sourceMappingURL=setting.d.ts.map