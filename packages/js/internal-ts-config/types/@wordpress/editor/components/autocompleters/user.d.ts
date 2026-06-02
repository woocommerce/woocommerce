declare module '@wordpress/editor/build-types/components/autocompleters/user' {
	export function getUserLabel(user: any): import("react").JSX.Element;
	declare const _default: {
	    name: string;
	    className: string;
	    triggerPrefix: string;
	    useItems(filterValue: any): {
	        key: string;
	        value: import("@wordpress/core-data").User<"edit">;
	        label: import("react").JSX.Element;
	    }[][];
	    getOptionCompletion(user: any): string;
	};
	export default _default;
}
