/**
 * External dependencies
 */
import { createRoot, useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	COLOR_INPUT_SELECTOR,
	clearSiblingVisualInput,
	clearVisualInput,
	IMAGE_INPUT_SELECTOR,
	observeInputValueChanges,
} from './utils';

const WRAPPER_CLASS = 'wc-admin-visual-attribute-image-picker-root';
const EMPTY_IMAGE_VALUE = '';

const getInitialImageId = ( input: HTMLInputElement ) => {
	const attributeValue = input.value || input.getAttribute( 'value' ) || '';

	return attributeValue.trim();
};

type MediaAttachment = {
	id: number;
	url: string;
	sizes?: {
		thumbnail?: {
			url: string;
		};
	};
};

const ImageField = ( { input }: { input: HTMLInputElement } ) => {
	const [ imageId, setImageId ] = useState( () =>
		getInitialImageId( input )
	);
	const [ previewUrl, setPreviewUrl ] = useState( '' );
	const [ isMediaFrameOpen, setIsMediaFrameOpen ] = useState( false );
	const triggerRef = useRef< HTMLButtonElement | null >( null );
	const mediaFrameRef = useRef< wp.media.frame | null >( null );

	// Listen to changes in the input field. Because WP core uses jQuery, we
	// can't listen to native `change` and `input` events. Instead, we override
	// the `value` property to sync input changes to the image picker.
	// @see https://github.com/WordPress/wordpress-develop/blob/bd4e3c97903743ab455682f32dbf38d1b38b715a/src/js/_enqueues/admin/tags.js#L194
	useEffect( () => {
		return observeInputValueChanges( input, ( nextValue ) => {
			const nextImageId = nextValue.trim();
			setImageId( nextImageId );

			if ( ! nextImageId ) {
				setPreviewUrl( '' );
			}
		} );
	}, [ input ] );

	useEffect( () => {
		if ( getInitialImageId( input ) === imageId ) {
			return;
		}

		input.value = imageId;
		input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	}, [ imageId, input ] );

	useEffect( () => {
		let isCurrent = true;

		if ( ! imageId ) {
			setPreviewUrl( '' );
			return () => {
				isCurrent = false;
			};
		}

		const attachmentId = Number.parseInt( imageId, 10 );

		if ( Number.isNaN( attachmentId ) || attachmentId <= 0 ) {
			setPreviewUrl( '' );
			return () => {
				isCurrent = false;
			};
		}

		const attachment = window.wp.media.attachment( attachmentId );

		attachment
			.fetch()
			.then( () => {
				if ( ! isCurrent ) {
					return;
				}

				const sizes = attachment.get(
					'sizes'
				) as MediaAttachment[ 'sizes' ];
				const url =
					sizes?.thumbnail?.url ||
					( attachment.get( 'url' ) as string ) ||
					'';

				setPreviewUrl( url );
			} )
			.catch( () => {
				if ( isCurrent ) {
					setPreviewUrl( '' );
				}
			} );

		return () => {
			isCurrent = false;
		};
	}, [ imageId ] );

	const setImageValue = ( attachment: MediaAttachment ) => {
		const nextImageId = String( attachment.id );
		const thumbnailUrl =
			attachment.sizes?.thumbnail?.url || attachment.url || '';

		setImageId( nextImageId );
		setPreviewUrl( thumbnailUrl );
		clearSiblingVisualInput( input, COLOR_INPUT_SELECTOR );
	};

	const openMediaLibrary = () => {
		let frame = mediaFrameRef.current;

		if ( ! frame ) {
			frame = window.wp.media( {
				title: __( 'Choose an image', 'woocommerce' ),
				button: {
					text: __( 'Use image', 'woocommerce' ),
				},
				library: {
					type: 'image',
				},
				multiple: false,
			} );

			frame.on( 'select', () => {
				const selection = frame?.state().get( 'selection' );

				if ( ! selection?.first ) {
					return;
				}

				const attachment = selection
					.first()
					.toJSON() as MediaAttachment;

				if ( attachment?.id ) {
					setImageValue( attachment );
				}
			} );
			frame.on( 'open', () => setIsMediaFrameOpen( true ) );
			frame.on( 'close', () => setIsMediaFrameOpen( false ) );

			mediaFrameRef.current = frame;
		}

		frame.open();
	};

	const clearImage = () => {
		setImageId( EMPTY_IMAGE_VALUE );
		setPreviewUrl( '' );
		clearVisualInput( input );
	};

	const hasImage = Number.parseInt( imageId, 10 ) > 0;

	const displayedImageValue = hasImage
		? __( 'Change image', 'woocommerce' )
		: __( 'Select an image', 'woocommerce' );

	return (
		<>
			<button
				ref={ triggerRef }
				type="button"
				className="wc-admin-visual-attribute-image-picker-trigger"
				onClick={ openMediaLibrary }
				aria-haspopup="dialog"
				aria-expanded={ isMediaFrameOpen }
			>
				<span
					className={ `wc-admin-image-swatch${
						previewUrl ? '' : ' is-empty'
					}` }
					aria-hidden="true"
				>
					{ previewUrl ? <img src={ previewUrl } alt="" /> : null }
				</span>
				<span>{ displayedImageValue }</span>
			</button>
			{ hasImage && (
				<button
					type="button"
					className="button-link wc-admin-visual-attribute-image-picker-clear"
					onClick={ clearImage }
				>
					{ __( 'Clear', 'woocommerce' ) }
				</button>
			) }
		</>
	);
};

export const mountImagePicker = ( input: HTMLInputElement ) => {
	if ( input.dataset.wcImagePickerMounted === '1' ) {
		return;
	}

	input.dataset.wcImagePickerMounted = '1';
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
	root.render( <ImageField input={ input } /> );

	const associatedLabels = input.labels ? Array.from( input.labels ) : [];
	associatedLabels.forEach( ( labelElement ) => {
		labelElement.addEventListener( 'click', ( event ) => {
			event.preventDefault();

			const trigger = wrapper.querySelector< HTMLButtonElement >(
				'.wc-admin-visual-attribute-image-picker-trigger'
			);

			trigger?.click();
		} );
	} );
};

export const mountAllImagePickers = ( context: ParentNode = document ) => {
	const imageInputs = context.querySelectorAll( IMAGE_INPUT_SELECTOR );

	imageInputs.forEach( ( inputElement ) => {
		if ( inputElement instanceof HTMLInputElement ) {
			mountImagePicker( inputElement );
		}
	} );
};
