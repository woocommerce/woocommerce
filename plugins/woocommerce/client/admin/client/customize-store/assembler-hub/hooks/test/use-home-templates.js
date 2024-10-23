/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import { useHomeTemplates, getTemplatePatterns } from '../use-home-templates';
import { usePatterns } from '../use-patterns';

// Mocking the dependent hooks and data
jest.mock( '../use-patterns' );
jest.mock( '~/customize-store/data/homepageTemplates', () => ( {
	HOMEPAGE_TEMPLATES: {
		template1: { blocks: [ 'content1', 'content2' ] },
		template2: { blocks: [ 'content3' ] },
	},
} ) );

const mockUsePatterns = usePatterns;

const mockPatternsByName = {
	content1: { name: 'content1', content: '<div>Content1</div>' },
	content2: { name: 'content2', content: '<div>Content2</div>' },
	content3: { name: 'content3', content: '<div>Content3</div>' },
};

describe( 'useHomeTemplates', () => {
	beforeEach( () => {
		mockUsePatterns.mockReturnValue( {
			blockPatterns: Object.values( mockPatternsByName ),
			isLoading: false,
		} );
	} );

	it( 'should return home templates without first and last items', () => {
		const { result } = renderHook( () => useHomeTemplates() );

		// The expected result based on the HOMEPAGE_TEMPLATES and mock patterns
		const expectedResult = {
			template1: getTemplatePatterns(
				[ 'content1', 'content2' ],
				mockPatternsByName
			),
			template2: getTemplatePatterns(
				[ 'content3' ],
				mockPatternsByName
			),
		};

		expect( result.current.homeTemplates ).toEqual( expectedResult );
	} );
} );
