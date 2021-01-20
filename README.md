# Datawrapper support for Silverstripe

This module provides an iframe element to embed [Datawrapper](https://datawrapper.de) charts and maps on a page, along with the script to enable responsiveness.

### Features

+ Supports Locator Maps, Charts, Chloropeth Maps
+ Responsive iframe support
+ Your content editors can edit charts and maps in Datawrapper and create corresponding elements in pages to display these
+ Datawrapper custom webhook support. See Configuration notes below and [Webhook notes](./docs/en/002_webhooks.md) for more information.

## Requirements

See [composer.json](./composer.json)

## Installation

```
composer require nswdpc/silverstripe-datawrapper
```

## License

[BSD-3-Clause](./LICENSE.md)

## Documentation


Further [documentation for content authors](./docs/en/001_index.md) is available.

## Configuration

### Webhooks

```yaml
Name: 'app-datawrapper'
After:
  - '#nswdpc-datawrapper'
NSWDPC\Datawrapper\WebhookController:
  webhooks_enabled: true|false
  webhooks_random_code: 'a random unguessable code'
```

If you are using Datwrapper custom webhooks, add a `webhooks_random_code` value.

As there is no shared webhook signing key, anyone with the webhook URL and the Datawrapper Id of an element on your website will be able to publish elements.
You can change this random code at any time but you must ensure the custom webhook URL value at Datawrapper is updated to match.

You can set elements to ignore webhook publishing requests by unchecking the  "Auto publish" value on an element.

Datawrapper custom webhook URLs are Team-based. Only charts in that team will receive a webhook request when they are published.

## Maintainers

+ [dpcdigital@NSWDPC:~$](https://dpc.nsw.gov.au)

## Bugtracker

We welcome bug reports, pull requests and feature requests on the Github Issue tracker for this project.

Please review the [code of conduct](./code-of-conduct.md) prior to opening a new issue.

## Development and contribution

If you would like to make contributions to the module please ensure you raise a pull request and discuss with the module maintainers.

Please review the [code of conduct](./code-of-conduct.md) prior to completing a pull request.
