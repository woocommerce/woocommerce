/**
 * External dependencies
 */
import { ColorPicker, Popover } from '@wordpress/components';
import { createRoot, useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const INPUT_SELECTOR = 'input.wc-admin-visual-attribute-color-input';
const WRAPPER_CLASS = 'wc-admin-visual-attribute-color-picker-root';
const FALLBACK_COLOR = '#000000';
const EMPTY_COLOR_VALUE = '';

const normalizeColor = ( value: string ) => {
	if ( ! value ) {
		return '';
	}

	return value.trim().toLowerCase();
};

const getInitialColor = ( input: HTMLInputElement ) => {
	const attributeValue = normalizeColor(
		input.getAttribute( 'value' ) ?? ''
	);

	if ( attributeValue ) {
		return attributeValue;
	}

	return '';
};

const ColorField = ( { input }: { input: HTMLInputElement } ) => {
	const [ color, setColor ] = useState( () => getInitialColor( input ) );
	const [ isPopoverVisible, setIsPopoverVisible ] = useState( false );
	const triggerRef = useRef< HTMLButtonElement | null >( null );

	useEffect( () => {
		input.value = color;
		input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	}, [ color, input ] );

	const handleColorSelection = ( value: string ) => {
		const nextColor = normalizeColor( value );
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
				ref={ triggerRef as never }
				type="button"
				className="wc-admin-visual-attribute-color-picker-trigger"
				onClick={ () => setIsPopoverVisible( true ) }
				aria-haspopup="dialog"
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
			<button
				type="button"
				className="button-link wc-admin-visual-attribute-color-picker-clear"
				onClick={ clearColor }
			>
				{ __( 'Clear', 'woocommerce' ) }
			</button>
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
	input.style.display = 'none';

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
	const colorInputs = context.querySelectorAll( INPUT_SELECTOR );

	colorInputs.forEach( ( inputElement ) => {
		if ( inputElement instanceof HTMLInputElement ) {
			mountColorPicker( inputElement );
		}
	} );
};

const startObserver = () => {
	const observer = new MutationObserver( ( mutationList ) => {
		mutationList.forEach( ( mutation ) => {
			mutation.addedNodes.forEach( ( node ) => {
				if ( node instanceof HTMLElement ) {
					mountAllColorPickers( node );
				}
			} );
		} );
	} );

	observer.observe( document.body, {
		childList: true,
		subtree: true,
	} );
};

mountAllColorPickers();
startObserver();
