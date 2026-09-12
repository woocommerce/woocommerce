/**
 * External dependencies
 */
import { ColorPicker, Popover } from '@wordpress/components';
import { createRoot, useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { mountAllImagePickers } from './image-field';
import { mountAllVisualTypeSwitchers } from './type-switcher';
import {
	COLOR_INPUT_SELECTOR,
	IMAGE_INPUT_SELECTOR,
	clearSiblingVisualInput,
	observeInputValueChanges,
} from './utils';

const WRAPPER_CLASS = 'wc-admin-visual-attribute-color-picker-root';
const FALLBACK_COLOR = '#000000';
const EMPTY_COLOR_VALUE = '';

const normalizeColor = ( value: string ) => {
	if ( typeof value !== 'string' || ! value ) {
		return '';
	}

	return value.trim().toLowerCase();
};

const getInitialColor = ( input: HTMLInputElement ) => {
	const attributeValue = normalizeColor(
		input.value || input.getAttribute( 'value' ) || ''
	);

	return attributeValue;
};

const ColorField = ( { input }: { input: HTMLInputElement } ) => {
	const [ color, setColor ] = useState( () => getInitialColor( input ) );
	const [ isPopoverVisible, setIsPopoverVisible ] = useState( false );
	const triggerRef = useRef< HTMLButtonElement | null >( null );

	// Listen to changes in the input field. Because WP core uses jQuery, we
	// can't listen to native `change` and `input` events. Instead, we override
	// the `value` property to sync input changes to the color picker.
	// @see https://github.com/WordPress/wordpress-develop/blob/bd4e3c97903743ab455682f32dbf38d1b38b715a/src/js/_enqueues/admin/tags.js#L194
	useEffect( () => {
		return observeInputValueChanges( input, ( nextValue ) => {
			const nextColor = normalizeColor( nextValue );
			setColor( nextColor );
		} );
	}, [ input ] );

	useEffect( () => {
		if ( normalizeColor( input.value ) === color ) {
			return;
		}
		input.value = color;
		input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	}, [ color, input ] );

	const handleColorSelection = ( value: string ) => {
		const nextColor = normalizeColor( value );

		if ( nextColor ) {
			clearSiblingVisualInput( input, IMAGE_INPUT_SELECTOR );
		}

		setColor( nextColor );
	};

	const clearColor = () => {
		setColor( EMPTY_COLOR_VALUE );
		setIsPopoverVisible( false );
	};

	const displayedColorValue = color
		? color.toUpperCase()
		: __( 'Select a color', 'woocommerce' );

	const popoverColor = color || FALLBACK_COLOR;

	return (
		<>
			<button
				ref={ triggerRef }
				type="button"
				className="wc-admin-visual-attribute-color-picker-trigger"
				onClick={ () => setIsPopoverVisible( true ) }
				aria-haspopup="dialog"
				aria-expanded={ isPopoverVisible }
			>
				<span
					className={ `wc-admin-color-swatch${
						color ? '' : ' is-empty'
					}` }
					style={ color ? { backgroundColor: color } : undefined }
					aria-hidden="true"
				/>
				<span>{ displayedColorValue }</span>
			</button>
			{ color && (
				<button
					type="button"
					className="button-link wc-admin-visual-attribute-color-picker-clear"
					onClick={ clearColor }
				>
					{ __( 'Clear', 'woocommerce' ) }
				</button>
			) }
			{ isPopoverVisible && triggerRef.current && (
				<Popover
					anchor={ triggerRef.current }
					onClose={ () => setIsPopoverVisible( false ) }
					placement="bottom-start"
				>
					<ColorPicker
						color={ popoverColor }
						onChange={ handleColorSelection }
					/>
				</Popover>
			) }
		</>
	);
};

const mountColorPicker = ( input: HTMLInputElement ) => {
	if ( input.dataset.wcColorPickerMounted === '1' ) {
		return;
	}

	input.dataset.wcColorPickerMounted = '1';
	input.style.height = '0';
	input.style.width = '0';
	input.style.position = 'absolute';
	input.style.top = '0';
	input.style.left = '0';
	input.style.opacity = '0';
	input.style.visibility = 'hidden';
	input.style.pointerEvents = 'none';
	input.style.userSelect = 'none';

	const wrapper = document.createElement( 'div' );
	wrapper.className = WRAPPER_CLASS;
	input.insertAdjacentElement( 'beforebegin', wrapper );

	const root = createRoot( wrapper );
	root.render( <ColorField input={ input } /> );

	// Make sure labels associated to the input also trigger the color picker.
	const associatedLabels = input.labels ? Array.from( input.labels ) : [];
	associatedLabels.forEach( ( labelElement ) => {
		labelElement.addEventListener( 'click', ( event ) => {
			event.preventDefault();

			const trigger = wrapper.querySelector< HTMLButtonElement >(
				'.wc-admin-visual-attribute-color-picker-trigger'
			);

			trigger?.click();
		} );
	} );
};

const mountAllColorPickers = ( context: ParentNode = document ) => {
	const colorInputs = context.querySelectorAll( COLOR_INPUT_SELECTOR );

	colorInputs.forEach( ( inputElement ) => {
		if ( inputElement instanceof HTMLInputElement ) {
			mountColorPicker( inputElement );
		}
	} );
};

const mountAllVisualAttributeFields = ( context: ParentNode = document ) => {
	mountAllColorPickers( context );
	mountAllImagePickers( context );
	mountAllVisualTypeSwitchers( context );
};

const startObserver = () => {
	const observer = new MutationObserver( ( mutationList ) => {
		mutationList.forEach( ( mutation ) => {
			mutation.addedNodes.forEach( ( node ) => {
				if ( node instanceof HTMLElement ) {
					mountAllVisualAttributeFields( node );
				}
			} );
		} );
	} );

	observer.observe( document.body, {
		childList: true,
		subtree: true,
	} );
};

mountAllVisualAttributeFields();
startObserver();
