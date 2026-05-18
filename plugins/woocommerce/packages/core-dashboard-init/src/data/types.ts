/**
 * Minimal shape accepted by `@wordpress/core-data`'s `addEntities()` action.
 * Mirrors the contract that powers `getEntityRecords( kind, name, query )`
 * — the consumer side of the wp-data + REST integration.
 */
export interface Entity {
	/** Entity identifier within a kind (e.g. `'order'`). */
	name: string;
	/** Namespace grouping related entities (e.g. `'woocommerce'`). */
	kind: string;
	/** REST base URL (e.g. `'/wc/v3/orders'`). */
	baseURL: string;
	/** Field name used as the primary key on returned records. */
	key: string;
	/** Human label, singular. */
	label: string;
	/** Human label, plural. */
	plural: string;
	/** Whether the endpoint supports `per_page` / `page` pagination params. */
	supportsPagination: boolean;
}

/**
 * Partial of the `/wc/v3/orders` response shape — only the fields the
 * Store Activity widget consumes today.
 */
export type OrderRecord = {
	id: number;
	number: string;
	status: string;
	date_created_gmt?: string;
	date_created?: string;
	customer_id?: number;
	billing?: {
		first_name?: string;
		last_name?: string;
	};
};
