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
 * Conponent to plug an attribute to a prop.
 *
 * @param {BoundAttributePlugProps} props - The component props.
 * @return {null} The component.
 */
const BlockBindingConnector = ( {
	attrName,
	attrValue,
	useSource,
	blockProps,
	args,
}: BoundAttributePlugProps ): null => {
	const lastPropValue = useRef();
	const lastAttrValue = useRef();
	const { value, updateValue } = useSource( blockProps, args );

	const setAttributes = blockProps.setAttributes;

	const onPropValueChange = useCallback(
		( newAttrValue ) => {
			setAttributes( {
				[ attrName ]: newAttrValue,
			} );
		},
		[ attrName, setAttributes ]
	);

	/*
	 * Source Prop => Block Attribute
	 *
	 * Detect changes in source prop value,
	 * and update the attribute value accordingly.
	 */
	useEffect( () => {
		if ( value === lastPropValue.current ) {
			return;
		}

		lastPropValue.current = value;
		onPropValueChange( value );
	}, [ onPropValueChange, value ] );

	/*
	 * Block Attribute => Source Prop
	 *
	 * Detect changes in block attribute value,
	 * and update the source prop value accordingly.
	 */
	useEffect( () => {
		if ( attrValue === lastAttrValue.current ) {
			return;
		}

		lastAttrValue.current = attrValue;
		updateValue( attrValue );
	}, [ updateValue, attrValue ] );

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
