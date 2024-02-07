/**
 * External dependencies
 */
import { type BlockAttributes } from '@wordpress/blocks';
import { createHigherOrderComponent } from '@wordpress/compose';
import {
	// @ts-expect-error no exported member.
	type ComponentType,
	createElement,
	useMemo,
	useCallback,
} from '@wordpress/element';
/**
 * Internal dependencies
 */
import { isBlockAllowed } from '../';
import productEntitySource, {
	type CoreBlockEditProps,
} from '../product-entity-source/index';

type BoundBlockAttributes = BlockAttributes & {
	metadata: {
		bindings: Record<
			string,
			{
				source: string;
				args: { prop: string };
			}
		>;
	};
};

type BoundBlockEditInstance = CoreBlockEditProps< BoundBlockAttributes >;
type BoundBlockEditComponent = ComponentType< BoundBlockEditInstance >;

type BoundAttributesProps = {
	[ attributeName: string ]: {
		placeholder: string | null;
		useValue: [ string, ( newValue: string ) => void ];
	};
};

type BoundAttributesValuesProps = {
	[ attributeName: string ]: {
		value: string;
		fullValue: string;
	};
};

const blockEditWithBoundAttribute =
	createHigherOrderComponent< BoundBlockEditComponent >(
		( BlockEdit: BoundBlockEditComponent ) => {
			return ( props: BoundBlockEditInstance ) => {
				if ( ! isBlockAllowed( props.name ) ) {
					return <BlockEdit { ...props } />;
				}

				const { name, attributes, setAttributes, ...restProps } = props;
				const { bindings } = attributes?.metadata || {};
				const sourceAttributesHandlers: BoundAttributesProps = {};

				if ( attributes?.metadata?.bindings ) {
					Object.entries( attributes?.metadata?.bindings ).forEach(
						( source ) => {
							const [ attribute, binding ] = source;

							sourceAttributesHandlers[ attribute ] =
								productEntitySource.useSource(
									props,
									binding.args
								);
						}
					);
				}

				/*
				 * Populate the block attributes
				 * with the source values
				 */
				const boundAttributes = useMemo( () => {
					const values: BoundAttributesValuesProps = {};

					// Do not populate if the block is not allowed
					if ( ! isBlockAllowed( name ) ) {
						return {};
					}

					if ( bindings ) {
						Object.entries( bindings ).forEach( ( source ) => {
							const [ boundAttributeName, binding ] = source;

							// Get the source value bound to the block attribute
							const boundAttributeValue =
								productEntitySource.getSourcePropValue(
									props,
									binding.args
								);

							values[ boundAttributeName ] =
								boundAttributeValue.value;
						} );
					}

					return {
						...values,
					};
				}, [ name, bindings, props ] );

				// Merge the block attributes with the source values.
				const fullAttributes = useMemo(
					() => ( {
						...attributes,
						...boundAttributes,
					} ),
					[ attributes, boundAttributes ]
				);

				const setAttributesWithBindings = useCallback(
					( nextAttributes: BoundBlockAttributes ) => {
						/*
						 * Do not modify the setting attributes handler
						 * if the block is not allowed
						 */
						if ( ! isBlockAllowed( name ) ) {
							setAttributes( nextAttributes );
							return;
						}

						for ( const attributeName of Object.keys(
							nextAttributes
						) ) {
							const attributeNextValue =
								nextAttributes[ attributeName ];

							// Check if the attribute is bound to a source
							if ( boundAttributes[ attributeName ] ) {
								const updatePropValueHandler =
									productEntitySource.updateSourcePropHandler(
										props,
										bindings[ attributeName ].args
									);

								if ( updatePropValueHandler ) {
									updatePropValueHandler(
										attributeNextValue
									);
								}
							}
						}
					},
					[ bindings, boundAttributes, name, props, setAttributes ]
				);

				return (
					<BlockEdit
						name={ name }
						attributes={ fullAttributes }
						setAttributes={ setAttributesWithBindings }
						{ ...restProps }
					/>
				);
			};
		},
		'blockEditWithBoundAttribute'
	);

export default blockEditWithBoundAttribute;
