import {
	AbstractProduct,
	IProductCommon,
	IProductCrossSells,
	IProductInventory,
	IProductSalesTax,
	IProductShipping,
	IProductUpSells,
	ProductSearchParams,
} from './abstract';
import {
	ProductInventoryUpdateParams,
	ProductCommonUpdateParams,
	ProductDefaultAttribute,
	ProductSalesTaxUpdateParams,
	ProductCrossUpdateParams,
	ProductShippingUpdateParams,
	ProductUpSellUpdateParams,
	ProductVariableUpdateParams,
	StockStatus,
	BackorderStatus,
	Taxability,
} from './shared';
import { HTTPClient } from '../../http';
import { variableProductRESTRepository } from '../../repositories';
import {
	CreatesModels,
	DeletesModels,
	ListsModels,
	ModelRepositoryParams,
	ReadsModels,
	UpdatesModels,
} from '../../framework';

/**
 * The parameters that variable products can update.
 */
type VariableProductUpdateParams = ProductVariableUpdateParams
	& ProductCommonUpdateParams
	& ProductCrossUpdateParams
	& ProductInventoryUpdateParams
	& ProductSalesTaxUpdateParams
	& ProductShippingUpdateParams
	& ProductUpSellUpdateParams;
/**
 * The parameters embedded in this generic can be used in the ModelRepository in order to give
 * type-safety in an incredibly granular way.
 */
export type VariableProductRepositoryParams =
	ModelRepositoryParams< VariableProduct, never, ProductSearchParams, VariableProductUpdateParams >;

/**
 * An interface for listing variable products using the repository.
 *
 * @typedef ListsVariableProducts
 * @alias ListsModels.<VariableProduct>
 */
export type ListsVariableProducts = ListsModels< VariableProductRepositoryParams >;

/**
 * An interface for creating variable products using the repository.
 *
 * @typedef CreatesVariableProducts
 * @alias CreatesModels.<VariableProduct>
 */
export type CreatesVariableProducts = CreatesModels< VariableProductRepositoryParams >;

/**
 * An interface for reading variable products using the repository.
 *
 * @typedef ReadsVariableProducts
 * @alias ReadsModels.<VariableProduct>
 */
export type ReadsVariableProducts = ReadsModels< VariableProductRepositoryParams >;

/**
 * An interface for updating variable products using the repository.
 *
 * @typedef UpdatesVariableProducts
 * @alias UpdatesModels.<VariableProduct>
 */
export type UpdatesVariableProducts = UpdatesModels< VariableProductRepositoryParams >;

/**
 * An interface for deleting variable products using the repository.
 *
 * @typedef DeletesVariableProducts
 * @alias DeletesModels.<VariableProduct>
 */
export type DeletesVariableProducts = DeletesModels< VariableProductRepositoryParams >;

/**
 * The base for the Variable product object.
 */
export class VariableProduct extends AbstractProduct implements
	IProductCommon,
	IProductCrossSells,
	IProductInventory,
	IProductSalesTax,
	IProductShipping,
	IProductUpSells {
	/**
	 * @see ./abstracts/cross-sells.ts
	 */
	public readonly crossSellIds: Array<number> = [];

	/**
	 * @see ./abstracts/upsell.ts
	 */
	public readonly upSellIds: Array<number> = [];

	/**
	 * @see ./abstracts/inventory.ts
	 */
	public readonly onePerOrder: boolean = false;
	public readonly trackInventory: boolean = false;
	public readonly remainingStock: number = -1;
	public readonly stockStatus: StockStatus = ''
	public readonly backorderStatus: BackorderStatus = BackorderStatus.Allowed;
	public readonly canBackorder: boolean = false;
	public readonly isOnBackorder: boolean = false;

	/**
	 * @see ./abstracts/sales-tax.ts
	 */
	public readonly taxStatus: Taxability = Taxability.ProductAndShipping;
	public readonly taxClass: string = '';

	/**
	 * @see ./abstracts/shipping.ts
	 */
	public readonly weight: string = '';
	public readonly length: string = '';
	public readonly width: string = '';
	public readonly height: string = '';
	public readonly requiresShipping: boolean = false;
	public readonly isShippingTaxable: boolean = false;
	public readonly shippingClass: string = '';
	public readonly shippingClassId: number = 0;

	/**
	 * Default product attributes.
	 *
	 * @type {ReadonlyArray.<ProductDefaultAttribute>}
	 */
	public readonly defaultAttributes: readonly ProductDefaultAttribute[] = [];

	/**
	 * Product variations.
	 *
	 * @type {ReadonlyArray.<number>}
	 */
	public readonly variations: Array<number> = [];

	/**
	 * Creates a new Variable product instance with the given properties
	 *
	 * @param {Object} properties The properties to set in the object.
	 */
	public constructor( properties?: Partial< VariableProduct > ) {
		super();
		Object.assign( this, properties );
	}

	/**
	 * Creates a model repository configured for communicating via the REST API.
	 *
	 * @param {HTTPClient} httpClient The client for communicating via HTTP.
	 */
	public static restRepository( httpClient: HTTPClient ): ReturnType< typeof variableProductRESTRepository > {
		return variableProductRESTRepository( httpClient );
	}
}
