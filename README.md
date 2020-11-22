# Datawrapper support for Silverstripe

This module provides an iframe element to embed [Datawrapper](https://datawrapper.de) charts and maps on a page, along with the script to enable responsiveness.

### Features

+ Supports Locator Maps, Charts, Chloropeth Maps
+ Responsive iframe support
+ Your content editors can edit charts and maps in Datawrapper and create corresponding elements in pages to display these

## Requirements

See [composer.json](./composer.json)

## Installation

```
composer require nswdpc/silverstripe-datawrapper
```

Until this module appears in Packagist, add a repository entry:

```json
"repositories": [
    {
        "type" : "vcs",
        "url" : "https://github.com/nswdpc/silverstripe-datawrapper.git"
    }
    ...
]
```


## License

[BSD-3-Clause](./LICENSE.md)

## Documentation


Further [documentation for content authors](./docs/en/001_index.md) is available.

## Configuration

There is no current configuration other than base elemental configuration.

See [config.yml](./_config/config.yml) for module configuration values

## Maintainers

+ [dpcdigital@NSWDPC:~$](https://dpc.nsw.gov.au)

## Bugtracker

We welcome bug reports, pull requests and feature requests on the Github Issue tracker for this project.

Please review the [code of conduct](./code-of-conduct.md) prior to opening a new issue.

## Development and contribution

If you would like to make contributions to the module please ensure you raise a pull request and discuss with the module maintainers.

Please review the [code of conduct](./code-of-conduct.md) prior to completing a pull request.
