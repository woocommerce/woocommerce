import {
	AbstractProductData,
	IProductDelivery,
	IProductInventory,
	IProductSalesTax,
	IProductShipping,
	ProductSearchParams,
} from './abstract';
import {
	ProductDataUpdateParams,
	ProductDeliveryUpdateParams,
	ProductInventoryUpdateParams,
	ProductSalesTaxUpdateParams,
	ProductShippingUpdateParams,
	ProductLinks,
	Taxability,
	ProductDownload,
	StockStatus,
	BackorderStatus,
} from './shared';
import {
	CreatesModels,
	DeletesModels,
	ListsModels,
	ModelRepositoryParams,
	ReadsModels,
	UpdatesModels,
} from '../../framework';

/**
 * The parameters that product variations can update.
 */
type ProductVariationUpdateParams = ProductDataUpdateParams
	& ProductDeliveryUpdateParams
	& ProductInventoryUpdateParams
	& ProductSalesTaxUpdateParams
	& ProductShippingUpdateParams;
/**
 * The parameters embedded in this generic can be used in the ModelRepository in order to give
 * type-safety in an incredibly granular way.
 */
export type ProductVariationRepositoryParams =
	ModelRepositoryParams< ProductVariation, never, ProductSearchParams, ProductVariationUpdateParams >;

/**
 * An interface for listing variable products using the repository.
 *
 * @typedef ListsProductVariations
 * @alias ListsModels.<ProductVariation>
 */
export type ListsProductVariations = ListsModels< ProductVariationRepositoryParams >;

/**
 * An interface for creating variable products using the repository.
 *
 * @typedef CreatesProductVariations
 * @alias CreatesModels.<ProductVariation>
 */
export type CreatesProductVariations = CreatesModels< ProductVariationRepositoryParams >;

/**
 * An interface for reading variable products using the repository.
 *
 * @typedef ReadsProductVariations
 * @alias ReadsModels.<ProductVariation>
 */
export type ReadsProductVariations = ReadsModels< ProductVariationRepositoryParams >;

/**
 * An interface for updating variable products using the repository.
 *
 * @typedef UpdatesProductVariations
 * @alias UpdatesModels.<ProductVariation>
 */
export type UpdatesProductVariations = UpdatesModels< ProductVariationRepositoryParams >;

/**
 * An interface for deleting variable products using the repository.
 *
 * @typedef DeletesProductVariations
 * @alias DeletesModels.<ProductVariation>
 */
export type DeletesProductVariations = DeletesModels< ProductVariationRepositoryParams >;

/**
 * The base for the product variation object.
 */
export class ProductVariation extends AbstractProductData implements
	IProductDelivery,
	IProductInventory,
	IProductSalesTax,
	IProductShipping {
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
	 * The variation links.
	 *
	 * @type {ReadonlyArray.<ProductLinks>}
	 */
	public readonly links: ProductLinks = {
		collection: [ { href: '' } ],
		self: [ { href: '' } ],
		up: [ { href: '' } ],
	};

	/**
	 * Creates a new product variation instance with the given properties
	 *
	 * @param {Object} properties The properties to set in the object.
	 */
	public constructor( properties?: Partial< ProductVariation > ) {
		super();
		Object.assign( this, properties );
	}
}
