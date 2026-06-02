declare module '@wordpress/core-data/build-types/utils/log-entity-deprecation' {
	/**
	 * Logs a deprecation warning for an entity, if it's deprecated.
	 *
	 * @param kind                            The kind of the entity.
	 * @param name                            The name of the entity.
	 * @param functionName                    The name of the function that was called with a deprecated entity.
	 * @param options                         The options for the deprecation warning.
	 * @param options.alternativeFunctionName The name of the alternative function that should be used instead.
	 * @param options.isShorthandSelector     Whether the function is a shorthand selector.
	 */
	export default function logEntityDeprecation(kind: string, name: string, functionName: string, { alternativeFunctionName, isShorthandSelector, }?: {
	    alternativeFunctionName?: string;
	    isShorthandSelector?: boolean;
	}): void;
}
