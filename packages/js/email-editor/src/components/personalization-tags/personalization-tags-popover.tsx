/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
import { Popover, Button, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

type PersonalizationTagsPopoverProps = {
	contentRef: React.RefObject< HTMLElement >;
	onUpdate: ( originalValue: string, updatedValue: string ) => void;
};

/**
 * Component to display a popover with a text control to update personalization tags.
 * The popover is displayed when a user clicks on a personalization tag in the editor.
 *
 * @param root0
 * @param root0.contentRef Reference to the container where the popover should be displayed
 * @param root0.onUpdate   Callback to update the personalization tag
 */
const PersonalizationTagsPopover = ( {
	contentRef,
	onUpdate,
}: PersonalizationTagsPopoverProps ) => {
	const [ isPopoverVisible, setIsPopoverVisible ] = useState( false );
	const [ anchor, setAnchor ] = useState< HTMLElement | null >( null );
	const [ updatedValue, setUpdatedValue ] = useState( '' );
	const [ originalValue, setOriginalValue ] = useState( '' );

	useEffect( () => {
		if ( ! contentRef || ! contentRef.current ) {
			return undefined;
		}

		const container = contentRef.current;

		// Handle clicks within the referenced container
		const handleContainerClick = ( event: Event ) => {
			const target = event.target as HTMLElement;
			const commentSpan = target.closest(
				'span[data-rich-text-comment]'
			) as HTMLElement;

			if ( commentSpan ) {
				// Remove brackets from the text content for better user experience
				const textContent = commentSpan.innerText.replace(
					/^\[|\]$/g,
					''
				);
				setOriginalValue( textContent );
				setUpdatedValue( textContent );
				setAnchor( commentSpan );
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
			{ isPopoverVisible && anchor && (
				<Popover
					position="bottom right"
					onClose={ () => setIsPopoverVisible( false ) }
					anchor={ anchor } // Directly use commentSpan as the anchor
					className="woocommerce-personalization-tag-popover"
				>
					<div className="woocommerce-personalization-tag-popover-content">
						<TextControl
							label={ __( 'Personalization Tag', 'woocommerce' ) }
							value={ updatedValue }
							onChange={ ( value ) => setUpdatedValue( value ) }
							__nextHasNoMarginBottom // To avoid warning about deprecation in console
							__next40pxDefaultSize
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
									onUpdate( originalValue, updatedValue );
									setIsPopoverVisible( false );
								} }
							>
								{ __( 'Update', 'woocommerce' ) }
							</Button>
						</div>
					</div>
				</Popover>
			) }
		</>
	);
};

export { PersonalizationTagsPopover };
