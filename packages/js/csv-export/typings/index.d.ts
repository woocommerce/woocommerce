declare module 'browser-filesaver' {
	declare function FileSaver(data: Blob | string, filename?: string, options?: FileSaver.FileSaverOptions): void;

	export const saveAs: typeof FileSaver;
}
