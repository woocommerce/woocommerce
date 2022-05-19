declare module 'locutus/php/strings/number_format' {
	const number_format: (number: number | string, decimals?: number, decPoint?: string, thousandsSep?: string) => string;
	export default number_format;
}
