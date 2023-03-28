# How to create your first UCRM plugin

This is a step by step guide for creating your first simple UCRM plugin.

## 1) Get your environment ready
First step is to prepare your development environment. You should have the following software installed:
- PHP 8.1 - https://secure.php.net/downloads.php
- Git - https://git-scm.com/
- Composer - https://getcomposer.org/

## 2) Prepare plugin's directory structure
UCRM plugins have a required directory structure. You can get started quickly by downloading the [skeleton ZIP archive](first-plugin/skeleton.zip) and unpacking it in your working directory.
You can get detailed information about plugin's file structure in [File structure](../file-structure.md) documentation.

After unpacking the skeleton archive, you should have the following structure ready:
```
README.md
src/.gitignore
src/composer.json
src/main.php
src/manifest.json
```

Everything in the `src` directory is the plugin itself.
The `README.md` file should contain description of what your plugin does.
It is also automatically displayed by Github when accessing the plugin's directory in the official UCRM Plugin repository.
Take a look at the [skeleton directory](first-plugin/skeleton) to see an example. Or at [Revenue Report Plugin](../../plugins/revenue-report) for a real one.  

After the plugin is finished and ready for release, the plugin's ZIP archive will also reside next to the `README.md` file.

## 3) Prepare your manifest file
Next we're going to take a look at the `manifest.json` file. This file describes your plugin to UCRM.
You can get detailed information about manifest structure in [Plugin manifest](../manifest.md) documentation.

There is basic manifest file already present in the skeleton files. For now, just change the name of your plugin to `My First Plugin`.
The manifest should look like this, after your change:
<pre>
{
    "version": "1",
    "information": {
        <strong>"name": "my-first-plugin",
        "displayName": "My First Plugin",</strong>
        "description": "Description of your plugin.",
        "url": "https://github.com/Ubiquiti-App/UCRM-plugins",
        "version": "1.0.0",
        "unmsVersionCompliancy": {
            "min": "2.1.0",
            "max": null
        },
        "author": "Your Name"
    }
}
</pre>

## 4) Install plugin dependencies
The skeleton comes with [UCRM Plugin SDK](https://github.com/Ubiquiti-App/UCRM-Plugin-SDK) as a composer dependency.
To install it just go to the `src` directory and run `composer install`.

## 5) Let's write some code
The `main.php` file is used to execute code in the background. We won't be actually using it in this tutorial, so let's just leave it as is.  
Instead we're going to create a `public.php` file right next to it.
This is used to display something in the browser and we can create a UCRM menu item leading to it.

So, create a file called `public.php` in the same directory as the `main.php` file a put the following code inside:
```php
<?php

require_once __DIR__ . '/vendor/autoload.php';
```

This code will make all packages defined in composer.json file available. For now it's just UCRM Plugin SDK.
We're going to use it to access UCRM API and display open jobs from the Scheduling module.

First we need to get the API service:
```php
$api = \Ubnt\UcrmPluginSdk\Service\UcrmApi::create();
```

Now that we have API service available, we can load the jobs:
```php
$jobs = $api->get('scheduling/jobs');
```

We now have all jobs available in UCRM loaded into the `$jobs` array, but that's not what we wanted.
Let's use some parameters to filter them. Change the line you just wrote to:
```php
$jobs = $api->get(
    'scheduling/jobs',
    [
        'statuses' => [0],
    ]
);
```

Now there are only open jobs in the array. However it would be nice to get only jobs relevant to currently logged in user.
To do this, we'll have to get the user's data first. We can use the Security service for that:
```php
$security = \Ubnt\UcrmPluginSdk\Service\UcrmSecurity::create();
$user = $security->getUser();
```

With the user's data available, let's check if the user is actually logged in (i.e. the `$user` variable is not `null`) and if he actually has the permission to view the jobs.
Add this code before API call:
```php
if (! $user || ! $user->hasViewPermission(\Ubnt\UcrmPluginSdk\Security\PermissionNames::SCHEDULING_MY_JOBS)) {
    die('You do not have permission to see this page.');
}

$jobs = $api->get(
// ...
```

Now that this is checked, we can load jobs just for this user. Let's change the API call again:
```php
$jobs = $api->get(
    'scheduling/jobs',
    [
        'statuses' => [0],
        'assignedUserId' => $user->userId,
    ]
);
```
> NOTE: You can find documentation for the `scheduling/jobs` endpoint in [UCRM API documentation](https://ucrm.docs.apiary.io/#reference/jobs).

Good, we have exactly the jobs we needed. Let's show them to the user.
```php
echo 'The following jobs are open and assigned to you:<br>';
echo '<ul>';
foreach ($jobs as $job) {
    echo sprintf('<li>%s</li>', htmlspecialchars($job['title'], ENT_QUOTES));
}
echo '</ul>';
```

Great, the code of your first UCRM plugin is now finished. It will display all open jobs assigned to the currently logged in user.

This is the final code in the `public.php` file:
```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$api = \Ubnt\UcrmPluginSdk\Service\UcrmApi::create();
$security = \Ubnt\UcrmPluginSdk\Service\UcrmSecurity::create();

$user = $security->getUser();
if (! $user || ! $user->hasViewPermission(\Ubnt\UcrmPluginSdk\Security\PermissionNames::SCHEDULING_MY_JOBS)) {
    die('You do not have permission to view this page.');
}

$jobs = $api->get(
    'scheduling/jobs',
    [
        'statuses' => [0],
        'assignedUserId' => $user->userId,
    ]
);

echo 'The following jobs are open and assigned to you:<br>';
echo '<ul>';
foreach ($jobs as $job) {
    echo sprintf('<li>%s</li>', htmlspecialchars($job['title'], ENT_QUOTES));
}
echo '</ul>';
```

## 6) Add a menu item
To make the plugin easily accessible for UCRM users, we should add an item to UCRM menu.
To do that, we will extend the plugin's manifest file with menu information:
<pre>
{
    "version": "1",
    "information": {
        "name": "my-first-plugin",
        "displayName": "My First Plugin",
        "description": "Description of your plugin.",
        "url": "https://github.com/Ubiquiti-App/UCRM-plugins",
        "version": "1.0.0",
        "unmsVersionCompliancy": {
            "min": "2.1.0",
            "max": null
        },
        "author": "Your Name"
    },
    <strong>"menu": [
        {
            "label": "My Jobs",
            "type": "admin",
            "target": "iframe"
        }
    ]</strong>
}
</pre>

## 7) Try the plugin
We have the plugin's code ready, but we have not actually tried if it works. To do that we will have to pack the plugin to ZIP archive first.
Run the following command in the `src` directory:
```bash
./vendor/bin/pack-plugin
```

A ZIP archive called `my-first-plugin.zip` will be created next to the `README.md` file.
Take it and upload it to UCRM in the System -> Plugins section.
After you enable the plugin, you will see new menu item "My Jobs" for your plugin and the list of open jobs for current user when you open it.

## 8) Finish line
__Congratulations,__ you've just created your first UCRM plugin! Play around and take a look at the complete [plugin documentation](../index.md).
If you get stuck on something or want to share your new plugin, make a post in the [UCRM Plugins community forum](https://community.ubnt.com/t5/UCRM-Plugins/bd-p/UCRMPlugins).
