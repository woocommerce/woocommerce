declare module '@wordpress/data/build-types/redux-store/thunk-middleware' {
	export default function createThunkMiddleware(args: any): () => (next: any) => (action: any) => any;
}
