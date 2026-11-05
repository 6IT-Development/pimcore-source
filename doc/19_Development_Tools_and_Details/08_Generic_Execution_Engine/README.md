# Generic Execution Engine
:::caution

To use this feature, please update your `composer.json` by requiring `phpdocumentor/reflection-docblock` and `symfony/property-info` like in the `suggest` section. Enable the `PimcoreGenericExecutionEngineBundle` in your `bundles.php` file and install it accordingly with the following command:


`bin/console pimcore:bundle:install PimcoreGenericExecutionEngineBundle`

:::

Generic execution engine
- executes jobs asynchronously via [Symfony Messenger](https://symfony.com/doc/current/messenger.html).
- traces and logs state of job runs.
- manages (start, cancel, restart) job runs.

The execution is based on the [Symfony Messenger](https://symfony.com/doc/current/messenger.html#consuming-messages-running-the-worker) queue.
If activated, the selected jobs are automatically executed.

:::caution

Messages are dispatched via `pimcore_generic_execution_engine` transport. Please ensure you have workers processing this transport:

```bash
bin/console messenger:consume pimcore_generic_execution_engine
```

The bundle routes all messages implementing
`Pimcore\Bundle\GenericExecutionEngineBundle\Messenger\Messages\GenericExecutionEngineMessageInterface` to this
transport. Do not route them to a `sync://` transport or override the routing so they are handled synchronously: job
runs are continued by the events of the messenger worker only, so a synchronously handled message leaves the job run
in `running`.

:::