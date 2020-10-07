import { Factory } from 'fishery';

/**
 * A temporary class until Fishery includes better async support.
 */
export class AsyncFactory extends Factory {
	constructor( generator, creator ) {
		super( generator );
		this.creator = creator;
	}

	/**
	 * Create an object using your factory
	 *
	 * @param {*} params The parameters that should populate the object.
	 * @param {*} options The options to be used in the builder.
	 * @return {Promise} Resolves to the created model.
	 */
	create( params = {}, options = {} ) {
		const model = this.build( params, options );
		return this.creator( model );
	}

	/**
	 * Create an array of objects using your factory
	 *
	 * @param {number} number The number of models to create.
	 * @param {*}      params The parameters that should populate the object.
	 * @param {*}      options The options to be used in the builder.
	 * @return {Promise} Resolves to the created models.
	 */
	createList( number, params = {}, options = {} ) {
		const models = this.buildList( number, params, options );
		const promises = [];
		for ( const model of models ) {
			promises.push( this.creator( model ) );
		}

		return Promise.all( promises );
	}
}
