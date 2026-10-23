# AGENTS.md

This is a Symfony **bundle**, not an application: there is no `bin/console`, `.env` or `public/`. It adds Doctrine persistence and a REST API on top of `boutdecode/etl-core-bundle` (sibling repository `../ETL-core`).

## Layout

The tree mirrors the core's DDD layout: one bounded context per directory (`Core`, `Run`, `Statistics`, `ETL`), each split into layers, and a class lives in the context of the core interface it implements. `tests/Unit` and `tests/Integration` mirror `src/`.

- `src/<Context>/Infrastructure/Persistence/ORM` — the Doctrine layer, split into `Entity` (concrete entities extending the core's `Abstract*` models: `Core`: Pipeline, PlannedTask, Step, Workflow; `Run`: PipelineHistory, StepHistory; `Statistics`: WorkflowStatistic, WorkflowExecutionStatistic), `Repository` (implement the core's `Persister` / `Provider` interfaces) and `Factory` (implement the core's `*Factory` interfaces). Doctrine mapping (one per context) and API Platform paths are prepended by `DependencyInjection/ETLExtension`.
- `src/Run/Infrastructure/Scheduler` — implements the core's `ExpressionScheduler`.
- `src/Run/Application/PipelineLauncher` — creates, schedules and dispatches a pipeline.
- `src/<Context>/Infrastructure/Api` — API Platform layer. Entities carry the `#[ApiResource]` attributes (and serialization groups where the core's getters would leak internals); `State` holds the providers/processors, which go through the core's `CommandBus`; `Request` the validated input DTOs; `ETL/Infrastructure/Api/Resource` the non-Doctrine `ExecutableStep` resource.
- `#[AsAlias]` on each repository, factory and the scheduler — aliases the core interface to its implementation here. Add one when the core adds an interface. New services must sit under a context path covered by `Resources/config/services.yaml`.

## Commands

```bash
composer ecs:fix && composer phpstan && composer test
```

`test:integration` needs the database from `ETL_TEST_DATABASE_URL` (see README).

## Conventions

- `declare(strict_types=1)` everywhere, PHPStan level max without ignores, ECS as in the core.
- Follow the core's phpdoc types (e.g. `stepConfiguration` is `array<string, mixed>`) and validate the real shape at runtime with `Webmozart\Assert`.
- Core behaviours worth remembering: pipelines run only from `scheduled` status; `ExecuteWorkflowCommand` is async; the core requires the host to define `sync` and `async` Messenger transports and a default bus.
