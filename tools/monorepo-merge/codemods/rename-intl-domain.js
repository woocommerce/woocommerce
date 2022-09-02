export const parser = 'tsx';

const i18nFunctionsToRename = [
    '__',
    '_n',
    '_x'
]


const isFunctionNamed = ( path, names ) => {
	return names.includes(path.value.callee.name);
};

export default function transformer( file, api ) {
	const j = api.jscodeshift;

	return j( file.source )
		.find( j.CallExpression )
		.filter( ( p ) => isFunctionNamed( p, i18nFunctionsToRename ) )
		.forEach( ( path ) => {
			j( path ) // Descend into each call expression to find any strings literal 'woocommerce-admin'
				.find( j.StringLiteral, { value: 'woocommerce-admin' } )
				.replaceWith( j.stringLiteral( 'woocommerce' ) );
		} )
		.toSource();
}
