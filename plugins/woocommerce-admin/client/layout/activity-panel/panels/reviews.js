/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import Gridicon from 'gridicons';

/**
 * Internal dependencies
 */
import ActivityHeader from '../activity-header';
import SplitButton from 'components/split-button';

class ReviewsPanel extends Component {
	render() {
		return (
			<Fragment>
				<ActivityHeader title={ __( 'Reviews', 'wc-admin' ) } />

				<SplitButton
					isPrimary
					mainLabel="Primary Button"
					menuLabel="Select an action"
					onClick={ () => alert( 'Primary Main Action clicked' ) }
					controls={ [
						{
							label: 'Up',
							onClick: () => alert( 'Primary Up clicked' ),
						},
						{
							label: 'Right',
							onClick: () => alert( 'Primary Right clicked' ),
						},
						{
							label: 'Down',
							icon: <Gridicon icon="arrow-down" />,
							onClick: () => alert( 'Primary Down clicked' ),
						},
						{
							label: 'Left',
							icon: <Gridicon icon="arrow-left" />,
							onClick: () => alert( 'Primary Left clicked' ),
						},
					] }
				/>

				<SplitButton
					mainIcon={ <Gridicon icon="pencil" /> }
					mainLabel="Secondary Button"
					menuLabel="Select an action"
					onClick={ () => alert( 'Secondary Main Action clicked' ) }
					controls={ [
						{
							label: 'Up',
							icon: <Gridicon icon="arrow-up" />,
							onClick: () => alert( 'Secondary Up clicked' ),
						},
						{
							label: 'Right',
							onClick: () => alert( 'Secondary Right clicked' ),
						},
						{
							label: 'Down',
							icon: <Gridicon icon="arrow-down" />,
							onClick: () => alert( 'Secondary Down clicked' ),
						},
						{
							icon: <Gridicon icon="arrow-left" />,
							onClick: () => alert( 'Secondary Left clicked' ),
						},
					] }
				/>

				<SplitButton
					mainIcon={ <Gridicon icon="pencil" /> }
					menuLabel="Select an action"
					onClick={ () => alert( 'Icon Only Action clicked' ) }
					controls={ [
						{
							label: 'Up',
							icon: <Gridicon icon="arrow-up" />,
							onClick: () => alert( 'Icon Only Up clicked' ),
						},
						{
							label: 'Right',
							onClick: () => alert( 'Icon Only Right clicked' ),
						},
						{
							label: 'Down',
							icon: <Gridicon icon="arrow-down" />,
							onClick: () => alert( 'Icon Only Down clicked' ),
						},
						{
							icon: <Gridicon icon="arrow-left" />,
							onClick: () => alert( 'Icon Only Left clicked' ),
						},
					] }
				/>
			</Fragment>
		);
	}
}

export default ReviewsPanel;
