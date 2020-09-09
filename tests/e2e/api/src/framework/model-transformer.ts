import { Model } from '../models/model';

type ToServerFn< T > = ( model: T ) => any;
type FromServerFn< T > = ( data: any ) => T;

/**
 * A class for transforming models between the server representation and the client representation.
 */
export class ModelTransformer< T extends Model > {
	private readonly toServerHook: ToServerFn< T >;
	private readonly fromServerHook: FromServerFn< T >;

	public constructor( toServer: ToServerFn< T >, fromServer: FromServerFn< T > ) {
		this.toServerHook = toServer;
		this.fromServerHook = fromServer;
	}

	/**
	 * Transforms the model to a representation the server will understand.
	 *
	 * @param {Model} model The model to transform.
	 * @return {*} The transformed model.
	 */
	public toServer( model: T ): any {
		return this.toServerHook( model );
	}

	/**
	 * Transforms the model from the server's representation to the client model.
	 *
	 * @param {*} data The server representation.
	 * @return {Model} The transformed model.
	 */
	public fromServer( data: any ): T {
		return this.fromServerHook( data );
	}
}
