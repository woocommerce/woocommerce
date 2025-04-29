/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import {
	Popover,
	Button,
	TextControl,
	SelectControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { storeName } from '../../store';

type PersonalizationTagsLinkPopoverProps = {
	contentRef: React.RefObject< HTMLElement >;
	onUpdate: (
		htmlElement: HTMLElement,
		newTag: string,
		newText: string
	) => void;
};
const PersonalizationTagsLinkPopover = ( {
	contentRef,
	onUpdate,
}: PersonalizationTagsLinkPopoverProps ) => {
	const [ isPopoverVisible, setIsPopoverVisible ] = useState( false );
	const [ linkElement, setLinkElement ] = useState< HTMLElement | null >(
		null
	);
	const [ linkText, setLinkText ] = useState( '' );
	const [ linkHref, setLinkHref ] = useState( '' );

	const list = useSelect(
		( select ) => select( storeName ).getPersonalizationTagsList(),
		[]
	);

	useEffect( () => {
		if ( ! contentRef || ! contentRef.current ) {
			return undefined;
		}

		const container = contentRef.current;

		// Handle clicks within the referenced container
		const handleContainerClick = ( event: Event ) => {
			const target = event.target as HTMLElement;
			const element = target.closest(
				'a[data-link-href]'
			) as HTMLElement;

			if ( element ) {
				// Remove brackets from the text content for better user experience
				setLinkElement( element );
				setLinkHref( element.getAttribute( 'data-link-href' ) || '' );
				setLinkText( element.textContent || '' );
				setIsPopoverVisible( true );
			}
		};

		// Add the event listener to the container
		container.addEventListener( 'click', handleContainerClick );

		// Cleanup function to remove the event listener on unmount
		return () => {
			container.removeEventListener( 'click', handleContainerClick );
		};
	}, [ contentRef ] );

	return (
		<>
			{ isPopoverVisible && linkElement && (
				<Popover
					position="bottom left"
					onClose={ () => setIsPopoverVisible( false ) }
					anchor={ linkElement } // Directly use commentSpan as the anchor
					className="woocommerce-personalization-tag-popover"
				>
					<div className="woocommerce-personalization-tag-popover-content">
						<TextControl
							label={ __( 'Link Text', 'woocommerce' ) }
							value={ linkText }
							onChange={ ( value ) => setLinkText( value ) }
							__nextHasNoMarginBottom // To avoid warning about deprecation in console
							__next40pxDefaultSize
							autoComplete="off"
						/>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Link tag', 'woocommerce' ) }
							value={ linkHref }
							onChange={ ( value ) => {
								setLinkHref( value );
							} }
							options={ list
								.filter( ( tag ) => {
									return (
										tag.category ===
										__( 'Link', 'woocommerce' )
									);
								} )
								.map( ( tag ) => {
									return {
										label: tag.name,
										value: tag.token,
									};
								} ) }
						/>
						<div className="woocommerce-personalization-tag-popover-content-buttons">
							<Button
								isTertiary
								onClick={ () => {
									setIsPopoverVisible( false );
								} }
							>
								{ __( 'Cancel', 'woocommerce' ) }
							</Button>
							<Button
								isPrimary
								onClick={ () => {
									setIsPopoverVisible( false );
									onUpdate( linkElement, linkHref, linkText );
								} }
							>
								{ __( 'Update link', 'woocommerce' ) }
							</Button>
						</div>
					</div>
				</Popover>
			) }
		</>
	);
};

export { PersonalizationTagsLinkPopover };
