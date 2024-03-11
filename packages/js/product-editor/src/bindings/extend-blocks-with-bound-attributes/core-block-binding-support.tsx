/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import {
	createElement,
	Fragment,
	useEffect,
	useCallback,
	useRef,
} from '@wordpress/element';
import { getBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import type {
	BindingSourceHandlerProps,
	BoundBlockEditComponent,
	BoundBlockEditInstance,
	MetadataBindingsProps,
} from '../types';
import { getBlockBindingsSource, hasPossibleBlockBinding } from '..';

type BlockBindingConnectorProp = {
	/*
	 * The name of the attribute to bind.
	 */
	attrName: string;

	/*
	 * The value of the attribute
	 * it can be any type.
	 */
	attrValue: any; // eslint-disable-line @typescript-eslint/no-explicit-any

	/*
	 * The block props with bound attribute.
	 */
	blockProps: BoundBlockEditInstance;

	/*
	 * The source handler.
	 */
	source: BindingSourceHandlerProps< any >; // eslint-disable-line @typescript-eslint/no-explicit-any

	/*
	 * The source args.
	 */
	args: any; // eslint-disable-line @typescript-eslint/no-explicit-any

	placeholder?: string | null;
};

/**
 * This component is responsible detecting and
 * propagating data changes between block attribute and
 * the block-binding source property.
 *
 * The app creates an instance of this component for each
 * pair of block-attribute/source-property.
 *
 * @param {BlockBindingConnectorProp} props - The component props.
 * @return {null} The component.
 */
const BlockBindingConnector = ( {
	args,
	attrName,
	attrValue,
	blockProps,
	source,
}: BlockBindingConnectorProp ): null => {
	const {
		placeholder,
		value: propValue,
		updateValue: updatePropValue,
	} = source.useSource( blockProps, args );

	const blockName = blockProps.name;

	const setAttributes = blockProps.setAttributes;

	const updateBoundAttibute = useCallback(
		( newAttrValue ) => {
			setAttributes( {
				[ attrName ]: newAttrValue,
			} );
		},
		[ attrName, setAttributes ]
	);

	// Store a reference to the last value and attribute value.
	const lastPropValue = useRef( propValue );
	const lastAttrValue = useRef( attrValue );

	/*
	 * Initially sync (first render / onMount ) attribute
	 * value with the source prop value.
	 */
	useEffect( () => {
		updateBoundAttibute( propValue );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ updateBoundAttibute ] );

	/*
	 * Sync data.
	 * This effect will run every time
	 * the attribute value or the prop value changes.
	 * It will sync them in both directions.
	 */
	useEffect( () => {
		/*
		 * Source Prop => Block Attribute
		 *
		 * Detect changes in source prop value,
		 * and update the attribute value accordingly.
		 */
		if ( typeof propValue !== 'undefined' ) {
			if ( propValue !== lastPropValue.current ) {
				lastPropValue.current = propValue;
				updateBoundAttibute( propValue );
				return;
			}
		} else if ( placeholder ) {
			/*
			 * If the attribute is `src` or `href`,
			 * a placeholder can't be used because it is not a valid url.
			 * Adding this workaround until
			 * attributes and metadata fields types are improved and include `url`.
			 */
			const htmlAttribute = (
				getBlockType( blockName )?.attributes[ attrName ] as {
					attribute: string;
				}
			 ).attribute;

			if ( htmlAttribute === 'src' || htmlAttribute === 'href' ) {
				updateBoundAttibute( null );
				return;
			}

			updateBoundAttibute( placeholder );
		}

		/*
		 * Block Attribute => Source Prop
		 *
		 * Detect changes in block attribute value,
		 * and update the source prop value accordingly.
		 */
		if ( attrValue !== lastAttrValue.current && updatePropValue ) {
			lastAttrValue.current = attrValue;
			updatePropValue( attrValue );
		}
	}, [
		updateBoundAttibute,
		propValue,
		attrValue,
		updatePropValue,
		placeholder,
		blockName,
		attrName,
	] );

	return null;
};

function BlockBindingBridge( {
	bindings,
	props,
}: {
	bindings: MetadataBindingsProps;
	props: BoundBlockEditInstance;
} ) {
	if ( ! bindings ) {
		return null;
	}

	const { name, attributes } = props;

	// Collect all the binding connectors.
	const BindingConnectorInstances: JSX.Element[] = [];

	Object.entries( bindings ).forEach(
		( [ attrName, boundAttrSettings ], i ) => {
			// Check if the block attribute can be bound.
			if ( ! hasPossibleBlockBinding( name, attrName ) ) {
				return;
			}

			// Bail early if the block doesn't have a valid source handler.
			const source = getBlockBindingsSource( boundAttrSettings.source );
			if ( ! source ) {
				return;
			}

			BindingConnectorInstances.push(
				<BlockBindingConnector
					key={ `${ boundAttrSettings.source }-${ name }-${ attrName }-${ i }` }
					attrName={ attrName }
					attrValue={ attributes[ attrName ] }
					source={ source }
					blockProps={ props }
					args={ boundAttrSettings.args }
				/>
			);
		}
	);

	return <>{ BindingConnectorInstances }</>;
}

const withBlockBindingSupport =
	createHigherOrderComponent< BoundBlockEditComponent >(
		( BlockEdit: BoundBlockEditComponent ) =>
			( props: BoundBlockEditInstance ) => {
				const { attributes } = props;

				// Bail early if the block doesn't have bindings.
				const bindings = attributes?.metadata?.bindings;
				if ( ! bindings || Object.keys( bindings ).length === 0 ) {
					return null;
				}

				return (
					<>
						<BlockBindingBridge
							bindings={ bindings }
							props={ props }
						/>
						<BlockEdit { ...props } />
					</>
				);
			},
		'withBlockBindingSupport'
	);

export default withBlockBindingSupport;
