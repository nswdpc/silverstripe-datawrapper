# Support for custom Webhooks

> Audience: Content administrators

To get notifications of publish events for your charts:
1. Navigate to  the relevant Teams page on the Datawrapper website
1. Select "Enable custom webhook"
1. If webhooks are enabled, enter the URL provided in the element editing screen of the CMS
1. In the CMS ensure 'Auto publish' is checked for relevant Datawrapper content elements

> Note: the chart must be in the same Datawrapper Team as the Custom Webhook URL. Move or duplicate the chart to the relevant team if this is not the case. The Datawrapper ID will change if you duplicate the chart.

## Developer testing

> Audience: Developers
>
> https://developer.datawrapper.de/docs/using-team-webhooks-for-publish-notifications

To test, publish a chart. A sample POST will look like this:

```
POST /webhook HTTP/1.1
content-type: application/json
content-length: 32

{"id":"zPrsY","publicVersion":1}
```

You can use a system such as Pipedream to inspect incoming requests.


## Turning off/on

Turn off|on via project configuration, the default is `true`

```yaml
NSWDPC\Datawrapper\Webhook:
  webhooks_enabled: true|false
  webhooks_random_code: 'some_random_code_string'
```

Use `webhooks_random_code` to randomise the URL. Using this example, the submission URL will look something like `https://mysite.example.com/_datawrapperwebhook/submit/some_random_code_string/`

This is empty by default. It's a good idea to have this as anyone who knows the URL and a Datawrapper chart ID you are using will be able to submit webhook requests.
