type StorePage = {
	id?: number;
};

type StorePages = {
	cart?: StorePage;
	checkout?: StorePage;
};

const getStorePageId = ( page: keyof StorePages ): number => {
	const storePages = ( window.wcSettings?.storePages as StorePages ) || {};
	return storePages[ page ]?.id || 0;
};

export const CART_PAGE_ID = getStorePageId( 'cart' );
export const CHECKOUT_PAGE_ID = getStorePageId( 'checkout' );
