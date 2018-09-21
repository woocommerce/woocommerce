/** @format */
/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { Component } from '@wordpress/element';
import classnames from 'classnames';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';
import { H } from 'components/section';
import ImageAsset from 'components/image-asset';

/**
 * A component to be used when there is no data to show.
 * It can be used as an opportunity to provide explanation or guidance to help a user progress.
 */
class EmptyContent extends Component {
	renderIllustration() {
		const { illustrationWidth, illustrationHeight, illustration } = this.props;
		return (
			<ImageAsset
				alt=""
				src={ illustration }
				width={ illustrationWidth }
				height={ illustrationHeight }
				className="woocommerce-empty-content__illustration"
			/>
		);
	}

	renderActionButtons( type ) {
		const actionLabel =
			'secondary' === type ? this.props.secondaryActionLabel : this.props.actionLabel;
		const actionURL = 'secondary' === type ? this.props.secondaryActionURL : this.props.actionURL;
		const actionCallback =
			'secondary' === type ? this.props.secondaryActionCallback : this.props.actionCallback;

		const isPrimary = 'secondary' === type ? false : true;

		if ( actionURL && actionCallback ) {
			return (
				<Button
					className="woocommerce-empty-content__action"
					isPrimary={ isPrimary }
					onClick={ actionCallback }
					href={ actionURL }
				>
					{ actionLabel }
				</Button>
			);
		} else if ( actionURL ) {
			return (
				<Button
					className="woocommerce-empty-content__action"
					isPrimary={ isPrimary }
					href={ actionURL }
				>
					{ actionLabel }
				</Button>
			);
		} else if ( actionCallback ) {
			return (
				<Button
					className="woocommerce-empty-content__action"
					isPrimary={ isPrimary }
					onClick={ actionCallback }
				>
					{ actionLabel }
				</Button>
			);
		}

		return null;
	}

	renderActions() {
		const { actionLabel, secondaryActionLabel } = this.props;
		return (
			<div className="woocommerce-empty-content__actions">
				{ actionLabel && this.renderActionButtons( 'primary' ) }
				{ secondaryActionLabel && this.renderActionButtons( 'secondary' ) }
			</div>
		);
	}

	render() {
		const { title, message, illustration } = this.props;
		return (
			<div className={ classnames( 'woocommerce-empty-content', this.props.className ) }>
				{ illustration && this.renderIllustration() }
				{ title ? <H className="woocommerce-empty-content__title">{ title }</H> : null }
				{ message ? <p className="woocommerce-empty-content__message">{ message }</p> : null }

				{ this.renderActions() }
			</div>
		);
	}
}

EmptyContent.propTypes = {
	/**
	 * The title to be displayed.
	 */
	title: PropTypes.string.isRequired,
	/**
	 * An additional message to be displayed.
	 */
	message: PropTypes.string,
	/**
	 * The url string of an image path. Prefix with `/` to load an image relative to the plugin directory.
	 */
	illustration: PropTypes.string,
	/**
	 * Height to use for the illustration.
	 */
	illustrationHeight: PropTypes.number,
	/**
	 * Width to use for the illustration.
	 */
	illustrationWidth: PropTypes.number,
	/**
	 * Label to be used for the primary action button.
	 */
	actionLabel: PropTypes.string.isRequired,
	/**
	 * URL to be used for the primary action button.
	 */
	actionURL: PropTypes.string,
	/**
	 * Callback to be used for the primary action button.
	 */
	actionCallback: PropTypes.func,
	/**
	 * Label to be used for the secondary action button.
	 */
	secondaryActionLabel: PropTypes.string,
	/**
	 * URL to be used for the secondary action button.
	 */
	secondaryActionURL: PropTypes.string,
	/**
	 * Callback to be used for the secondary action button.
	 */
	secondaryActionCallback: PropTypes.func,
	/**
	 * Additional CSS classes.
	 */
	className: PropTypes.string,
};

EmptyContent.defaultProps = {
	illustration: '/empty-content.svg',
	illustrationHeight: 400,
	illustrationWidth: 400,
};

export default EmptyContent;
