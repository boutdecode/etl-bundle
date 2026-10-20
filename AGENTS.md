# AGENTS.md

This is a Symfony **bundle**, not an application: there is no `bin/console`, `.env` or `public/`. It adds Doctrine persistence and a REST API on top of `boutdecode/etl-core-bundle` (sibling repository `../ETL-core`).

## Layout

- `src/Entity` — concrete entities extending the core's `Abstract*` models. Mapping is prepended by `DependencyInjection/ETLExtension`.
- `src/Repository` — implement the core's `Persister` / `Provider` interfaces.
- `src/Factory`, `src/Scheduler` — implement the core's `*Factory` and `ExpressionScheduler` interfaces.
- `src/Service/PipelineLauncher` — creates, schedules and dispatches a pipeline.
- `src/Api` — API Platform layer. Entities carry the `#[ApiResource]` attributes (and serialization groups where the core's getters would leak internals); `Api/State` holds the providers/processors, which go through the core's `CommandBus`; `Api/Request` the validated input DTOs; `Api/Resource` the non-Doctrine `ExecutableStep` resource.
- `#[AsAlias]` on each repository, factory and the scheduler — aliases the core interface to its implementation here. Add one when the core adds an interface.

## Commands

```bash
composer ecs:fix && composer phpstan && composer test
```

`test:integration` needs the database from `ETL_TEST_DATABASE_URL` (see README).

## Conventions

- `declare(strict_types=1)` everywhere, PHPStan level max without ignores, ECS as in the core.
- Follow the core's phpdoc types (e.g. `stepConfiguration` is `array<string, mixed>`) and validate the real shape at runtime with `Webmozart\Assert`.
- Core behaviours worth remembering: pipelines run only from `scheduled` status; `ExecuteWorkflowCommand` is async; the core requires the host to define `sync` and `async` Messenger transports and a default bus.
