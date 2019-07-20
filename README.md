<p align="center"><img src="./src/icon.svg" width="100" height="100" alt="Xero plugin for Craft Commerce 2"></p>

# Xero plugin for Craft Commerce 2

## Overview
**NOTE: 👨‍💻This plugin is stable and fine to use in production however it's in active development so expect frequent updates while new features are added until v1.0.0.**

This plugin allows you to automatically push Commerce invoices with Xero including contacts, payments and inventory updates.

You'll need to create a [private application](https://developer.xero.com) within Xero and then connect via oAuth using a consumer key, consumer secret and private cert.

Once connected the plugin allows you to map your Chart of Accounts including:

- sales revenue
- accounts receivable 
- shipping/develiery
- rounding

By default all paid orders will be pushed into the queue with a delay of 30 seconds (this is reduce the time customers spend waiting for their order to process) and then once the queue has dispatched the job, the invoice will be sent to Xero. 

More detailed documentation to coming soon.

## Feature requests 🙏
As this plugin is still in active development now is a good time to suggest new features. Feel free to contact me via email or create a GitHub issue.

## Roadmap 🚀
- Improve documentation
- Configure Crafts new testing framework to ensure new features don't cause unexpected issues.
- Add multiple hooks/events so developers can further extend if required
- Refunds support
- Admin features like element actions, widgets and different syncing methods
## Installation

Either by the plugin store (search "Xero") or via composer.

`"mediabeastnz/craft-commerce-xero": "^0.9.0"`

## Requirements

This plugin requires Craft CMS 3.1.0 or later and Craft Commerce 2.0 or later.