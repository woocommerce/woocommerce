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
	 * The value of the property
	 * it can be any type.
	 */
	propValue: any; // eslint-disable-line @typescript-eslint/no-explicit-any

	/*
	 * Callback function invoked when the
	 * attribute value changes.
	 * Use it like an oportunity to update the prop value
	 * based on the attribute value.
	 */
	onAttributeChange: ( attribute: string ) => void;

	/*
	 * The value of the attribute
	 * it can be any type.
	 */
	attrValue: any; // eslint-disable-line @typescript-eslint/no-explicit-any

	/*
	 * Callback function invoked when the
	 * prop value changes.
	 * Use it like an oportunity to update the attribute value
	 * based on the prop value.
	 */
	onPropValueChange: ( propValue: string ) => void;

	placeholder?: string | null;
};

/**
 * Conponent to bind an attribute to a prop.
 *
 * @param {BoundAttributePlugProps} props - The component props.
 * @return {null} The component.
 */
const BlockBindingConnector = ( {
	propValue,
	onAttributeChange,

	attrValue,
	onPropValueChange = () => {},
}: BoundAttributePlugProps ): null => {
	const lastPropValue = useRef();
	const lastAttrValue = useRef();

	useEffect( () => {
		/*
		 * When the prop value changes, update the attribute value.
		 */
		if ( propValue !== lastPropValue.current ) {
			lastPropValue.current = propValue;
			onAttributeChange( propValue );
		}
	}, [ onAttributeChange, propValue ] );

	useEffect( () => {
		/*
		 * When the attribute value changes, update the prop value.
		 */
		if ( attrValue !== lastAttrValue.current ) {
			console.count( '(attr change) sync Attr to Prop' ); // eslint-disable-line no-console
			lastAttrValue.current = attrValue;
			onPropValueChange( attrValue );
		}
	}, [ onPropValueChange, attrValue ] );

	return null;
};

const withBlockBindingSupport =
	createHigherOrderComponent< BoundBlockEditComponent >(
		( BlockEdit: BoundBlockEditComponent ) =>
			( props: BoundBlockEditInstance ) => {
				const { attributes, setAttributes } = props;

				// // @ts-expect-error There are no types for this.
				// const { getBlockBindingsSource } =
				// 	useSelect( blockEditorStore );
				const bindings = attributes?.metadata?.bindings;
				const Bridges: JSX.Element[] = [];

				if ( bindings ) {
					Object.entries( bindings ).forEach(
						( [ attrName, settings ] ) => {
							const source = getBlockBindingsSource(
								settings.source
							);

							if ( source ) {
								const attrValue = attributes[ attrName ];

								/*
								 * Pick the prop value and setter
								 * from the source custom hook.
								 */
								const {
									useValue: [ propValue, setPropValue ] = [],
								} = source.useSource( props, settings.args );

								// Create a unique key for the bridge instance
								const key = `${ settings.source.replace(
									/\//gi,
									'-'
								) }-${ attrName }`;

								Bridges.push(
									<BlockBindingConnector
										key={ key }
										attrValue={ attrValue }
										onAttributeChange={ useCallback(
											( newAttrValue: string ) => {
												setAttributes( {
													[ attrName ]: newAttrValue,
												} );
											},
											[ attrName ]
										) }
										propValue={ propValue }
										onPropValueChange={ useCallback(
											( newPropValue: string ) => {
												setPropValue?.( newPropValue );
											},
											[ setPropValue ]
										) }
									/>
								);
							}
						}
					);
				}

				return (
					<>
						{ Bridges }
						<BlockEdit { ...props } />
					</>
				);
			},
		'withBlockBindingSupport'
	);

export default withBlockBindingSupport;
