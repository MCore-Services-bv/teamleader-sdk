```bash
tree -I 'node_modules|vendor|storage|.git|.idea' -a -L 6
.
├── CHANGELOG.md
├── FILES.md
├── README.md
├── composer.json
├── config
│   └── teamleader.php
├── docs
│   ├── crm
│   │   ├── addresses.md
│   │   ├── business_types.md
│   │   ├── companies.md
│   │   ├── contacts.md
│   │   └── tags.md
│   ├── deals
│   │   ├── deal_phases.md
│   │   ├── deal_pipelines.md
│   │   ├── deal_sources.md
│   │   ├── deals.md
│   │   ├── lost_reasons.md
│   │   ├── orders.md
│   │   └── quotations.md
│   ├── filtering.md
│   ├── general
│   │   ├── custom_fields.md
│   │   ├── departments.md
│   │   ├── notes.md
│   │   ├── resources.md
│   │   ├── teams.md
│   │   ├── users.md
│   │   └── work_types.md
│   ├── invoicing
│   │   ├── commercial_discounts.md
│   │   ├── creditnotes.md
│   │   ├── invoices.md
│   │   ├── payment_methods.md
│   │   ├── payment_terms.md
│   │   ├── subscriptions.md
│   │   ├── taxrates.md
│   │   └── withholding_taxrates.md
│   ├── other
│   │   └── webhooks.md
│   ├── products
│   │   ├── price_lists.md
│   │   ├── product_categories.md
│   │   ├── products.md
│   │   └── units_of_measure.md
│   ├── sideloading.md
│   └── usage.md
└── src
    ├── Console
    │   └── Commands
    │       ├── TeamleaderConfigValidateCommand.php
    │       ├── TeamleaderHealthCommand.php
    │       └── TeamleaderStatusCommand.php
    ├── Exceptions
    │   └── TeamleaderException.php
    ├── Facades
    │   ├── Teamleader
    │   │   └── ApiCallCounterMiddleware.php
    │   └── Teamleader.php
    ├── Resources
    │   ├── CRM
    │   │   ├── Addresses.php
    │   │   ├── BusinessTypes.php
    │   │   ├── Companies.php
    │   │   ├── Contacts.php
    │   │   └── Tags.php
    │   ├── Calender
    │   │   ├── ActivityTypes.php
    │   │   ├── CalenderEvents.php
    │   │   ├── CallOutcomes.php
    │   │   ├── Calls.php
    │   │   └── Meetings.php
    │   ├── Deals
    │   │   ├── DealPhases.php
    │   │   ├── DealPipelines.php
    │   │   ├── DealSources.php
    │   │   ├── Deals.php
    │   │   ├── LostReasons.php
    │   │   ├── Orders.php
    │   │   └── Quotations.php
    │   ├── Files
    │   │   └── Files.php
    │   ├── General
    │   │   ├── ClosingDays.php
    │   │   ├── Currencies.php
    │   │   ├── CustomFields.php
    │   │   ├── DayOffTypes.php
    │   │   ├── DaysOff.php
    │   │   ├── Departments.php
    │   │   ├── DocumentTemplates.php
    │   │   ├── EmailTracking.php
    │   │   ├── Notes.php
    │   │   ├── Teams.php
    │   │   ├── Users.php
    │   │   └── WorkTypes.php
    │   ├── Invoicing
    │   │   ├── CommercialDiscounts.php
    │   │   ├── Creditnotes.php
    │   │   ├── Invoices.php
    │   │   ├── PaymentMethods.php
    │   │   ├── PaymentTerms.php
    │   │   ├── Subscriptions.php
    │   │   ├── TaxRates.php
    │   │   └── WithholdingTaxRates.php
    │   ├── Other
    │   │   ├── Accounts.php
    │   │   ├── CloudPlatforms.php
    │   │   ├── Migrate.php
    │   │   └── Webhooks.php
    │   ├── Products
    │   │   ├── PriceLists.php
    │   │   ├── ProductCategories.php
    │   │   ├── Products.php
    │   │   └── UnitsOfMeasure.php
    │   ├── Projects
    │   │   ├── Groups.php
    │   │   ├── Legacy
    │   │   │   ├── Milestones.php
    │   │   │   └── Projects.php
    │   │   ├── Materials.php
    │   │   ├── ProjectLines.php
    │   │   ├── Projects.php
    │   │   └── Tasks.php
    │   ├── Resource.php
    │   ├── Tasks
    │   │   └── Tasks.php4
    │   ├── Templates
    │   │   └── MailTemplates.php
    │   ├── Tickets
    │   │   ├── TicketStatus.php
    │   │   └── Tickets.php
    │   └── TimeTracking
    │       ├── TimeTracking.php4
    │       └── Timers.php4
    ├── Services
    │   ├── ApiRateLimiterService.php
    │   ├── ConfigurationValidator.php
    │   ├── HealthCheckService.php
    │   ├── TeamleaderErrorHandler.php
    │   └── TokenService.php
    ├── TeamleaderSDK.php
    ├── TeamleaderServiceProvider.php
    ├── Traits
    │   └── FilterTrait.php
    └── Transformers
        └── ResponseTransformer.php

33 directories, 115 files
```
