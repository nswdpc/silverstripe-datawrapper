# Datawrapper support for Silverstripe

This module provides an iframe element to embed [Datawrapper](https://datawrapper.de) charts and maps on a page, as an Elemental content block, along with the script to enable responsiveness.

### Features

+ Supports Locator Maps, Charts, Chloropeth Maps
+ Responsive iframe support
+ Your content editors can edit charts and maps in Datawrapper and create corresponding elements in pages to display these
+ Datawrapper custom webhook support. See Configuration notes below and [Webhook notes](./docs/en/002_webhooks.md) for more information.

## Requirements

See [composer.json](./composer.json)

## Installation

```sh
composer require nswdpc/silverstripe-datawrapper
```

## License

[BSD-3-Clause](./LICENSE.md)

## Documentation

Further [documentation for content authors](./docs/en/001_index.md) is available.

## Configuration

### Enable the element

Add the following to a project configuration,e.g. 'app/_config/datawrapper.yml` to enable the content block on Page records:

```yml
Page:
  allowed_elements:
    - 'NSWDPC\Elemental\Models\Datawrapper\ElementDatawrapper'
```

### Webhooks

[See webhooks documentation](./docs/en/002_webhooks.md) for further information.

## Maintainers

+ PD Web Team

## Bugtracker

We welcome bug reports, pull requests and feature requests on the Github Issue tracker for this project.

Please review the [code of conduct](./code-of-conduct.md) prior to opening a new issue.

## Development and contribution

If you would like to make contributions to the module please ensure you raise a pull request and discuss with the module maintainers.

Please review the [code of conduct](./code-of-conduct.md) prior to completing a pull request.
