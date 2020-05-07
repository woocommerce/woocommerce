export class Product {
	public readonly id: number|null = -1;
	public readonly name: string = '';

	public constructor( args?: Partial< Product > ) {
		Object.assign( this, args );
	}
}
