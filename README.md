# ETLBundle

A Symfony Bundle that plugs **Doctrine persistence** and a **REST API** into [ETLCoreBundle](https://github.com/boutdecode/etl-core).

`boutdecode/etl-core-bundle` is the pipeline engine (workflows, pipelines, steps, Messenger buses, scheduler) but ships no storage: the application has to provide the entities, repositories and factories. This bundle provides them, so a project only needs to write its own steps.

[![PHP Version](https://img.shields.io/badge/PHP-8.2%20%7C%208.3%20%7C%208.4-blue)](https://php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-6.4%20%7C%207.x-green)](https://symfony.com/)
[![License](https://img.shields.io/badge/License-MIT-yellow)](LICENSE)

---

## What is included

| Area | Content |
|---|---|
| Entities | `Workflow`, `Step`, `Pipeline`, `PlannedTask`, `PipelineHistory`, `StepHistory`, `WorkflowStatistic`, `WorkflowExecutionStatistic` (tables prefixed `etl_`) |
| Repositories | One per entity, implementing every `Persister` / `Provider` interface of the core |
| Factories | Implementations of all the `*Factory` interfaces of the core |
| Scheduler | `CronExpressionScheduler` (the core's `ExpressionScheduler`), based on `dragonmantank/cron-expression` |
| REST API | API Platform resources: workflows, pipelines, planned tasks, history, statistics and the catalog of available steps |

The core's compiler pass only aliases interfaces the application implements; this bundle declares the aliases itself, so no wiring is needed.

## Requirements

| Dependency | Version |
|---|---|
| PHP | `>= 8.2` |
| Symfony | `^6.4 \|\| ^7.0` |
| Doctrine ORM / DoctrineBundle | `^3.6` / `^2.13` |
| API Platform | `^4.1` |
| `boutdecode/etl-core-bundle` | `^0.14` |

## Installation

```bash
composer require boutdecode/etl-bundle
```

Without Symfony Flex, register both bundles and import the core configuration:

```php
// config/bundles.php
return [
    // ...
    BoutDeCode\ETLCoreBundle\BoutDeCodeETLCoreBundle::class => ['all' => true],
    BoutDeCode\ETLBundle\BoutDeCodeETLBundle::class => ['all' => true],
];
```

```yaml
# config/packages/boutdecode_etl.yaml
imports:
    - { resource: "@BoutDeCodeETLCoreBundle/Resources/config/config.yaml" }
```

### What the application must still configure

These come from the core, not from this bundle:

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        default_bus: messenger.bus.default      # the core declares two buses, a default is required
        buses:
            messenger.bus.default: ~
        transports:
            sync: 'sync://'                     # the core routes its Sync* messages here
            async: '%env(MESSENGER_TRANSPORT_DSN)%'   # ExecuteWorkflowCommand is routed here
```

Run a worker for the `async` transport, and one for `scheduler_etl` if you use planned tasks or the purge:

```bash
bin/console messenger:consume async scheduler_etl
```

### Database

The bundle prepends the Doctrine mapping of its entities. Generate and run the migration in your application:

```bash
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
```

### API Platform

The entities are declared as [API Platform](https://api-platform.com) resources; the bundle prepends the `api_platform.mapping.paths` for you. Load API Platform's routes in your application, and make sure both formats used by the resources are enabled:

```yaml
# config/routes/api_platform.yaml
api_platform:
    resource: .
    type: api_platform
    prefix: /etl
```

```yaml
# config/packages/api_platform.yaml
api_platform:
    formats:
        jsonld: ['application/ld+json']
        json: ['application/json']
```

Everything else (pagination, error format, OpenAPI docs, CORS) is standard API Platform configuration, and applies to these resources like to any other.

## Configuration

```yaml
boutdecode_etl:
    api:
        enabled: true   # default: true — set to false to not expose the entities as API resources
```

With `enabled: false` the bundle's resources are hidden from API Platform (no route, no OpenAPI entry) and its state processors are not registered; the entities, repositories and factories keep working. This is done by filtering the resource list rather than through the mapping, because API Platform may discover resources on its own (it scans the `Entity` directory of every registered bundle), independently of the mapping paths this bundle prepends.

The bundle does not secure the API. Protect the prefix with your own `access_control` rules or a firewall:

```yaml
security:
    access_control:
        - { path: ^/etl, roles: ROLE_ETL_ADMIN }
```

## REST API

Resources are served as JSON-LD (`Accept: application/ld+json`) or plain JSON (`Accept: application/json`). Collections are paginated by API Platform. Validation failures answer `422` with a `violations` list. `null` properties are omitted, as is the API Platform default.

| Method | Path | Description |
|---|---|---|
| `GET` | `/executable_steps`, `/executable_steps/{code}` | Executable steps registered in the container, with their configuration schema |
| `GET` `POST` | `/workflows` | List / create a workflow. Unknown step `code`s are rejected |
| `GET` `PUT` | `/workflows/{id}` | Show / replace a workflow |
| `POST` | `/workflows/{id}/execute` | Create a pipeline from the workflow and queue its execution (`202`). Optional body: `name`, `configuration`, `input` |
| `GET` | `/pipelines` | List pipelines. Filters: `status` (`pending`, `scheduled`, `in_progress`, `completed`, `failed`), `workflow` |
| `GET` `DELETE` | `/pipelines/{id}` | Show / purge a pipeline and its history |
| `GET` | `/pipeline_histories` | Executions, with per-step input and result. Filters: `pipeline`, `status` |
| `GET` | `/workflow_statistics` | Aggregated statistics per workflow. Filter: `workflow` |
| `GET` | `/workflow_execution_statistics` | One row per execution. Filters: `workflow`, `status` |
| `GET` `POST` | `/planned_tasks` | List / create a planned task (`name`, `workflow` as an id, cron `schedule`, `enabled`, `input`, `configuration`) |
| `GET` `PUT` | `/planned_tasks/{id}` | Show / replace a planned task |
| `POST` | `/planned_tasks/{id}/execute` | Plan the next pipeline of the task now (`202`) |

Writes (`POST`, `PUT`) take a plain JSON body, validated by a DTO and handled by a state processor that goes through the core's factories and repositories. Relations in responses are IRIs.

Example:

```bash
curl -X POST /etl/workflows -H 'Content-Type: application/json' -d '{
  "name": "Import users",
  "stepConfiguration": [
    {"code": "etl.extractor.csv_file", "name": "read", "configuration": {"path": "/data/users.csv"}},
    {"code": "etl.loader.json_file",   "name": "write", "configuration": {"destination": "/data/users.json"}}
  ]
}'

curl -X POST /etl/workflows/{id}/execute -H 'Content-Type: application/json' -d '{"input": {"source": "manual"}}'
curl '/etl/pipeline_histories?pipeline={pipelineId}'
```

### Notes on execution

- `execute` moves the pipeline to `scheduled` before dispatching it, so the periodic scheduler (which picks up `pending` pipelines) does not start it twice. The core only runs `scheduled` pipelines, whatever its README says about `pending`.
- `ExecuteWorkflowCommand` is asynchronous: the response is returned as soon as the pipeline is queued. Poll `GET /pipelines/{id}`.
- A planned task needs a running `scheduler_etl` worker to produce pipelines on its own.

## Writing your own steps

Steps are plain core steps, nothing is specific to this bundle:

```php
#[AsExecutableStep(code: 'app.uppercase')]
final class UppercaseStep extends AbstractTransformerStep { /* ... */ }
```

The bundle ships one step, `etl.executor.console_command`, which runs a Symfony console command (`command`, `arguments`, `catchExceptions`) and returns its `exitCode` and `output`.

They show up in `GET /steps` and can be used as the `code` of a workflow step.

## Customising

- **Own entities**: the repositories and factories are tied to the bundled entities. To use your own, implement the core interfaces yourself and override the aliases declared by `#[AsAlias]` in this bundle from your application's `services.yaml` (not covered by the test suite).
- **API**: disable it (`api.enabled: false`) and expose your own resources on top of the repositories and the `CommandBus`.

## Testing

```bash
composer test:unit
composer test:integration   # needs a database, see below
composer phpstan
composer ecs
```

The integration suite boots a real kernel against the database in `ETL_TEST_DATABASE_URL` (default: PostgreSQL on `127.0.0.1:54329`) and recreates the schema before each test:

```bash
docker run --rm -d -p 54329:5432 -e POSTGRES_PASSWORD=test -e POSTGRES_DB=etl_test postgres:16-alpine
```

## License

MIT
