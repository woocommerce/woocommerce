declare module '@wordpress/data/build-types/plugins/persistence/storage/object' {
	export default storage;
	declare namespace storage {
	    function getItem(key: any): any;
	    function setItem(key: any, value: any): void;
	    function clear(): void;
	}
}
