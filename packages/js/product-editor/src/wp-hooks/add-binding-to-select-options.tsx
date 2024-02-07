/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { createElement, useEffect, useState } from '@wordpress/element';
import { createHigherOrderComponent } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import useProductEntityProp from '../hooks/use-product-entity-prop';

const createOption = ( customArg: string, num: number ) => ( {
	label: `Option ${ customArg.toUpperCase() }-${ num }`,
	value: `option-${ customArg }-${ num }`,
} );

const bindingSources = {
	'custom-options': {
		name: 'custom-options',
		label: 'Custom Options',
		// @ts-ignore todo: fix types
		useSource( props, sourceAttributes ) {
			const options = [
				createOption( sourceAttributes.customArg, 1 ),
				createOption( sourceAttributes.customArg, 2 ),
				createOption( sourceAttributes.customArg, 3 ),
			];

			return {
				placeholder: null,
				useValue: [ options ],
			};
		},
	},
	'custom-options-async': {
		name: 'custom-options-async',
		label: 'Custom Options Async',
		// @ts-ignore todo: fix types
		useSource( props, sourceAttributes ) {
			const [ options, setOptions ] = useState( null );

			useEffect( () => {
				setTimeout( () => {
					// @ts-ignore todo: fix types
					setOptions( [
						createOption( sourceAttributes.customArg, 1 ),
						createOption( sourceAttributes.customArg, 2 ),
						createOption( sourceAttributes.customArg, 3 ),
					] );
				}, 5000 );
			}, [ sourceAttributes ] );

			return {
				placeholder: null,
				useValue: [ options ],
			};
		},
	},
	'custom-options-product-props': {
		name: 'custom-options-product-props',
		label: 'Custom Options Product Props',
		// @ts-ignore todo: fix types
		useSource() {
			const [ regularPrice ] = useProductEntityProp( 'regular_price' );

			let options;

			if ( ( parseFloat( regularPrice as string ) || 0 ) > 10 ) {
				options = [
					{ label: 'Expensive Option', value: 'expensive-option' },
					{
						label: 'Super Expensive Option',
						value: 'super-expensive-option',
					},
				];
			} else {
				options = [
					{ label: 'Cheap Option', value: 'cheap-option' },
					{
						label: 'Super Cheap Option',
						value: 'super-cheap-option',
					},
				];
			}

			return {
				placeholder: null,
				useValue: [ options ],
			};
		},
	},
};

const maybeAddBindingToSelectOptions = createHigherOrderComponent<
	Record< string, unknown >
>( ( BlockEdit ) => {
	return ( props ) => {
		if (
			props.name === 'woocommerce/product-select-field' &&
			// @ts-ignore todo: fix types
			props.attributes.metadata?.bindings?.options?.source
		) {
			const optionsBindingSource =
				// @ts-ignore todo: fix types
				props.attributes.metadata.bindings.options.source;

			// @ts-ignore todo: fix types
			const source = bindingSources[ optionsBindingSource ];

			if ( ! source ) {
				throw new Error( `Unknown source ${ optionsBindingSource }` );
			}

			const { placeholder, useValue: [ options ] = [] } =
				source.useSource(
					props,
					// @ts-ignore todo: fix types
					props.attributes.metadata.bindings.options.args
				);

			if ( placeholder && ! options ) {
				// @ts-ignore todo: fix types
				props.attributes.options = placeholder;
				// @ts-ignore todo: fix types
				props.attributes.disabled = true;
			}

			if ( options ) {
				// @ts-ignore todo: fix types
				props.attributes.options = options;
				// @ts-ignore todo: fix types
				props.attributes.disabled = false;
			}

			return <BlockEdit { ...props } />;
		}

		return <BlockEdit { ...props } />;
	};
}, 'maybeAddBindingToSelectOptions' );

export default function () {
	addFilter(
		'editor.BlockEdit',
		'woocommerce/add-binding-to-select-options',
		maybeAddBindingToSelectOptions
	);
}
