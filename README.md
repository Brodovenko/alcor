# Earning Line with a History of Manual Adjustments

The system calculates the line automatically, and a specialist can adjust it.
Adjustments can only be added. After the first one, automatic recalculation does not change the line anymore.

Built with DDD + CQRS + Event Sourcing in plain PHP 8.4, without a framework.

## How to run

```bash
composer install
composer demo     # runs the scenario from the task
composer test     # tests
composer check    # code style + PHPStan (max) + tests
```

With Docker:

```bash
docker compose run --rm app composer install
docker compose run --rm app composer demo
docker compose run --rm app composer test:coverage
```

## How it works

```
Command → CommandBus → Handler → EarningLine → events → EventStore
                                                     ↓
                     Query ← Read model ← Projector
```

- **`EarningLine`** is the only aggregate, and it checks all business rules:
  - `calculate()` creates the line;
  - `recalculate()` changes the system value while there are no adjustments; after the first one, it only records the attempt;
  - `addAdjustment()` adds an adjustment with an amount, a comment, an author and a time.
- **Events** are `SystemValueCalculated`, `ManualAdjustmentAdded`, `SystemRecalculationIgnored`. They are the only source of truth.
- **Read side.** The projector builds the line history from events: the frozen system value, the adjustments, the current value.
  The query reads only this history.

## Why Event Sourcing

The rule "an adjustment cannot be changed or deleted" describes a log where you can only add new records. In ES, this log is the data model.
There are no methods for editing, events cannot change, and the event store only adds. A mistake is fixed with a new compensating adjustment.

## Decisions

- **Money in cents (`int`).**
- **An ignored recalculation is an event, not an exception.** Batch recalculation does not break, and an auditor can see that the source data changed.
- **Optimistic locking.** Two specialists cannot overwrite each other's adjustments.
- **Own minimal ES/CQRS framework** (`src/Shared`).

## Assumptions

- The frozen value is the last system value before the first adjustment ($1,050, not $1,000).
- A zero adjustment and an empty comment are not allowed.
- One currency per line. Amounts are limited to ±9,999,999,999,999.99.
- The line value can be negative.
- Manual mode is permanent. You cannot go back to automatic calculation.
- Employee, period and earning type are not modelled.

## Structure

```
src/Shared/                  ES/CQRS framework: AggregateRoot, EventStore, CommandBus, Clock
src/Payroll/Domain/          aggregate, value objects, events
src/Payroll/Application/     commands, handlers, query, projector
src/Payroll/Infrastructure/  repository, in-memory storage, CLI, PayrollModule
tests/Unit, tests/Integration
```
