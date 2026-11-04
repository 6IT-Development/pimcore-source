# Maintenance Mode

Pimcore offers a maintenance mode, which restricts access to the admin user interface to the user that enabled the maintenance mode. It is session based 
and no other user will be able to access the website or the admin interface. 

All other users get a [default "Temporary not available" page](https://github.com/pimcore/pimcore/blob/11.x/bundles/CoreBundle/public/misc/maintenance.html)
displayed. 

Moreover, maintenance scripts and headless executions of Pimcore will be prevented.
In addition, you can enable or disable the maintenance mode via the following console command:

```shell script
bin/console pimcore:maintenance-mode --enable
bin/console pimcore:maintenance-mode --disable
bin/console pimcore:maintenance-mode --status
```

Each call prints whether the maintenance mode is active afterwards. `--status` only prints it.

## Customize Maintenance Page

Overwrite the service `Pimcore\Bundle\CoreBundle\EventListener\MaintenancePageListener` in your `config/services.yaml`. 
The template is only read when the maintenance page is shown.

```yaml
Pimcore\Bundle\CoreBundle\EventListener\MaintenancePageListener:
    arguments:
        $defaultTemplateResource: '@@App/Resources/misc/maintenance.html'
```

The `loadTemplateFromResource` call still works, but it reads the file whenever the listener is created.

Use loadTemplateFromPath if the file is located outside a bundle.

```yaml
Pimcore\Bundle\CoreBundle\EventListener\MaintenancePageListener:
    calls:
        - [loadTemplateFromPath, ['/templates/maintenance.html']]
```
