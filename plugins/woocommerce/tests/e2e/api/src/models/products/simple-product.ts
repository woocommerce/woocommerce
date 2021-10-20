import {
	AbstractProduct,
	IProductCommon,
	IProductCrossSells,
	IProductDelivery,
	IProductInventory,
	IProductPrice,
	IProductSalesTax,
	IProductShipping,
	IProductUpSells,
	ProductSearchParams,
} from './abstract';
import {
	ProductCommonUpdateParams,
	ProductCrossUpdateParams,
	ProductDeliveryUpdateParams,
	ProductInventoryUpdateParams,
	ProductPriceUpdateParams,
	ProductSalesTaxUpdateParams,
	ProductShippingUpdateParams,
	ProductUpSellUpdateParams,
	ProductDownload,
	StockStatus,
	BackorderStatus,
	Taxability,
} from './shared';
import { HTTPClient } from '../../http';
import { simpleProductRESTRepository } from '../../repositories';
import {
	CreatesModels,
	DeletesModels,
	ListsModels,
	ModelRepositoryParams,
	ReadsModels,
	UpdatesModels,
} from '../../framework';

/**
 * The parameters that simple products can update.
 */
type SimpleProductUpdateParams = ProductDeliveryUpdateParams
	& ProductCommonUpdateParams
	& ProductCrossUpdateParams
	& ProductInventoryUpdateParams
	& ProductPriceUpdateParams
	& ProductSalesTaxUpdateParams
	& ProductShippingUpdateParams
	& ProductUpSellUpdateParams;
/**
 * The parameters embedded in this generic can be used in the ModelRepository in order to give
 * type-safety in an incredibly granular way.
 */
export type SimpleProductRepositoryParams = ModelRepositoryParams< SimpleProduct, never, ProductSearchParams, SimpleProductUpdateParams >;

/**
 * An interface for listing simple products using the repository.
 *
 * @typedef ListsSimpleProducts
 * @alias ListsModels.<SimpleProduct>
 */
export type ListsSimpleProducts = ListsModels< SimpleProductRepositoryParams >;

/**
 * An interface for creating simple products using the repository.
 *
 * @typedef CreatesSimpleProducts
 * @alias CreatesModels.<SimpleProduct>
 */
export type CreatesSimpleProducts = CreatesModels< SimpleProductRepositoryParams >;

/**
 * An interface for reading simple products using the repository.
 *
 * @typedef ReadsSimpleProducts
 * @alias ReadsModels.<SimpleProduct>
 */
export type ReadsSimpleProducts = ReadsModels< SimpleProductRepositoryParams >;

/**
 * An interface for updating simple products using the repository.
 *
 * @typedef UpdatesSimpleProducts
 * @alias UpdatesModels.<SimpleProduct>
 */
export type UpdatesSimpleProducts = UpdatesModels< SimpleProductRepositoryParams >;

/**
 * An interface for deleting simple products using the repository.
 *
 * @typedef DeletesSimpleProducts
 * @alias DeletesModels.<SimpleProduct>
 */
export type DeletesSimpleProducts = DeletesModels< SimpleProductRepositoryParams >;

/**
 * The base for the simple product object.
 */
export class SimpleProduct extends AbstractProduct implements
	IProductCommon,
	IProductCrossSells,
	IProductDelivery,
	IProductInventory,
	IProductPrice,
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
	 * @see ./abstracts/delivery.ts
	 */
	public readonly isVirtual: boolean = false;
	public readonly isDownloadable: boolean = false;
	public readonly downloads: readonly ProductDownload[] = [];
	public readonly downloadLimit: number = -1;
	public readonly daysToDownload: number = -1;
	public readonly purchaseNote: string = '';

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
	 * @see ./abstracts/price.ts
	 */
	public readonly price: string = '';
	public readonly priceHtml: string = '';
	public readonly regularPrice: string = '';
	public readonly onSale: boolean = false;
	public readonly salePrice: string = '';
	public readonly saleStart: Date | null = null;
	public readonly saleEnd: Date | null = null;

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
	 * Creates a new simple product instance with the given properties
	 *
	 * @param {Object} properties The properties to set in the object.
	 */
	public constructor( properties?: Partial< SimpleProduct > ) {
		super();
		Object.assign( this, properties );
	}

	/**
	 * Creates a model repository configured for communicating via the REST API.
	 *
	 * @param {HTTPClient} httpClient The client for communicating via HTTP.
	 */
	public static restRepository( httpClient: HTTPClient ): ReturnType< typeof simpleProductRESTRepository > {
		return simpleProductRESTRepository( httpClient );
	}
}
