import {
	AbstractProduct,
	IProductCommon,
	IProductExternal,
	IProductPrice,
	IProductSalesTax,
	IProductUpSells,
	ProductSearchParams,
} from './abstract';
import {
	ProductCommonUpdateParams,
	ProductExternalUpdateParams,
	ProductPriceUpdateParams,
	ProductSalesTaxUpdateParams,
	ProductUpSellUpdateParams,
	Taxability,
} from './shared';
import { HTTPClient } from '../../http';
import { externalProductRESTRepository } from '../../repositories';
import {
	CreatesModels,
	DeletesModels,
	ListsModels,
	ModelRepositoryParams,
	ReadsModels,
	UpdatesModels,
} from '../../framework';

/**
 * The parameters that external products can update.
 */
type ExternalProductUpdateParams = ProductCommonUpdateParams
	& ProductExternalUpdateParams
	& ProductPriceUpdateParams
	& ProductSalesTaxUpdateParams
	& ProductUpSellUpdateParams;
/**
 * The parameters embedded in this generic can be used in the ModelRepository in order to give
 * type-safety in an incredibly granular way.
 */
export type ExternalProductRepositoryParams =
	ModelRepositoryParams< ExternalProduct, never, ProductSearchParams, ExternalProductUpdateParams >;

/**
 * An interface for listing external products using the repository.
 *
 * @typedef ListsExternalProducts
 * @alias ListsModels.<ExternalProduct>
 */
export type ListsExternalProducts = ListsModels< ExternalProductRepositoryParams >;

/**
 * An interface for external simple products using the repository.
 *
 * @typedef CreatesExternalProducts
 * @alias CreatesModels.<ExternalProduct>
 */
export type CreatesExternalProducts = CreatesModels< ExternalProductRepositoryParams >;

/**
 * An interface for reading external products using the repository.
 *
 * @typedef ReadsExternalProducts
 * @alias ReadsModels.<ExternalProduct>
 */
export type ReadsExternalProducts = ReadsModels< ExternalProductRepositoryParams >;

/**
 * An interface for updating external products using the repository.
 *
 * @typedef UpdatesExternalProducts
 * @alias UpdatesModels.<ExternalProduct>
 */
export type UpdatesExternalProducts = UpdatesModels< ExternalProductRepositoryParams >;

/**
 * An interface for deleting external products using the repository.
 *
 * @typedef DeletesExternalProducts
 * @alias DeletesModels.<ExternalProduct>
 */
export type DeletesExternalProducts = DeletesModels< ExternalProductRepositoryParams >;

/**
 * The base for the external product object.
 */
export class ExternalProduct extends AbstractProduct implements
	IProductCommon,
	IProductExternal,
	IProductPrice,
	IProductSalesTax,
	IProductUpSells {
	/**
	 * @see ./abstracts/external.ts
	 */
	public readonly buttonText: string = ''
	public readonly externalUrl: string = ''

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
	 * @see ./abstracts/upsell.ts
	 */
	public readonly upSellIds: Array<number> = [];

	/**
	 * @see ./abstracts/sales-tax.ts
	 */
	public readonly taxStatus: Taxability = Taxability.ProductAndShipping;
	public readonly taxClass: string = '';

	/**
	 * Creates a new simple product instance with the given properties
	 *
	 * @param {Object} properties The properties to set in the object.
	 */
	public constructor( properties?: Partial< ExternalProduct > ) {
		super();
		Object.assign( this, properties );
	}

	/**
	 * Creates a model repository configured for communicating via the REST API.
	 *
	 * @param {HTTPClient} httpClient The client for communicating via HTTP.
	 */
	public static restRepository( httpClient: HTTPClient ): ReturnType< typeof externalProductRESTRepository > {
		return externalProductRESTRepository( httpClient );
	}
}
