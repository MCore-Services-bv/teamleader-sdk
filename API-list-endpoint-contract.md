# API list-endpoint contract

Generated from `@teamleader/focus-api-specification` v1.197.0.

58 `.list` endpoints.

| Endpoint | Filters | Sort fields | Includes |
|---|---|---|---|
| `activityTypes` | `ids` | — | — |
| `bookkeepingSubmissions` | `subject` | — | — |
| `businessTypes` | **none** | — | — |
| `callOutcomes` | **none** | — | — |
| `calls` | `call_outcome_id`, `relates_to`, `scheduled_after`, `scheduled_before` | — | — |
| `closingDays` | `date_after`, `date_before` | — | — |
| `commercialDiscounts` | `department_id` | — | — |
| `companies` | `email`, `ids`, `marketing_mails_consent`, `national_identification_number`, `status`, `tags`, `term`, `updated_since`, `vat_number` | `name`, `added_at`, `updated_at` | yes |
| `contacts` | `company_id`, `email`, `ids`, `marketing_mails_consent`, `status`, `tags`, `term`, `updated_since` | `added_at`, `name`, `updated_at` | yes |
| `creditNotes` | `credit_note_date_after`, `credit_note_date_before`, `customer`, `department_id`, `ids`, `invoice_id`, `project_id`, `updated_since` | — | — |
| `customFieldDefinitions` | `context`, `ids` | `label`, `context` | — |
| `dayOffTypes` | **none** | — | — |
| `dealPhases` | `deal_pipeline_id`, `ids` | — | — |
| `dealPipelines` | `ids`, `status` | — | — |
| `dealSources` | `ids` | — | — |
| `deals` | `created_before`, `customer`, `estimated_closing_date`, `estimated_closing_date_from`, `estimated_closing_date_until`, `ids`, `phase_id`, `pipeline_ids`, `responsible_user_id`, `status`, `term`, `updated_since` | `created_at`, `weighted_value` | yes |
| `departments` | `ids`, `status` | `default_department`, `name`, `created_at` | — |
| `documentTemplates` | `department_id`, `document_type`, `status` | — | — |
| `emailTracking` | `subject` | — | — |
| `events` | `activity_type_id`, `attendee`, `done`, `ends_after`, `ids`, `link`, `starts_before`, `task_id`, `term`, `user_id` | `starts_at` | — |
| `expenses` | `bookkeeping_statuses`, `department_ids`, `document_date`, `paid_at`, `payment_statuses`, `review_statuses`, `source_types`, `supplier`, `term` | `document_date`, `due_date`, `supplier_name` | — |
| `files` | `subject` | `updated_at` | — |
| `invoices` | `customer`, `deal_id`, `department_id`, `ids`, `invoice_date_after`, `invoice_date_before`, `invoice_number`, `payment_reference`, `project_id`, `purchase_order_number`, `status`, `subscription_id`, `term`, `updated_since` | `invoice_number`, `invoice_date` | yes |
| `levelTwoAreas` | **none** | — | — |
| `lostReasons` | `ids` | `name` | — |
| `mailTemplates` | `department_id`, `type` | — | — |
| `meetings` | `employee_id`, `end_date`, `group_id`, `ids`, `milestone_id`, `recurrence_id`, `start_date`, `term` | `scheduled_at` | yes |
| `milestones` | `due_after`, `due_before`, `ids`, `project_id`, `status`, `term` | `starts_on`, `due_on` | — |
| `notes` | `subject` | — | — |
| `orders` | `ids` | — | yes |
| `paymentMethods` | `ids`, `status` | — | — |
| `paymentTerms` | **none** | — | — |
| `plannableItems` | `assignees`, `completion_statuses`, `end_date`, `ids`, `planned_time_statuses`, `project_ids`, `start_date`, `term`, `types`, `work_type_ids` | `id`, `end_date`, `total_duration` | — |
| `priceLists` | `ids` | — | — |
| `productCategories` | `department_id` | — | — |
| `products` | `ids`, `term`, `updated_since` | — | — |
| `projects` | `customer`, `participant_id`, `status`, `term`, `updated_since` | `due_on`, `title`, `created_at` | — |
| `projects-v2/materials` | `ids` | — | — |
| `projects-v2/projectGroups` | `ids`, `project_id` | — | — |
| `projects-v2/projectLines` | `assignees`, `types` | — | — |
| `projects-v2/projects` | `customers`, `deal_ids`, `ids`, `quotation_ids`, `status`, `term` | `amount_billed`, `amount_paid`, `amount_unbilled`, `cost`, `customer`, `end_date`, `external_budget_spent`, `external_budget`, `internal_budget`, `margin`, `price`, `project_key`, `start_date`, `status`, `time_budget`, `time_estimated`, `time_tracked`, `title` | yes |
| `projects-v2/tasks` | `ids` | — | — |
| `quotations` | `ids` | — | — |
| `reservations` | `assignees`, `end_date`, `plannable_item_ids`, `project_ids`, `source_types`, `sources`, `start_date`, `term`, `work_type_ids` | — | — |
| `subscriptions` | `customer`, `deal_id`, `department_id`, `ids`, `invoice_id`, `status` | `title`, `created_at`, `status` | — |
| `tags` | **none** | `tag` | — |
| `tasks` | `completed`, `customer`, `due_by`, `due_from`, `ids`, `milestone_id`, `scheduled`, `term`, `user_id` | `created_at`, `due_on` | — |
| `taxRates` | `department_id` | `department_id`, `rate`, `description` | — |
| `teams` | `ids`, `team_lead_id`, `term` | `name` | — |
| `ticketStatus` | `ids` | — | — |
| `tickets` | `exclude`, `ids`, `project_ids`, `relates_to` | — | — |
| `timeTracking` | `ended_after`, `ended_before`, `ids`, `relates_to`, `started_after`, `started_before`, `subject`, `subject_types`, `user_id` | `starts_on` | yes |
| `unitsOfMeasure` | **none** | — | — |
| `userSchedules` | `from`, `until`, `user_ids` | — | — |
| `users` | `ids`, `status`, `term` | `first_name`, `last_name`, `email`, `function` | — |
| `webhooks` | **none** | — | — |
| `withholdingTaxRates` | `department_id` | — | — |
| `workTypes` | `ids`, `term` | — | — |
