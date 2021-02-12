# Release Notes for Craft Commerce Xero

## 1.0.0-beta.2 - 2021-02-13
### Changed
- Users can now select from a list of organisations
- Improved multi-tenant support
- Users can now disconnect organisations programatically
### Fixed
- Fixed a bug where the current connection wasn't returning the right organisation

## 1.0.0-beta.1 - 2021-02-12
### Changed
- Added support for Xero OAuth 2.0.
- Plugin now has a CP nav item.
- Account code mappings and settings can be changed in production mode.
- Refactored code base

## 0.9.3 - 2019-10-05
### Changed
- order items sent to Xero now use Crafts prodsuct description, which if isn't set defaults to title.
- fixed an issue where active carts weren't viewable when plugin was enabled.
- example xero.php config file include for multi environment setups.

## 0.9.2 - 2019-08-21
### Changed
- ca-bundle.crt is now required in settings, documentation has been updated. A ca-bundle file can easily be downloaded from github or firefox and then put in the same folder as your other cert files.

## 0.9.1 - 2019-08-01
### Fixed
- Fixed an issue where items where passing through an incorrect total

## 0.9.0 - 2019-07-20
### Added
- Initial BETA release
- Added ability to connect to Xero
- Added ability to configure Chart of Accounts
- Added ability autmatically send invoices to Xero
- Added ability to manually send invoices to Xero
