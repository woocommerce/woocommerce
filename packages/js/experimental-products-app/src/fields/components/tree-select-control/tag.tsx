/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { forwardRef, Fragment, type Ref } from '@wordpress/element';
import { decodeEntities } from '@wordpress/html-entities';
import { useInstanceId } from '@wordpress/compose';
import { closeSmall } from '@wordpress/icons';
import { VisuallyHidden, IconButton } from '@wordpress/ui';
import clsx from 'clsx';

/**
 * Internal dependencies
 */

type Props = {
	/** The name for this item, displayed as the tag's text. */
	label: string;
	/** A unique ID for this item. This is used to identify the item when the remove button is clicked. */
	id?: number | string;
	/** A function called when the remove X is clicked. If not used, no X icon will display.*/
	remove: (
		id: number | string | undefined
	) => React.MouseEventHandler< HTMLButtonElement >;
	/** A more descriptive label for screen reader users. Defaults to the `name` prop. */
	screenReaderLabel?: string;
	/** Whether the tag can be removed. Defaults to true. */
	removable?: boolean;
	/** A function called when the tag is clicked. */
	onClick?: () => void;
};

const Tag = forwardRef(
	(
		{
			id,
			label,
			remove,
			screenReaderLabel,
			removable = true,
			onClick,
		}: Props,
		removeButtonRef: Ref< HTMLButtonElement >
	) => {
		const instanceId = useInstanceId( Tag ).toString();
		const labelId = `tag__label-${ instanceId }`;

		screenReaderLabel = screenReaderLabel || label;
		if ( ! label ) {
			// A null label probably means something went wrong
			// @todo Maybe this should be a loading indicator?
			return null;
		}
		label = decodeEntities( label );

		const tagClassName = clsx( 'woocommerce-tree-select-control__tag', {
			'woocommerce-tree-select-control__tag--non-removable': ! removable,
		} );

		const handleClick = onClick
			? ( e: React.MouseEvent ) => {
					// Don't trigger if clicking the remove button
					if (
						e.target instanceof HTMLElement &&
						e.target.closest( 'button' )
					) {
						return;
					}
					onClick();
			  }
			: undefined;

		return (
			<span
				className={ tagClassName }
				onClick={ handleClick }
				role={ onClick ? 'button' : undefined }
				tabIndex={ onClick ? 0 : undefined }
				onKeyDown={
					onClick
						? ( e ) => {
								if ( e.key === 'Enter' || e.key === ' ' ) {
									onClick();
								}
						  }
						: undefined
				}
			>
				<Fragment>
					<VisuallyHidden>{ screenReaderLabel }</VisuallyHidden>
					<span aria-hidden="true">{ label }</span>
				</Fragment>
				{ removable && (
					<IconButton
						icon={ closeSmall }
						size="small"
						variant="minimal"
						tone="neutral"
						label={ sprintf(
							// translators: %s is the name of the tag being removed.
							__( 'Remove %s', 'woocommerce' ),
							label
						) }
						aria-describedby={ labelId }
						ref={ removeButtonRef }
						onClick={ remove( id ) }
					/>
				) }
			</span>
		);
	}
);

export default Tag;
