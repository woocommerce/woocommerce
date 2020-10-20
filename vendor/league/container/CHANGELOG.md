# Changelog

All Notable changes to `League\Container` will be documented in this file

## 3.3.3

### Fixed
- Fixed bug relating to `ReflectionContainer::call` on arrow functions.

## 3.3.2

### Added
- Experimental support for PHP 8.

### Fixed
- Fix issue when preventing reflection from using default value for arguments.

## 3.3.1

### Fixed
- Respect `$new` argument when getting tagged definitions.

## 3.3.0

### Added
- Support for PHP 7.3
- `{set,get}LeagueContainer` methods added to ContainerAwareTrait as a temporary measure until next major release when this can be properly addressed, less hinting of `Psr\Container\ContainerInterface`

### Changed
- Various internal code improvements

### Fixed
- Fix for `setConcrete` not re-resolving class on when overriding (@jleeothon)
- Fix stack overflow error incase a service provider lies about providing a specific service (@azjezz)
- Fix issue where providers may be aggregated multiple times (@bwg)
- Various documentation fixes

## 3.2.2

### Fixed
- Fixed issue that prevented service providers from registering if a previous one in the aggregate was already registered.

## 3.2.1

### Fixed
- Fixed issue where all service providers were registered regardless of whether they need to be.

## 3.2.0

### Added
- Added ability to add definition as not shared when container is set to default to shared.
- Added `{set|get}Concrete` to definitions to allow for better use of `extend`.

## 3.1.0

### Added
- Re-added the `share` proxy method that was mistakenly removed in previous major release.
- Added ability to set Conatiner to "share" by default using `defaultToShared` method.
- Added ability for `ReflectionContainer` to cache resolutions and pull from cache for following calls.

## 3.0.1

### Added
- Allow definition aggregates to be built outside of container.

## 3.0.0

### Added
- Service providers can now be pulled from the container if they are registered.
- Definition logic now handled by aggregate for better separation.
- Now able to add tags to a definition to return an array of items containing that tag.

### Changed
- Updated minimum PHP requirements to 7.0.
- Now depend directly on PSR-11 interfaces, including providing PSR-11 exceptions.
- Refactored inflector logic to accept type on construction and use generator to iterate.
- Refactored service provider logic with better separation and performance.
- Merged service provider signature logic in to one interface and abstract.
- Heavily simplified definition logic providing more control to user.

## 2.4.1

### Fixed
- Ensures `ReflectionContainer` converts class name in array callable to object.

## 2.4.0

### Changed
- Can now wrap shared objects as `RawArgument`.
- Ability to override shared items.

### Fixed
- Booleans now recognised as accepted values.
- Various docblock fixes.
- Unused imports removed.
- Unreachable arguments no longer passed.

## 2.3.0

### Added
- Now implementation of the PSR-11.

## 2.2.0

### Changed
- Service providers can now be added multiple times by giving them a signature.

## 2.1.0

### Added
- Allow resolving of `RawArgument` objects as first class dependencies.

### Changed
- Unnecessary recursion removed from `Container::get`.

## 2.0.3

### Fixed
- Bug where delegating container was not passed to delegate when needed.
- Bug where `Container::extend` would not return a shared definition to extend.

## 2.0.2

### Fixed
- Bug introduced in 2.0.1 where shared definitions registered via a service provider would never be returned as shared.

## 2.0.1

### Fixed
- Bug where shared definitions were not stored as shared.

## 2.0.0

### Added
- Now implementation of the container-interop project.
- `BootableServiceProviderInterface` for eagerly loaded service providers.
- Delegate container functionality.
- `RawArgument` to ensure scalars are not resolved from the container but seen as an argument.

### Altered
- Refactor of definition functionality.
- `Container::share` replaces `singleton` functionality to improve understanding.
- Auto wiring is now disabled by default.
- Auto wiring abstracted to be a delegate container `ReflectionContainer` handling all reflection based functionality.
- Inflection functionality abstracted to an aggregate.
- Service provider functionality abstracted to an aggregate.
- Much bloat removed.
- `Container::call` now proxies to `ReflectionContainer::call` and handles argument resolution in a much more efficient way.

### Removed
- Ability to register invokables, this functionality added a layer of complexity too large for the problem it solved.
- Container no longer accepts a configuration array, this functionality will now be provided by an external service provider package.

## 1.4.0

### Added
- Added `isRegisteredCallable` method to public API.
- Invoking `call` now accepts named arguments at runtime.

### Fixed
- Container now stores instantiated Service Providers after first instantiation.
- Extending a definition now looks in Service Providers as well as just Definitions.

## 1.3.1 - 2015-02-21

### Fixed
- Fixed bug where arbitrary values were attempted to be resolved as classes.

## 1.3.0 - 2015-02-09

### Added
- Added `ServiceProvider` functionality to allow cleaner resolving of complex dependencies.
- Added `Inflector` functionality to allow for manipulation of resolved objects of a specific type.
- Improvements to DRY throughout the package.

### Fixed
- Setter in `ContainerAwareTrait` now returns self (`$this`).

## 1.2.1 - 2015-01-29

### Fixed
- Allow arbitrary values to be registered via container config.

## 1.2.0 - 2015-01-13

### Added
- Improvements to `Container::call` functionality.

### Fixed
- General code tidy.
- Improvements to test suite.

## 1.1.1 - 2015-01-13

### Fixed
- Allow singleton to be passed as method argument.

## 1.1.0 - 2015-01-12

### Added
- Addition of `ContainerAwareTrait` to provide functionality from `ContainerAwareInterface`.

## 1.0.0 - 2015-01-12

### Added
- Migrated from [Orno\Di](https://github.com/orno/di).
