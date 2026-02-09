export const parseAttributes = ( data: Record< string, unknown > ) => {
	return {
		customerEmail: data?.customerEmail || '',
		nonceToken: data?.nonceToken || '',
	};
};
