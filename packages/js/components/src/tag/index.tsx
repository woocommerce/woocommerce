/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	createElement,
	forwardRef,
	Fragment,
	useState,
} from '@wordpress/element';
import classnames from 'classnames';
import { Button, Popover } from '@wordpress/components';
import { Icon, closeSmall } from '@wordpress/icons';
import { decodeEntities } from '@wordpress/html-entities';
import { Ref } from 'react';
import { useInstanceId } from '@wordpress/compose';

type Props = {
	/** The name for this item, displayed as the tag's text. */
	label: string;
	/** A unique ID for this item. This is used to identify the item when the remove button is clicked. */
	id?: number | string;
	/** Contents to display on click in a popover */
	popoverContents?: React.ReactNode;
	/** A function called when the remove X is clicked. If not used, no X icon will display.*/
	remove?: (
		id: number | string | undefined
	) => React.MouseEventHandler< HTMLButtonElement >;
	/** A more descriptive label for screen reader users. Defaults to the `name` prop. */
	screenReaderLabel?: string;
	/** Additional CSS classes. */
	className?: string;
};

const Tag = forwardRef(
	(
		{
			id,
			label,
			popoverContents,
			remove,
			screenReaderLabel,
			className,
		}: Props,
		removeButtonRef: Ref< HTMLButtonElement >
	) => {
		const [ isVisible, setIsVisible ] = useState( false );

		const instanceId = useInstanceId( Tag ) as string;

		screenReaderLabel = screenReaderLabel || label;
		if ( ! label ) {
			// A null label probably means something went wrong
			// @todo Maybe this should be a loading indicator?
			return null;
		}
		label = decodeEntities( label );
		const classes = classnames( 'woocommerce-tag', className, {
			'has-remove': !! remove,
		} );
		const labelId = `woocommerce-tag__label-${ instanceId }`;
		const labelTextNode = (
			<Fragment>
				<span className="screen-reader-text">
					{ screenReaderLabel }
				</span>
				<span aria-hidden="true">{ label }</span>
			</Fragment>
		);

		return (
			<span className={ classes }>
				{ popoverContents ? (
					<Button
						className="woocommerce-tag__text"
						id={ labelId }
						onClick={ () => setIsVisible( true ) }
					>
						{ labelTextNode }
					</Button>
				) : (
					<span className="woocommerce-tag__text" id={ labelId }>
						{ labelTextNode }
					</span>
				) }
				{ popoverContents && isVisible && (
					<Popover onClose={ () => setIsVisible( false ) }>
						{ popoverContents }
					</Popover>
				) }
				{ remove && (
					<Button
						className="woocommerce-tag__remove"
						ref={ removeButtonRef }
						onClick={ remove( id ) }
						label={ sprintf(
							// translators: %s is the name of the tag being removed.
							__( 'Remove %s', 'woocommerce' ),
							label
						) }
						aria-describedby={ labelId }
					>
						<Icon
							icon={ closeSmall }
							size={ 20 }
							className="clear-icon"
						/>
					</Button>
				) }
			</span>
		);
	}
);

export default Tag;
