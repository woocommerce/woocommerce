import {
	AbstractProductData,
	IProductDelivery,
	IProductInventory,
	IProductSalesTax,
	IProductShipping,
} from './abstract';
import {
	ProductLinks,
	Taxability,
	ProductDownload,
	StockStatus,
	BackorderStatus,
} from './shared';

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
