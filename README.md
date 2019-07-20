<p align="center"><img src="./src/icon.svg" width="100" height="100" alt="Xero plugin for Craft Commerce 2"></p>

# Xero plugin for Craft Commerce 2

## Overview
**NOTE: This plugin is stable however it's in active development so expect frequent updates while new features are added.**

This plugin allows you to atuomatically sync Commerce invoices with Xero.

You'll need to create a private application within Xero and then connect via oAuth using a consumer key and consumer secret.

Once connected the plugin allows you to map your Chart of Accounts.

By default all completed/paid orders will be pushed into the queue with a delay of 30 seconds and then once the queue has dispatched the job the invoice will be sent to Xero.

More details documentation to come soon!

## Feature requests
As this plugin is still in active development now is a good time to suggest them. Submit an issue in the repo to start the process.

## Roadmap
- Improve documentation
- Configure Crafts new testing framework to ensure new features don't cause unexpected issues.
- Add multiple hooks/events so developers can further extend if required
- Refunds support
- Admin features like element actions, widgets and more

## Installation

Either by the plugin store (search "Xero") or via composer.

`"mediabeastnz/craft-commerce-xero": "^1.0.0"`

## Requirements

This plugin requires Craft CMS 3.1.0 or later and Craft Commerce 2.0 or later.