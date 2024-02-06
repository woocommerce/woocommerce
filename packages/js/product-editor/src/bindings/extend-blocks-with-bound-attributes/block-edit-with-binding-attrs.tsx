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
import { BLOCK_BINDINGS_ALLOWED_BLOCKS, isBlockAllowed } from '../';
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

const blockEditWithBoundAttribute =
	createHigherOrderComponent< BoundBlockEditComponent >(
		( BlockEdit: BoundBlockEditComponent ) => {
			return ( props: BoundBlockEditInstance ) => {
				if ( ! isBlockAllowed( props.name ) ) {
					return <BlockEdit { ...props } />;
				}

				const { name, attributes, setAttributes, ...restProps } = props;

				/*
				 * Create an object to organize the source attributes handlers.
				 */
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

				const attributesWithBindings = useMemo( () => {
					return {
						...attributes,
						...Object.fromEntries(
							BLOCK_BINDINGS_ALLOWED_BLOCKS[ name ].map(
								( attributeName ) => {
									if (
										sourceAttributesHandlers[
											attributeName
										]
									) {
										const {
											// placeholder,
											useValue: [
												sourceValue = null,
											] = [],
										} =
											sourceAttributesHandlers[
												attributeName
											];

										if ( sourceValue ) {
											return [
												attributeName,
												sourceValue,
											];
										}
									}

									return [
										attributeName,
										attributes[ attributeName ],
									];
								}
							)
						),
					};
				}, [ attributes, sourceAttributesHandlers, name ] );

				const updatedSetAttributes = useCallback(
					( nextAttributes: BoundBlockAttributes ) => {
						Object.entries( nextAttributes ?? {} )
							.filter(
								( [ attribute ] ) =>
									attribute in sourceAttributesHandlers
							)
							.forEach( ( [ attribute, value ] ) => {
								const {
									useValue: [ , setSourceValue = null ] = [],
								} = sourceAttributesHandlers[ attribute ];

								if ( setSourceValue ) {
									setSourceValue( value );
								}
							} );
						setAttributes( nextAttributes );
					},
					[ setAttributes, sourceAttributesHandlers ]
				);

				return (
					<BlockEdit
						name={ name }
						attributes={ attributesWithBindings }
						setAttributes={ updatedSetAttributes }
						{ ...restProps }
					/>
				);
			};
		},
		'blockEditWithBoundAttribute'
	);

export default blockEditWithBoundAttribute;
