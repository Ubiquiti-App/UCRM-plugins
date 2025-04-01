# ISPLink Plugin for UCRM

A UCRM plugin that integrates with [ISPLink](https://isplink.app) to provide a full self-service signup system for clients.

*Developed by [Fullstack](https://gofullstack.com)*

## Overview

When installed onto UCRM, the plugin public URL will display an intuitive multi-step interface for potential customers to check service availability at their address and sign up as a client in UISP.

**Note:** This plugin requires an active ISPLink account.  To create an ISPLink account, please visit [ISPLink](https://isplink.app).

**Version:** 1.0.0  
**Compatible with:**
- UCRM 2.14.0 and beyond
- UNMS 1.0.0-alpha.1 and beyond

## Features

- Address validation and service availability checking.
- Multi-step signup process with progress tracking.
- Customizable branding elements.
- Integration with [ISPLink](https://isplink.app)
- Account creation and lead capture.
- Installation scheduling.
- Initial payment processing via Authorize.net, Stripe, or Ubiquiti Payment Gateway.
- Adds intial payment method to UCRM for ongoing payments

## Installation

This plugin does not use "Execution Period" or "Execute Manually". It is designed to use the plugin public URL to post from a form and create a new client or lead.

## Configuration

The plugin offers several customization options:

- **Company Name**: Your company name (required)
- **ISPLink API Key**: Your [ISPLink](https://isplink.app) API key. To obtain an API key, please visit https://isplink.app
- **Page Title**: Title tag for your signup page
- **Company Website URL**: The URL of your company website
- **Signup Form Heading**: This heading will be displayed at the top of customer signup forms
- **Signup Form Subheading**: This subheading will be displayed at the top of customer signup forms
- **Logo URL**: URL of your company logo to display on the customer signup form

## Signup Process

The customer signup workflow includes:

1. **Availability Check**: Address validation to check service availability
2. **Service Plan Selection**: Browse and select available service plans
3. **Account Creation**: Enter personal and billing information
4. **Installation Scheduling**: Choose installation date and time
5. **Review**: Review all selections before submission
6. **Confirmation**: Receive confirmation and next steps

# Support
For support, please visit [https://isplink.app](https://isplink.app) or contact our support team.
