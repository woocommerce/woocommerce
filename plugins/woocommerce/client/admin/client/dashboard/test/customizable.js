/**
 * External dependencies
 */
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Button } from '@wordpress/components';
import { Component, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import CustomizableDashboard from '../customizable';

let mockPreferenceSeed;
let mockPreferencePayloads;

const mockUseUserPreferences = jest.fn( () => {
	const [ preferences, setPreferences ] = useState( () => ( {
		dashboard_sections: mockPreferenceSeed
			? mockPreferenceSeed.map( ( section ) => ( { ...section } ) )
			: undefined,
	} ) );

	return {
		...preferences,
		updateUserPreferences: ( payload ) => {
			mockPreferencePayloads.push(
				payload.dashboard_sections
					? {
							dashboard_sections: payload.dashboard_sections.map(
								( { key, isVisible } ) => ( {
									key,
									isVisible,
								} )
							),
					  }
					: { ...payload }
			);
			setPreferences( ( current ) => ( { ...current, ...payload } ) );
		},
	};
} );

const mockSelect = jest.fn( () => ( {
	getSetting: () => ( {
		woocommerce_default_date_range: 'period=month&compare=previous_year',
	} ),
} ) );

const mockReportBody = class extends Component {
	state = { isOpen: false };

	render() {
		const {
			controls: Controls,
			isFirst,
			isLast,
			onMove,
			onRemove,
			onTitleBlur,
			onTitleChange,
			title,
			titleInput,
		} = this.props;
		const { isOpen } = this.state;
		const onToggle = () => this.setState( { isOpen: ! isOpen } );

		return (
			<section aria-label={ `${ title } dashboard section` }>
				<h2>{ title }</h2>
				<Button
					aria-expanded={ isOpen }
					aria-label={ `${ title } section options` }
					onClick={ onToggle }
				>
					{ title } options
				</Button>
				{ isOpen && (
					<div role="menu">
						<Controls
							isFirst={ isFirst }
							isLast={ isLast }
							onMove={ onMove }
							onRemove={ onRemove }
							onTitleBlur={ onTitleBlur }
							onTitleChange={ onTitleChange }
							onToggle={ onToggle }
							titleInput={ titleInput }
						/>
					</div>
				) }
			</section>
		);
	}
};

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	withSelect: ( mapSelect ) => ( WrappedComponent ) => ( props ) => (
		<WrappedComponent { ...props } { ...mapSelect( mockSelect, props ) } />
	),
} ) );

jest.mock( '@woocommerce/data', () => ( {
	settingsStore: 'settingsStore',
	useUserPreferences: () => mockUseUserPreferences(),
} ) );

// Load only the real component modules used by the dashboard owner. Importing
// the package barrel eagerly loads unrelated search code that is not involved
// in section preference behavior.
jest.mock( '@woocommerce/components', () => ( {
	H: jest.requireActual( '@woocommerce/components/section/header' ).H,
	MenuItem: jest.requireActual(
		'@woocommerce/components/ellipsis-menu/menu-item'
	).default,
	Spinner: () => null,
} ) );

jest.mock( '@woocommerce/currency', () => {
	const { createContext } = jest.requireActual( '@wordpress/element' );

	return {
		CurrencyContext: createContext( {} ),
		getFilteredCurrencyInstance: () => ( {} ),
	};
} );

jest.mock( '@woocommerce/navigation', () => ( {
	getQuery: () => ( {} ),
} ) );

jest.mock( '@woocommerce/tracks', () => ( {
	recordEvent: jest.fn(),
} ) );

jest.mock( '../../analytics/components/report-header', () => ( {
	ReportHeader: () => null,
} ) );

jest.mock( '../dashboard-charts', () => ( {
	__esModule: true,
	default: mockReportBody,
} ) );

jest.mock( '../leaderboards', () => ( {
	__esModule: true,
	default: mockReportBody,
} ) );

jest.mock( '../store-performance', () => ( {
	__esModule: true,
	default: mockReportBody,
} ) );

const renderDashboard = ( preferenceSeed ) => {
	mockPreferenceSeed = preferenceSeed;

	return render(
		<CustomizableDashboard path="/analytics/overview" query={ {} } />
	);
};

const getVisibleSectionTitles = async () => {
	const headings = await screen.findAllByRole( 'heading', { level: 2 } );

	return headings.map( ( heading ) => heading.textContent );
};

const openSectionOptions = async ( title ) => {
	await userEvent.click(
		await screen.findByRole( 'button', {
			name: `${ title } section options`,
		} )
	);
};

const invokeSectionAction = async ( title, action ) => {
	await openSectionOptions( title );
	await userEvent.click(
		await screen.findByRole( 'menuitem', { name: new RegExp( action ) } )
	);
};

const getLastPreferencePayload = () =>
	mockPreferencePayloads[ mockPreferencePayloads.length - 1 ];

describe( 'CustomizableDashboard section preferences', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockPreferenceSeed = undefined;
		mockPreferencePayloads = [];
	} );

	it( 'renders Performance, Charts, and Leaderboards in default order', async () => {
		renderDashboard();

		expect( await getVisibleSectionTitles() ).toEqual( [
			'Performance',
			'Charts',
			'Leaderboards',
		] );
	} );

	it( 'hides move up for first and move down for last visible section', async () => {
		renderDashboard();

		await openSectionOptions( 'Performance' );
		expect(
			screen.queryByRole( 'menuitem', { name: /Move up/ } )
		).not.toBeInTheDocument();
		expect(
			screen.getByRole( 'menuitem', { name: /Move down/ } )
		).toBeInTheDocument();

		await userEvent.click(
			screen.getByRole( 'button', {
				name: 'Performance section options',
			} )
		);
		await openSectionOptions( 'Leaderboards' );
		expect(
			screen.getByRole( 'menuitem', { name: /Move up/ } )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'menuitem', { name: /Move down/ } )
		).not.toBeInTheDocument();
	} );

	it( 'moves the first section down and saves the ordered preference payload', async () => {
		renderDashboard();

		await invokeSectionAction( 'Performance', 'Move down' );

		expect( getLastPreferencePayload() ).toEqual( {
			dashboard_sections: [
				{ key: 'charts', isVisible: true },
				{ key: 'store-performance', isVisible: true },
				{ key: 'leaderboards', isVisible: true },
			],
		} );
	} );

	it( 'moves the first section down in the dashboard', async () => {
		renderDashboard();

		await invokeSectionAction( 'Performance', 'Move down' );

		expect( await getVisibleSectionTitles() ).toEqual( [
			'Charts',
			'Performance',
			'Leaderboards',
		] );
	} );

	it( 'moves the second section up and saves the ordered preference payload', async () => {
		renderDashboard();

		await invokeSectionAction( 'Charts', 'Move up' );

		expect( getLastPreferencePayload() ).toEqual( {
			dashboard_sections: [
				{ key: 'charts', isVisible: true },
				{ key: 'store-performance', isVisible: true },
				{ key: 'leaderboards', isVisible: true },
			],
		} );
	} );

	it( 'moves the second section up in the dashboard', async () => {
		renderDashboard();

		await invokeSectionAction( 'Charts', 'Move up' );

		expect( await getVisibleSectionTitles() ).toEqual( [
			'Charts',
			'Performance',
			'Leaderboards',
		] );
	} );

	it( 'removes Performance and saves it hidden at the end', async () => {
		renderDashboard();

		await invokeSectionAction( 'Performance', 'Remove section' );

		expect( getLastPreferencePayload() ).toEqual( {
			dashboard_sections: [
				{ key: 'charts', isVisible: true },
				{ key: 'leaderboards', isVisible: true },
				{ key: 'store-performance', isVisible: false },
			],
		} );
	} );

	it( 'removes Performance from the dashboard', async () => {
		renderDashboard();

		await invokeSectionAction( 'Performance', 'Remove section' );

		expect( await getVisibleSectionTitles() ).toEqual( [
			'Charts',
			'Leaderboards',
		] );
	} );

	it( 'adds hidden Performance back and saves it visible at the end', async () => {
		renderDashboard( [
			{ key: 'charts', isVisible: true },
			{ key: 'leaderboards', isVisible: true },
			{ key: 'store-performance', isVisible: false },
		] );

		await userEvent.click(
			await screen.findByRole( 'button', { name: 'Add more sections' } )
		);
		const choices = await screen.findByRole( 'heading', {
			name: 'Dashboard Sections',
		} );
		await userEvent.click(
			within( choices.parentElement ).getByRole( 'button', {
				name: 'Performance',
			} )
		);

		expect( getLastPreferencePayload() ).toEqual( {
			dashboard_sections: [
				{ key: 'charts', isVisible: true },
				{ key: 'leaderboards', isVisible: true },
				{ key: 'store-performance', isVisible: true },
			],
		} );
	} );

	it( 'adds hidden Performance back to the dashboard', async () => {
		renderDashboard( [
			{ key: 'charts', isVisible: true },
			{ key: 'leaderboards', isVisible: true },
			{ key: 'store-performance', isVisible: false },
		] );

		await userEvent.click(
			await screen.findByRole( 'button', { name: 'Add more sections' } )
		);
		const choices = await screen.findByRole( 'heading', {
			name: 'Dashboard Sections',
		} );
		await userEvent.click(
			within( choices.parentElement ).getByRole( 'button', {
				name: 'Performance',
			} )
		);
		await screen.findByRole( 'heading', { name: 'Performance' } );

		expect( await getVisibleSectionTitles() ).toEqual( [
			'Charts',
			'Leaderboards',
			'Performance',
		] );
	} );
} );
