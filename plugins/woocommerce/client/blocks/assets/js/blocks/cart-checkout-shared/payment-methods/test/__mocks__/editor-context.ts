// This needs to be defined in a separate file because we are mocking an import.
// The only way to do this is to define the mock and import it BEFORE the module being mocked.
export default jest.fn( () => ( { isEditor: false } ) );
