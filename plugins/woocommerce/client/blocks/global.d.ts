// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
declare let __webpack_public_path__: string;

interface CookieStoreGetResult {
	name: string;
	value: string;
}

interface CookieStore {
	get( name: string ): Promise< CookieStoreGetResult | null >;
}

// eslint-disable-next-line no-var
declare var cookieStore: CookieStore | undefined;
