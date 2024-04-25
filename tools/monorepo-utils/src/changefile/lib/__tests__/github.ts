/**
 * Internal dependencies
 */
import {
	shouldAutomateChangelog,
	getChangelogSignificance,
	getChangelogType,
	getChangelogDetails,
	getChangelogDetailsError,
} from '../github';
import { Logger } from '../../../core/logger';

jest.mock( '../../../core/logger', () => {
	return {
		Logger: {
			error: jest.fn(),
		},
	};
} );

describe( 'shouldAutomateChangelog', () => {
	it( 'should return true when checked', () => {
		const body =
			'### Changelog entry\r\n' +
			'\r\n' +
			'<!-- You can optionally choose to enter a changelog entry by checking the box and supplying data. -->\r\n' +
			'\r\n' +
			'- [x] Automatically create a changelog entry from the details below.\r\n' +
			'\r\n';
		const shouldAutomate = shouldAutomateChangelog( body );
		expect( shouldAutomate ).toBe( true );
	} );

	it( 'should return true when checked with upper-case', () => {
		const body =
			'### Changelog entry\r\n' +
			'\r\n' +
			'<!-- You can optionally choose to enter a changelog entry by checking the box and supplying data. -->\r\n' +
			'\r\n' +
			'- [X] Automatically create a changelog entry from the details below.\r\n' +
			'\r\n';
		const shouldAutomate = shouldAutomateChangelog( body );
		expect( shouldAutomate ).toBe( true );
	} );

	it( 'should return false when unchecked', () => {
		const body =
			'### Changelog entry\r\n' +
			'\r\n' +
			'<!-- You can optionally choose to enter a changelog entry by checking the box and supplying data. -->\r\n' +
			'\r\n' +
			'- [ ] Automatically create a changelog entry from the details below.\r\n' +
			'\r\n';
		const shouldAutomate = shouldAutomateChangelog( body );
		expect( shouldAutomate ).toBe( false );
	} );

	it( 'should return false when missing from body', () => {
		const body = '';
		const shouldAutomate = shouldAutomateChangelog( body );
		expect( shouldAutomate ).toBe( false );
	} );
} );

describe( 'getChangelogSignificance', () => {
	it( 'should return the selected significance', () => {
		const body =
			'### Changelog entry\r\n' +
			'\r\n' +
			'<!-- You can optionally choose to enter a changelog entry by checking the box and supplying data. -->\r\n' +
			'\r\n' +
			'- [x] Automatically create a changelog entry from the details below.\r\n' +
			'\r\n' +
			'<details>\r\n' +
			'\r\n' +
			'#### Significance\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Patch\r\n' +
			'- [ ] Minor\r\n' +
			'- [ ] Major\r\n' +
			'\r\n' +
			'#### Type\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Fix - Fixes an existing bug\r\n' +
			'- [ ] Add - Adds functionality\r\n' +
			'- [ ] Update - Update existing functionality\r\n' +
			'- [ ] Dev - Development related task\r\n' +
			'- [ ] Tweak - A minor adjustment to the codebase\r\n' +
			'- [ ] Performance - Address performance issues\r\n' +
			'- [ ] Enhancement\r\n' +
			'\r\n' +
			'#### Message ' +
			'<!-- Add a changelog message here -->\r\n' +
			'This is a very useful fix.\r\n' +
			'\r\n' +
			'#### Comment ' +
			`<!-- If the changes in this pull request don't warrant a changelog entry, you can alternatively supply a comment here. Note that comments are only accepted with a significance of "Patch" -->\r\n` +
			'\r\n' +
			'</details>';

		const significance = getChangelogSignificance( body );
		expect( significance ).toBe( 'patch' );
	} );

	it( 'should error when no significance selected', () => {
		const body =
			'### Changelog entry\r\n' +
			'\r\n' +
			'<!-- You can optionally choose to enter a changelog entry by checking the box and supplying data. -->\r\n' +
			'\r\n' +
			'- [X] Automatically create a changelog entry from the details below.\r\n' +
			'\r\n' +
			'<details>\r\n' +
			'\r\n' +
			'#### Significance\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [ ] Patch\r\n' +
			'- [ ] Minor\r\n' +
			'- [ ] Major\r\n' +
			'\r\n' +
			'#### Type\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Fix - Fixes an existing bug\r\n' +
			'- [ ] Add - Adds functionality\r\n' +
			'- [ ] Update - Update existing functionality\r\n' +
			'- [ ] Dev - Development related task\r\n' +
			'- [ ] Tweak - A minor adjustment to the codebase\r\n' +
			'- [ ] Performance - Address performance issues\r\n' +
			'- [ ] Enhancement\r\n' +
			'\r\n' +
			'#### Message ' +
			'<!-- Add a changelog message here -->\r\n' +
			'This is a very useful fix.\r\n' +
			'\r\n' +
			'#### Comment ' +
			`<!-- If the changes in this pull request don't warrant a changelog entry, you can alternatively supply a comment here. Note that comments are only accepted with a significance of "Patch" -->\r\n` +
			'\r\n' +
			'</details>';

		const significance = getChangelogSignificance( body );
		expect( significance ).toBeUndefined();
		expect( Logger.error ).toHaveBeenCalledWith(
			'No changelog significance found'
		);
	} );

	it( 'should error when more than one significance selected', () => {
		const body =
			'### Changelog entry\r\n' +
			'\r\n' +
			'<!-- You can optionally choose to enter a changelog entry by checking the box and supplying data. -->\r\n' +
			'\r\n' +
			'- [x] Automatically create a changelog entry from the details below.\r\n' +
			'\r\n' +
			'<details>\r\n' +
			'\r\n' +
			'#### Significance\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Patch\r\n' +
			'- [x] Minor\r\n' +
			'- [ ] Major\r\n' +
			'\r\n' +
			'#### Type\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Fix - Fixes an existing bug\r\n' +
			'- [ ] Add - Adds functionality\r\n' +
			'- [ ] Update - Update existing functionality\r\n' +
			'- [ ] Dev - Development related task\r\n' +
			'- [ ] Tweak - A minor adjustment to the codebase\r\n' +
			'- [ ] Performance - Address performance issues\r\n' +
			'- [ ] Enhancement\r\n' +
			'\r\n' +
			'#### Message ' +
			'<!-- Add a changelog message here -->\r\n' +
			'This is a very useful fix.\r\n' +
			'\r\n' +
			'#### Comment ' +
			`<!-- If the changes in this pull request don't warrant a changelog entry, you can alternatively supply a comment here. Note that comments are only accepted with a significance of "Patch" -->\r\n` +
			'\r\n' +
			'</details>';

		const significance = getChangelogSignificance( body );
		expect( significance ).toBeUndefined();
		expect( Logger.error ).toHaveBeenCalledWith(
			'Multiple changelog significances found. Only one can be entered'
		);
	} );
} );

describe( 'getChangelogType', () => {
	it( 'should return the selected changelog type', () => {
		const body =
			'### Changelog entry\r\n' +
			'\r\n' +
			'<!-- You can optionally choose to enter a changelog entry by checking the box and supplying data. -->\r\n' +
			'\r\n' +
			'- [x] Automatically create a changelog entry from the details below.\r\n' +
			'\r\n' +
			'<details>\r\n' +
			'\r\n' +
			'#### Significance\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Patch\r\n' +
			'- [ ] Minor\r\n' +
			'- [ ] Major\r\n' +
			'\r\n' +
			'#### Type\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Fix - Fixes an existing bug\r\n' +
			'- [ ] Add - Adds functionality\r\n' +
			'- [ ] Update - Update existing functionality\r\n' +
			'- [ ] Dev - Development related task\r\n' +
			'- [ ] Tweak - A minor adjustment to the codebase\r\n' +
			'- [ ] Performance - Address performance issues\r\n' +
			'- [ ] Enhancement\r\n' +
			'\r\n' +
			'#### Message ' +
			'<!-- Add a changelog message here -->\r\n' +
			'This is a very useful fix.\r\n' +
			'\r\n' +
			'#### Comment ' +
			`<!-- If the changes in this pull request don't warrant a changelog entry, you can alternatively supply a comment here. Note that comments are only accepted with a significance of "Patch" -->\r\n` +
			'\r\n' +
			'</details>';

		const type = getChangelogType( body );
		expect( type ).toBe( 'fix' );
	} );

	it( 'should error when no type selected', () => {
		const body =
			'### Changelog entry\r\n' +
			'\r\n' +
			'<!-- You can optionally choose to enter a changelog entry by checking the box and supplying data. -->\r\n' +
			'\r\n' +
			'- [x] Automatically create a changelog entry from the details below.\r\n' +
			'\r\n' +
			'<details>\r\n' +
			'\r\n' +
			'#### Significance\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Patch\r\n' +
			'- [ ] Minor\r\n' +
			'- [ ] Major\r\n' +
			'\r\n' +
			'#### Type\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [ ] Fix - Fixes an existing bug\r\n' +
			'- [ ] Add - Adds functionality\r\n' +
			'- [ ] Update - Update existing functionality\r\n' +
			'- [ ] Dev - Development related task\r\n' +
			'- [ ] Tweak - A minor adjustment to the codebase\r\n' +
			'- [ ] Performance - Address performance issues\r\n' +
			'- [ ] Enhancement\r\n' +
			'\r\n' +
			'#### Message ' +
			'<!-- Add a changelog message here -->\r\n' +
			'This is a very useful fix.\r\n' +
			'\r\n' +
			'#### Comment ' +
			`<!-- If the changes in this pull request don't warrant a changelog entry, you can alternatively supply a comment here. Note that comments are only accepted with a significance of "Patch" -->\r\n` +
			'\r\n' +
			'</details>';

		const type = getChangelogType( body );
		expect( type ).toBeUndefined();
		expect( Logger.error ).toHaveBeenCalledWith(
			'No changelog type found'
		);
	} );

	it( 'should error more than one type selected', () => {
		const body =
			'### Changelog entry\r\n' +
			'\r\n' +
			'<!-- You can optionally choose to enter a changelog entry by checking the box and supplying data. -->\r\n' +
			'\r\n' +
			'- [x] Automatically create a changelog entry from the details below.\r\n' +
			'\r\n' +
			'<details>\r\n' +
			'\r\n' +
			'#### Significance\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Patch\r\n' +
			'- [ ] Minor\r\n' +
			'- [ ] Major\r\n' +
			'\r\n' +
			'#### Type\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [ ] Fix - Fixes an existing bug\r\n' +
			'- [ ] Add - Adds functionality\r\n' +
			'- [x] Update - Update existing functionality\r\n' +
			'- [x] Dev - Development related task\r\n' +
			'- [x] Tweak - A minor adjustment to the codebase\r\n' +
			'- [ ] Performance - Address performance issues\r\n' +
			'- [ ] Enhancement\r\n' +
			'\r\n' +
			'#### Message ' +
			'<!-- Add a changelog message here -->\r\n' +
			'This is a very useful fix.\r\n' +
			'\r\n' +
			'#### Comment ' +
			`<!-- If the changes in this pull request don't warrant a changelog entry, you can alternatively supply a comment here. Note that comments are only accepted with a significance of "Patch" -->\r\n` +
			'\r\n' +
			'</details>';

		const type = getChangelogType( body );
		expect( type ).toBeUndefined();
		expect( Logger.error ).toHaveBeenCalledWith(
			'Multiple changelog types found. Only one can be entered'
		);
	} );
} );

describe( 'getChangelogDetails', () => {
	it( 'should return the changelog details', () => {
		const body =
			'### Changelog entry\r\n' +
			'\r\n' +
			'<!-- You can optionally choose to enter a changelog entry by checking the box and supplying data. -->\r\n' +
			'\r\n' +
			'- [x] Automatically create a changelog entry from the details below.\r\n' +
			'\r\n' +
			'<details>\r\n' +
			'\r\n' +
			'#### Significance\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Patch\r\n' +
			'- [ ] Minor\r\n' +
			'- [ ] Major\r\n' +
			'\r\n' +
			'#### Type\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Fix - Fixes an existing bug\r\n' +
			'- [ ] Add - Adds functionality\r\n' +
			'- [ ] Update - Update existing functionality\r\n' +
			'- [ ] Dev - Development related task\r\n' +
			'- [ ] Tweak - A minor adjustment to the codebase\r\n' +
			'- [ ] Performance - Address performance issues\r\n' +
			'- [ ] Enhancement\r\n' +
			'\r\n' +
			'#### Message ' +
			'<!-- Add a changelog message here -->\r\n' +
			'This is a very useful fix.\r\n' +
			'\r\n' +
			'#### Comment ' +
			`<!-- If the changes in this pull request don't warrant a changelog entry, you can alternatively supply a comment here. Note that comments are only accepted with a significance of "Patch" -->\r\n` +
			'\r\n' +
			'</details>';

		const details = getChangelogDetails( body );
		expect( details.significance ).toEqual( 'patch' );
		expect( details.type ).toEqual( 'fix' );
		expect( details.message ).toEqual( 'This is a very useful fix.' );
		expect( details.comment ).toEqual( '' );
	} );

	it( 'should provide comment and message when both are added', () => {
		const body =
			'### Changelog entry\r\n' +
			'\r\n' +
			'<!-- You can optionally choose to enter a changelog entry by checking the box and supplying data. -->\r\n' +
			'\r\n' +
			'- [x] Automatically create a changelog entry from the details below.\r\n' +
			'\r\n' +
			'<details>\r\n' +
			'\r\n' +
			'#### Significance\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Patch\r\n' +
			'- [ ] Minor\r\n' +
			'- [ ] Major\r\n' +
			'\r\n' +
			'#### Type\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Fix - Fixes an existing bug\r\n' +
			'- [ ] Add - Adds functionality\r\n' +
			'- [ ] Update - Update existing functionality\r\n' +
			'- [ ] Dev - Development related task\r\n' +
			'- [ ] Tweak - A minor adjustment to the codebase\r\n' +
			'- [ ] Performance - Address performance issues\r\n' +
			'- [ ] Enhancement\r\n' +
			'\r\n' +
			'#### Message ' +
			'<!-- Add a changelog message here -->\r\n' +
			'This is a very useful fix.\r\n' +
			'\r\n' +
			'#### Comment ' +
			`<!-- If the changes in this pull request don't warrant a changelog entry, you can alternatively supply a comment here. Note that comments are only accepted with a significance of "Patch" -->\r\n` +
			'This is a very useful comment.\r\n' +
			'\r\n' +
			'</details>';

		const details = getChangelogDetails( body );
		expect( details.message ).toEqual( 'This is a very useful fix.' );
		expect( details.comment ).toEqual( 'This is a very useful comment.' );
	} );

	it( 'should remove newlines from message and comment', () => {
		const body =
			'### Changelog entry\r\n' +
			'\r\n' +
			'<!-- You can optionally choose to enter a changelog entry by checking the box and supplying data. -->\r\n' +
			'\r\n' +
			'- [x] Automatically create a changelog entry from the details below.\r\n' +
			'\r\n' +
			'<details>\r\n' +
			'\r\n' +
			'#### Significance\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Patch\r\n' +
			'- [ ] Minor\r\n' +
			'- [ ] Major\r\n' +
			'\r\n' +
			'#### Type\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Fix - Fixes an existing bug\r\n' +
			'- [ ] Add - Adds functionality\r\n' +
			'- [ ] Update - Update existing functionality\r\n' +
			'- [ ] Dev - Development related task\r\n' +
			'- [ ] Tweak - A minor adjustment to the codebase\r\n' +
			'- [ ] Performance - Address performance issues\r\n' +
			'- [ ] Enhancement\r\n' +
			'\r\n' +
			'#### Message ' +
			'<!-- Add a changelog message here -->\r\n' +
			'This is a very useful fix.\r\n' +
			'I promise!\r\n' +
			'\r\n' +
			'#### Comment ' +
			`<!-- If the changes in this pull request don't warrant a changelog entry, you can alternatively supply a comment here. Note that comments are only accepted with a significance of "Patch" -->\r\n` +
			'This is a very useful comment.\r\n' +
			"I don't promise!\r\n" +
			'\r\n' +
			'</details>';

		const details = getChangelogDetails( body );
		expect( details.message ).toEqual(
			'This is a very useful fix. I promise!'
		);
		expect( details.comment ).toEqual(
			"This is a very useful comment. I don't promise!"
		);
	} );

	it( 'should return a comment even when it is entered with a significance other than patch', () => {
		const body =
			'### Changelog entry\r\n' +
			'\r\n' +
			'<!-- You can optionally choose to enter a changelog entry by checking the box and supplying data. -->\r\n' +
			'\r\n' +
			'- [x] Automatically create a changelog entry from the details below.\r\n' +
			'\r\n' +
			'<details>\r\n' +
			'\r\n' +
			'#### Significance\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [ ] Patch\r\n' +
			'- [x] Minor\r\n' +
			'- [ ] Major\r\n' +
			'\r\n' +
			'#### Type\r\n' +
			'<!-- Choose only one -->\r\n' +
			'- [x] Fix - Fixes an existing bug\r\n' +
			'- [ ] Add - Adds functionality\r\n' +
			'- [ ] Update - Update existing functionality\r\n' +
			'- [ ] Dev - Development related task\r\n' +
			'- [ ] Tweak - A minor adjustment to the codebase\r\n' +
			'- [ ] Performance - Address performance issues\r\n' +
			'- [ ] Enhancement\r\n' +
			'\r\n' +
			'#### Message ' +
			'<!-- Add a changelog message here -->\r\n' +
			'\r\n' +
			'#### Comment ' +
			`<!-- If the changes in this pull request don't warrant a changelog entry, you can alternatively supply a comment here. Note that comments are only accepted with a significance of "Patch" -->\r\n` +
			'This is a very useful comment.\r\n' +
			'\r\n' +
			'</details>';

		const details = getChangelogDetails( body );
		expect( details.comment ).toEqual( 'This is a very useful comment.' );
		expect( details.significance ).toEqual( 'minor' );
	} );
} );

describe( 'getChangelogDetailsError', () => {
	it( 'should return an error when both a message and comment provided', () => {
		const error = getChangelogDetailsError( {
			message: 'message',
			comment: 'comment',
			type: 'fix',
			significance: 'minor',
		} );
		expect( error ).toEqual(
			'Both a message and comment were found. Only one can be entered'
		);
	} );

	it( 'should return an error when a comment is provided with a significance other than patch', () => {
		const error = getChangelogDetailsError( {
			message: '',
			comment: 'comment',
			type: 'fix',
			significance: 'minor',
		} );
		expect( error ).toEqual(
			'Only patch changes can have a comment. Please change the significance to patch or remove the comment'
		);
	} );

	it( 'should return an error when no significance found', () => {
		const error = getChangelogDetailsError( {
			message: 'message',
			comment: '',
			type: 'fix',
			significance: '',
		} );
		expect( error ).toEqual( 'No changelog significance found' );
	} );

	it( 'should return an error when no type found', () => {
		const error = getChangelogDetailsError( {
			message: 'message',
			comment: '',
			type: '',
			significance: 'minor',
		} );
		expect( error ).toEqual( 'No changelog type found' );
	} );

	it( 'should return an error when neither a comment or message is provided', () => {
		const error = getChangelogDetailsError( {
			message: '',
			comment: '',
			type: 'fix',
			significance: 'minor',
		} );
		expect( error ).toEqual( 'No changelog message or comment found' );
	} );
} );
