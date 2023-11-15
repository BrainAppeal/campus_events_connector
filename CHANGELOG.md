# CHANGELOG.md

## 4.0.1 (2023-11-15)

### Fixed:
- limit allowed maximum length of event description to 65KB to prevent import errors
- increase allowed size for JSON encoded import data in import schedule table
- always execute file importer update, as long as unprocessed import entries exist
- fix switching from new to old API in scheduler task

## 4.0.0 (2023-04-27)

### Added
- compatibility with TYPO3 12 LTS (12.4.0)

## 3.0.7 (2023-04-24)

### Added
- Import view list model
- add link to documentation website in README

### Changed:
- improve cleanup of temporary files
- consider max lengths of varchar fields

### Fixed:
- don't use strtotime with null values
- remove object passed by reference; code cleanup

## 3.0.6 (2022-10-14)

### Fixed:
- cleanup for temporary files in transient folders

## 3.0.5 (2022-09-26)

### Fixed:
- Fixed usage of ResourceFactory instance for TYPO3 11.5

## 3.0.4 (2022-09-15)

### Fixed:
- Fixed passing API Key to Campus Events

## 3.0.3 (2022-05-16)

### Changed
- Improve file import handling

## 3.0.2 (2022-04-14)

### Fixed
- Fix passing api key by header for CE version >= 2.28

## 3.0.1 (2022-04-01)

### Fixed
- Creation of new instances in object converter

## 3.0.0

### Added
- Compatibility with TYPO3 11.5
