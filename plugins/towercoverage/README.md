# UCRM TowerCoverage Plugin

This is a simple plugin that allows end-users to configure a their UCRM to receive and handle data from the their
associated TowerCoverage.com accounts.

## Installation

#### TowerCoverage

1. Login to your Dashboard and Go to Account/API.
2. Under the EUS Billing API Section, fill in the following fields:

   | OPTION        | VALUE                                                 
   |---------------|-----------------------------------------------------------
   | EUS API       | `Other`
   | API Key       | `YOUR_DESIRED_KEY`
   | Push URL      | `http://YOUR_UCRM_HOST/_plugins/towercoverage/public.php`
   | Username      | `YOUR_DESIRED_USERNAME>`
   | Password      | `YOUR_DESIRED_PASSWORD>`

3. Click the Update button and you should be all set on this end!

_NOTE: You will also need to have your EUS Form living somewhere for users to visit!_ 


#### UCRM

1. Download the [Plugin](https://github.com/ucrm-plugins/towercoverage/raw/master/towercoverage.zip) and add it to the
System/Plugins in your UCRM.
2. Configure the plugin as noted below and then "Save and Enable".
3. Your all set, no web-hooks required with this one!


## Configuration

**Verbose Debugging?**

Currently does nothing!

Recommended: `No`

**API Key**

The API Key from your TowerCoverage.com EUS Billing API Settings noted above.

_If blank, allows receiving submissions from any TowerCoverage End-User Submission Form, regardless of the settings in
your TowerCoverage.com Account._

Recommended: `YOUR_DESIRED_KEY`

**API Username**

The API Username from your TowerCoverage.com EUS Billing API Settings noted above.

_If blank, allows receiving submissions from any TowerCoverage End-User Submission Form, regardless of the settings in
your TowerCoverage.com Account._

Recommended: `YOUR_DESIRED_USERNAME`

**API Password**

The API Password from your TowerCoverage.com EUS Billing API Settings noted above.

_If blank, allows receiving submissions from any TowerCoverage End-User Submission Form, regardless of the settings in
your TowerCoverage.com Account._

Recommended: `YOUR_DESIRED_PASSWORD`

**Duplicate Mode**

The method for determining duplicate submissions.

The options are as follows:

- "First & Last Names": When a submission comes in that matches the same First & Last Names of an existing Client Lead,
the submission will simply update the current Client Lead.

- "Primary Email": When a submission comes in that matches the same Primary Email of an existing Client Lead, the
submission will simply update the current Client Lead.

- "Street Address": When a submission comes in that matches the same Street Address of an existing Client Lead, the
submission will simply update the current Client Lead.

Recommended: `First & Last Names`

## Features

- Receives data pushed from the TowerCoverage.com End-User Submission (EUS) API and then either creates or updates a
Client Lead with the provided data.

- Optionally, the Plugin can be provided with an API Key, Username and Password to only allow EUS data from a single
TowerCoverage Account.  When not provided, the Plugin can receive and handle data for multiple TowerCoverage Accounts.

- Multiple "Duplicate Mode" options to allow automatic editing of existing Client Leads, based on "First & Last Names",
"Primary Email" or "Street Address".

- Works seamlessly with the Notifications Plugin, to notify Admins when a "Client Lead Added" or any subsequent "Client
Lead Edited" events.

#### Localization
COMING SOON


## About

### Requirements
- This package will be maintained in step with the PHP version used by UCRM to ensure 100% compatibility.
- Any packages required that are not already enabled in the default UCRM installation are included with this Plugin 
in the accompanying `vendor/` folder and can be updated and maintained manually using
[composer](https://getcomposer.org/) if desired.

### Related Packages
[ucrm-plugins/notifications](https://github.com/ucrm-plugins/notifications)\
A plugin module for localization.

### Submitting bugs and feature requests
Bugs and feature request are tracked on [Github](https://github.com/ucrm-plugins/towercoverage/issues)

### Author
Ryan Spaeth <[rspaeth@mvqn.net](mailto:rspaeth@mvqn.net)>

### License
This module is licensed under the MIT License - see the `LICENSE` file for details.

### Acknowledgements
Credit to the Ubiquiti Team for giving us the luxury of Plugins!
