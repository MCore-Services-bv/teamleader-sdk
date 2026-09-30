# API list-endpoint contract

Generated from `@teamleader/focus-api-specification` v1.221.0 by
`tests/Fixtures/specification/generate-spec-fixtures.mjs`. Do not edit by hand.

58 `.list` endpoints. The specification types `includes` as a free-form
string, so the values are collected from the request example and description and
from response fields documented as "only included with `includes=...`".

| Endpoint | Filters | Sort fields | Includes | Notes |
|---|---|---|---|---|
| `activityTypes` | `ids` | — | — | page |
| `bookkeepingSubmissions` | `subject` | — | — | — |
| `businessTypes` | **none** | — | — | — |
| `callOutcomes` | **none** | — | — | page |
| `calls` | `call_outcome_id`, `relates_to`, `scheduled_after`, `scheduled_before` | — | — | page, meta via `includes=pagination` |
| `closingDays` | `date_after`, `date_before` | — | — | page, meta via `includes=pagination` |
| `commercialDiscounts` | `department_id` | — | — | — |
| `companies` | `email`, `ids`, `marketing_mails_consent`, `national_identification_number`, `status`, `tags`, `term`, `updated_since`, `vat_number` | `added_at`, `name`, `updated_at` | `custom_fields` | page |
| `contacts` | `company_id`, `email`, `ids`, `marketing_mails_consent`, `status`, `tags`, `term`, `updated_since` | `added_at`, `name`, `updated_at` | `custom_fields` | page |
| `creditNotes` | `credit_note_date_after`, `credit_note_date_before`, `customer`, `department_id`, `ids`, `invoice_id`, `project_id`, `updated_since` | — | — | page |
| `customFieldDefinitions` | `context`, `ids` | `context`, `label` | — | page |
| `dayOffTypes` | **none** | — | — | — |
| `dealPhases` | `deal_pipeline_id`, `ids` | — | — | page |
| `dealPipelines` | `ids`, `status`, `term` | — | — | page, meta via `includes=pagination` |
| `deals` | `created_before`, `customer`, `estimated_closing_date`, `estimated_closing_date_from`, `estimated_closing_date_until`, `ids`, `phase_id`, `pipeline_ids`, `responsible_user_id`, `status`, `term`, `updated_since` | `created_at`, `weighted_value` | `custom_fields`, `second_responsible_user` | page |
| `dealSources` | `ids`, `term` | `name` | — | page |
| `departments` | `ids`, `status` | `created_at`, `default_department`, `name` | — | — |
| `documentTemplates` | `department_id`, `document_type`, `status` | — | — | — |
| `emailTracking` | `subject` | — | — | page |
| `events` | `activity_type_id`, `attendee`, `done`, `ends_after`, `ids`, `link`, `starts_before`, `task_id`, `term`, `user_id` | `starts_at` | — | page |
| `expenses` | `bookkeeping_statuses`, `department_ids`, `document_date`, `paid_at`, `payment_statuses`, `review_statuses`, `source_types`, `supplier`, `term` | `document_date`, `due_date`, `supplier_name` | — | page, meta via `includes=pagination` |
| `files` | `subject` | `updated_at` | — | page |
| `invoices` | `customer`, `deal_id`, `department_id`, `ids`, `invoice_date_after`, `invoice_date_before`, `invoice_number`, `payment_reference`, `project_id`, `purchase_order_number`, `status`, `subscription_id`, `term`, `updated_since` | `invoice_date`, `invoice_number` | `late_fees`, `totals.due_incasso_inclusive`, `totals.fixed_late_fee`, `totals.interest` | page |
| `levelTwoAreas` | **none** | — | — | — |
| `lostReasons` | `ids` | `name` | — | page |
| `mailTemplates` | `department_id`, `type` | — | — | — |
| `meetings` | `employee_id`, `end_date`, `group_id`, `ids`, `milestone_id`, `recurrence_id`, `start_date`, `term` | `scheduled_at` | `estimated_time`, `tracked_time` | page |
| `milestones` | `due_after`, `due_before`, `ids`, `project_id`, `status`, `term` | `due_on`, `starts_on` | — | page |
| `notes` | `subject` | — | — | page |
| `orders` | `ids` | — | `custom_fields` | page |
| `paymentMethods` | `ids`, `status` | — | — | page |
| `paymentTerms` | **none** | — | — | — |
| `plannableItems` | `assignees`, `completion_statuses`, `end_date`, `ids`, `planned_time_statuses`, `project_ids`, `start_date`, `term`, `types`, `work_type_ids` | `end_date`, `id`, `total_duration` | — | page |
| `priceLists` | `ids` | — | — | — |
| `productCategories` | `department_id` | — | — | — |
| `products` | `ids`, `term`, `updated_since` | — | — | page |
| `projects-v2/materials` | `ids` | — | — | page |
| `projects-v2/projectGroups` | `ids`, `project_id` | — | — | page |
| `projects-v2/projectLines` | `assignees`, `types` | — | — | — |
| `projects-v2/projects` | `customers`, `deal_ids`, `ids`, `quotation_ids`, `status`, `term` | `amount_billed`, `amount_paid`, `amount_unbilled`, `cost`, `customer`, `end_date`, `external_budget`, `external_budget_spent`, `internal_budget`, `margin`, `price`, `project_key`, `start_date`, `status`, `time_budget`, `time_estimated`, `time_tracked`, `title` | `custom_fields`, `legacy_project` | page, meta via `includes=pagination` |
| `projects-v2/tasks` | `ids` | — | — | page |
| `projects` | `customer`, `participant_id`, `status`, `term`, `updated_since` | `created_at`, `due_on`, `title` | — | page |
| `quotations` | `ids` | — | `expiry` | page, includes named in response only |
| `reservations` | `assignees`, `end_date`, `plannable_item_ids`, `project_ids`, `source_types`, `sources`, `start_date`, `term`, `work_type_ids` | — | — | page |
| `subscriptions` | `customer`, `deal_id`, `department_id`, `ids`, `invoice_id`, `status` | `created_at`, `status`, `title` | — | page |
| `tags` | **none** | `tag` | — | page |
| `tasks` | `completed`, `customer`, `due_by`, `due_from`, `ids`, `milestone_id`, `scheduled`, `term`, `user_id` | `created_at`, `due_on` | — | page |
| `taxRates` | `department_id` | `department_id`, `description`, `rate` | — | page |
| `teams` | `ids`, `team_lead_id`, `term` | `name` | — | — |
| `tickets` | `assignee_ids`, `exclude`, `ids`, `project_ids`, `relates_to` | — | — | page |
| `ticketStatus` | `ids` | — | — | — |
| `timeTracking` | `ended_after`, `ended_before`, `ids`, `relates_to`, `started_after`, `started_before`, `subject`, `subject_types`, `user_id` | `starts_on` | `materials`, `relates_to` | page |
| `unitsOfMeasure` | **none** | — | — | — |
| `users` | `ids`, `status`, `term` | `email`, `first_name`, `function`, `last_name` | — | page |
| `userSchedules` | `from`, `until`, `user_ids` | — | — | page, meta via `includes=pagination` |
| `webhooks` | **none** | — | — | — |
| `withholdingTaxRates` | `department_id` | — | — | — |
| `workTypes` | `ids`, `term` | — | — | page |
