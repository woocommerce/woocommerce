/**
 * CORE CLASSES
 * These exports relate to extending the core functionality of the package.
 */
export { Adapter } from './adapter';
export { ModelFactory } from './model-factory';
export { Model } from './model';

/**
 * API ADAPTER
 * These exports relate to replacing the underlying HTTP layer of API adapters.
 */
export { APIAdapter } from './api/api-adapter';
export { APIService, APIResponse, APIError } from './api/api-service';
