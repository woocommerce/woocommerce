declare module 'browser-filesaver' {
	declare function FileSaver(data: Blob, filename: string, disableAutoBOM?: boolean): void;

	export const saveAs: typeof FileSaver;
}
