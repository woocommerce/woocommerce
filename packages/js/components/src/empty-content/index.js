/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { createElement, Component } from '@wordpress/element';
import classnames from 'classnames';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import { H } from '../section';

/**
 * A component to be used when there is no data to show.
 * It can be used as an opportunity to provide explanation or guidance to help a user progress.
 */
class EmptyContent extends Component {
	renderIllustration() {
		const { illustrationWidth, illustrationHeight, illustration } =
			this.props;
		return (
			<img
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
			type === 'secondary'
				? this.props.secondaryActionLabel
				: this.props.actionLabel;
		const actionURL =
			type === 'secondary'
				? this.props.secondaryActionURL
				: this.props.actionURL;
		const actionCallback =
			type === 'secondary'
				? this.props.secondaryActionCallback
				: this.props.actionCallback;

		const isPrimary = type === 'secondary' ? false : true;

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
				{ secondaryActionLabel &&
					this.renderActionButtons( 'secondary' ) }
			</div>
		);
	}

	render() {
		const { className, title, message, illustration } = this.props;
		return (
			<div
				className={ classnames(
					'woocommerce-empty-content',
					className
				) }
			>
				{ illustration && this.renderIllustration() }
				{ title ? (
					<H className="woocommerce-empty-content__title">
						{ title }
					</H>
				) : null }
				{ message ? (
					<p className="woocommerce-empty-content__message">
						{ message }
					</p>
				) : null }

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
	message: PropTypes.node,
	/**
	 * The url string of an image path for img src.
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
	// eslint-disable-next-line max-len
	illustration:
		'data:image/svg+xml;utf8,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400"%3E%3Cpath d="M226.153073,88.3099993 L355.380187,301.446227 C363.970299,315.614028 359.448689,334.062961 345.280888,342.653073 C340.591108,345.496544 335.21158,347 329.727115,347 L71.2728854,347 C54.7043429,347 41.2728854,333.568542 41.2728854,317 C41.2728854,311.515534 42.7763415,306.136007 45.6198127,301.446227 L174.846927,88.3099993 C183.437039,74.1421985 201.885972,69.6205881 216.053773,78.2106999 C220.184157,80.7150022 223.64877,84.1796157 226.153073,88.3099993 Z M184.370159,153 L186.899684,255.024156 L213.459691,255.024156 L215.989216,153 L184.370159,153 Z M200.179688,307.722584 C209.770801,307.722584 217.359375,300.450201 217.359375,291.175278 C217.359375,281.900355 209.770801,274.627972 200.179688,274.627972 C190.588574,274.627972 183,281.900355 183,291.175278 C183,300.450201 190.588574,307.722584 200.179688,307.722584 Z" id="Combined-Shape" stroke="%23979797" fill="%2395588A" fill-rule="nonzero"%3E%3C/path%3E%3C/svg%3E',
	illustrationWidth: 400,
};

export default EmptyContent;
