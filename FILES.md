```bash
.
├── .env.example
├── .gitattributes
├── .gitignore
├── CHANGELOG.md
├── CONTRIBUTING.md
├── FILES.md
├── LICENSE.md
├── README.md
├── composer.json
├── config
│   └── teamleader.php
├── docs
│   ├── calendar
│   │   ├── activity-types.md
│   │   ├── call-outcomes.md
│   │   ├── calls.md
│   │   ├── events.md
│   │   └── meetings.md
│   ├── crm
│   │   ├── addresses.md
│   │   ├── business-types.md
│   │   ├── companies.md
│   │   ├── contacts.md
│   │   └── tags.md
│   ├── deals
│   │   ├── deals.md
│   │   ├── lost-reasons.md
│   │   ├── orders.md
│   │   ├── phases.md
│   │   ├── pipelines.md
│   │   ├── quotations.md
│   │   └── sources.md
│   ├── errors.md
│   ├── expenses
│   │   ├── bookkeeping-submissions.md
│   │   ├── expenses.md
│   │   ├── incoming-creditnotes.md
│   │   ├── incoming-invoices.md
│   │   └── receipts.md
│   ├── files
│   │   └── files.md
│   ├── filtering.md
│   ├── general
│   │   ├── closing-days.md
│   │   ├── currencies.md
│   │   ├── custom-fields.md
│   │   ├── days-off-types.md
│   │   ├── days-off.md
│   │   ├── departments.md
│   │   ├── document-templates.md
│   │   ├── email-tracking.md
│   │   ├── notes.md
│   │   ├── teams.md
│   │   ├── users.md
│   │   └── work-types.md
│   ├── invoicing
│   │   ├── commercial-discounts.md
│   │   ├── creditnotes.md
│   │   ├── invoices.md
│   │   ├── payment-methods.md
│   │   ├── payment-terms.md
│   │   ├── subscriptions.md
│   │   ├── tax-rates.md
│   │   └── withholding-taxrates.md
│   ├── other
│   │   ├── accounts.md
│   │   ├── cloud-platforms.md
│   │   ├── migrate.md
│   │   └── webhooks.md
│   ├── products
│   │   ├── categories.md
│   │   ├── price-lists.md
│   │   ├── products.md
│   │   └── units-of-measure.md
│   ├── projects
│   │   ├── external-parties.md
│   │   ├── groups.md
│   │   ├── legacy
│   │   │   ├── milestones.md
│   │   │   └── projects.md
│   │   ├── materials.md
│   │   ├── project-lines.md
│   │   ├── project-tasks.md
│   │   └── projects.md
│   ├── resources.md
│   ├── security
│   │   └── token-storage.md
│   ├── sideloading.md
│   ├── tasks
│   │   └── tasks.md
│   ├── templates
│   │   └── mail-templates.md
│   ├── tickets
│   │   ├── ticket-status.md
│   │   └── tickets.md
│   ├── time-tracking
│   │   ├── time-tracking.md
│   │   └── timers.md
│   └── usage.md
├── phpunit.xml
├── src
│   ├── Console
│   │   └── Commands
│   │       ├── TeamleaderConfigValidateCommand.php
│   │       ├── TeamleaderExportUuidsCommand.php
│   │       ├── TeamleaderHealthCommand.php
│   │       └── TeamleaderStatusCommand.php
│   ├── Constants
│   │   ├── ErrorMessages.php
│   │   └── TeamleaderConstants.php
│   ├── Exceptions
│   │   └── TeamleaderException.php
│   ├── Facades
│   │   ├── Teamleader
│   │   │   └── ApiCallCounterMiddleware.php
│   │   └── Teamleader.php
│   ├── Resources
│   │   ├── CRM
│   │   │   ├── Addresses.php
│   │   │   ├── BusinessTypes.php
│   │   │   ├── Companies.php
│   │   │   ├── Contacts.php
│   │   │   └── Tags.php
│   │   ├── Calendar
│   │   │   ├── ActivityTypes.php
│   │   │   ├── CallOutcomes.php
│   │   │   ├── Calls.php
│   │   │   ├── Events.php
│   │   │   └── Meetings.php
│   │   ├── Deals
│   │   │   ├── Deals.php
│   │   │   ├── LostReasons.php
│   │   │   ├── Orders.php
│   │   │   ├── Phases.php
│   │   │   ├── Pipelines.php
│   │   │   ├── Quotations.php
│   │   │   └── Sources.php
│   │   ├── Expenses
│   │   │   ├── BookkeepingSubmissions.php
│   │   │   ├── Expenses.php
│   │   │   ├── IncomingCreditNotes.php
│   │   │   ├── IncomingInvoices.php
│   │   │   └── Receipts.php
│   │   ├── Files
│   │   │   └── Files.php
│   │   ├── General
│   │   │   ├── ClosingDays.php
│   │   │   ├── Currencies.php
│   │   │   ├── CustomFields.php
│   │   │   ├── DayOffTypes.php
│   │   │   ├── DaysOff.php
│   │   │   ├── Departments.php
│   │   │   ├── DocumentTemplates.php
│   │   │   ├── EmailTracking.php
│   │   │   ├── Notes.php
│   │   │   ├── Teams.php
│   │   │   ├── Users.php
│   │   │   └── WorkTypes.php
│   │   ├── Invoicing
│   │   │   ├── CommercialDiscounts.php
│   │   │   ├── Creditnotes.php
│   │   │   ├── Invoices.php
│   │   │   ├── PaymentMethods.php
│   │   │   ├── PaymentTerms.php
│   │   │   ├── Subscriptions.php
│   │   │   ├── TaxRates.php
│   │   │   └── WithholdingTaxRates.php
│   │   ├── Other
│   │   │   ├── Accounts.php
│   │   │   ├── CloudPlatforms.php
│   │   │   ├── Migrate.php
│   │   │   └── Webhooks.php
│   │   ├── Products
│   │   │   ├── Categories.php
│   │   │   ├── PriceLists.php
│   │   │   ├── Products.php
│   │   │   └── UnitOfMeasure.php
│   │   ├── Projects
│   │   │   ├── ExternalParties.php
│   │   │   ├── Groups.php
│   │   │   ├── LegacyMilestones.php
│   │   │   ├── LegacyProjects.php
│   │   │   ├── Materials.php
│   │   │   ├── ProjectLines.php
│   │   │   ├── ProjectTasks.php
│   │   │   └── Projects.php
│   │   ├── Resource.php
│   │   ├── Tasks
│   │   │   └── Tasks.php
│   │   ├── Templates
│   │   │   └── MailTemplates.php
│   │   ├── Tickets
│   │   │   ├── TicketStatus.php
│   │   │   └── Tickets.php
│   │   └── TimeTracking
│   │       ├── TimeTracking.php
│   │       └── Timers.php
│   ├── Services
│   │   ├── ApiRateLimiterService.php
│   │   ├── ConfigurationValidator.php
│   │   ├── HealthCheckService.php
│   │   ├── TeamleaderErrorHandler.php
│   │   └── TokenService.php
│   ├── TeamleaderSDK.php
│   ├── TeamleaderServiceProvider.php
│   ├── Traits
│   │   ├── FilterTrait.php
│   │   └── SanitizesLogData.php
│   └── Transformers
│       └── ResponseTransformer.php
└── tests
    ├── Feature
    │   ├── AuthenticationTest.php
    │   ├── CompaniesResourceTest.php
    │   └── ConfigurationValidatorTest.php
    ├── TestCase.php
    └── Unit
        └── Services
            ├── ErrorHandlerTest.php
            ├── RateLimiterTest.php
            └── TokenServiceTest.php

48 directories, 174 files
```
