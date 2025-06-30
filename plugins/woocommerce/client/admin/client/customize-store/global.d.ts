declare global {
	interface Window {
    wcBlockSettings: {
      [ key: string ]: unknown;
    };
	}
}

/*~ If your module exports nothing, you'll need this line. Otherwise, delete it */
export {};
