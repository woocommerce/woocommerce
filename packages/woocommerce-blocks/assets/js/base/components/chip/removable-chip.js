/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { __, sprintf } from '@wordpress/i18n';
import { Icon, noAlt } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import { Chip } from './index.js';

/**
 * Component used to render a "chip" -- an item containing some text with
 * an X button to remove/dismiss each chip.
 */
const RemovableChip = ( {
	ariaLabel = '',
	className = '',
	disabled = false,
	onRemove = () => void null,
	removeOnAnyClick = false,
	text,
	screenReaderText = '',
	...props
} ) => {
	const RemoveElement = removeOnAnyClick ? 'span' : 'button';

	if ( ! ariaLabel ) {
		const ariaLabelText =
			screenReaderText && typeof screenReaderText === 'string'
				? screenReaderText
				: text;
		ariaLabel =
			typeof ariaLabelText !== 'string'
				? /* translators: Remove chip. */
				  __( 'Remove', 'woocommerce' )
				: sprintf(
						/* translators: %s text of the chip to remove. */
						__( 'Remove "%s"', 'woocommerce' ),
						ariaLabelText
				  );
	}

	const clickableElementProps = {
		'aria-label': ariaLabel,
		disabled,
		onClick: onRemove,
		onKeyDown: ( e ) => {
			if ( e.key === 'Backspace' || e.key === 'Delete' ) {
				onRemove();
			}
		},
	};

	const chipProps = removeOnAnyClick ? clickableElementProps : {};
	const removeProps = removeOnAnyClick
		? { 'aria-hidden': true }
		: clickableElementProps;

	return (
		<Chip
			{ ...props }
			{ ...chipProps }
			className={ classNames( className, 'is-removable' ) }
			element={ removeOnAnyClick ? 'button' : props.element }
			screenReaderText={ screenReaderText }
			text={ text }
		>
			<RemoveElement
				className="wc-block-components-chip__remove"
				{ ...removeProps }
			>
				<Icon
					className="wc-block-components-chip__remove-icon"
					srcElement={ noAlt }
					size={ 16 }
				/>
			</RemoveElement>
		</Chip>
	);
};

RemovableChip.propTypes = {
	text: PropTypes.node.isRequired,
	ariaLabel: PropTypes.string,
	className: PropTypes.string,
	disabled: PropTypes.bool,
	onRemove: PropTypes.func,
	removeOnAnyClick: PropTypes.bool,
	screenReaderText: PropTypes.string,
};

export default RemovableChip;
