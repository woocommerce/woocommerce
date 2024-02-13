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

/**
 * Internal dependencies
 */
import type { BoundBlockEditComponent, BoundBlockEditInstance } from '../types';
import { getBlockBindingsSource } from '..';

type BoundAttributePlugProps = {
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
	 * React custon hook to bind a source to a block.
	 */
	useSource: (
		blockProps: BoundBlockEditInstance,
		args: any // eslint-disable-line @typescript-eslint/no-explicit-any
	) => {
		value: any; // eslint-disable-line @typescript-eslint/no-explicit-any
		updateValue: ( newValue: any ) => void; // eslint-disable-line @typescript-eslint/no-explicit-any
	};

	/*
	 * The block props with bound attribute.
	 */
	blockProps: BoundBlockEditInstance;

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
 * @param {BoundAttributePlugProps} props - The component props.
 * @return {null} The component.
 */
const BlockBindingConnector = ( {
	args,
	attrName,
	attrValue,
	blockProps,
	useSource,
}: BoundAttributePlugProps ): null => {
	const { value: propValue, updateValue: updatePropValue } = useSource(
		blockProps,
		args
	);
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
	const lastPropValue = useRef();
	const lastAttrValue = useRef();

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
		if ( propValue !== lastPropValue.current ) {
			lastPropValue.current = propValue;
			updateBoundAttibute( propValue );
			return;
		}

		/*
		 * Block Attribute => Source Prop
		 *
		 * Detect changes in block attribute value,
		 * and update the source prop value accordingly.
		 */
		if ( attrValue !== lastAttrValue.current ) {
			lastAttrValue.current = attrValue;
			updatePropValue( attrValue );
		}
	}, [ updateBoundAttibute, propValue, attrValue, updatePropValue ] );

	return null;
};

const withBlockBindingSupport =
	createHigherOrderComponent< BoundBlockEditComponent >(
		( BlockEdit: BoundBlockEditComponent ) =>
			( props: BoundBlockEditInstance ) => {
				const { attributes, name } = props;

				// Bail early if there are no bindings.
				const bindings = attributes?.metadata?.bindings;
				if ( ! bindings ) {
					return <BlockEdit { ...props } />;
				}

				const BindingConnectorInstances: JSX.Element[] = [];

				Object.entries( bindings ).forEach(
					( [ attrName, settings ], i ) => {
						const source = getBlockBindingsSource(
							settings.source
						);

						if ( source ) {
							const { useSource } = source;
							const attrValue = attributes[ attrName ];

							// Create a unique key for the connector instance
							const key = `${ settings.source }-${ name }-${ attrName }-${ i }`;

							BindingConnectorInstances.push(
								<BlockBindingConnector
									key={ key }
									attrName={ attrName }
									attrValue={ attrValue }
									useSource={ useSource }
									blockProps={ props }
									args={ settings.args }
								/>
							);
						}
					}
				);

				return (
					<>
						{ BindingConnectorInstances }
						<BlockEdit { ...props } />
					</>
				);
			},
		'withBlockBindingSupport'
	);

export default withBlockBindingSupport;
