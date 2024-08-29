/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { Label, RemovableChip } from '@woocommerce/blocks-components';
import { Icon, closeSmall } from '@wordpress/icons';

interface RemovableListItemProps {
	type: string;
	name: string;
	prefix?: string | JSX.Element;
	showLabel?: boolean;
	isLoading?: boolean;
	displayStyle: string;
	removeCallback?: () => void;
}

export const RemovableListItem = ( {
	type,
	name,
	prefix = '',
	removeCallback = () => null,
	showLabel = true,
	displayStyle,
}: RemovableListItemProps ) => {
	const prefixedName = prefix ? (
		<>
			{ prefix }
			&nbsp;
			{ name }
		</>
	) : (
		name
	);
	const removeText = sprintf(
		/* translators: %s attribute value used in the filter. For example: yellow, green, small, large. */
		__( 'Remove %s filter', 'woocommerce' ),
		name
	);

	return (
		<li className="list-item" key={ `${ type }: ${ name }` }>
			{ showLabel && (
				<span className="list-item-type">{ `${ type }: ` }</span>
			) }
			{ displayStyle === 'chips' ? (
				<RemovableChip
					element="span"
					text={ prefixedName }
					onRemove={ removeCallback }
					radius="large"
					ariaLabel={ removeText }
				/>
			) : (
				<span className="list-item-name">
					<button
						className="list-item-remove"
						onClick={ removeCallback }
					>
						<Icon
							className="wc-block-components-chip__remove-icon"
							icon={ closeSmall }
							size={ 16 }
						/>
						<Label screenReaderLabel={ removeText } />
					</button>
					{ prefixedName }
				</span>
			) }
		</li>
	);
};
