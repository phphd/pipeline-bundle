PhdPipelineBundle
-----------------

🧰 Provides [Symfony Messenger](https://symfony.com/doc/current/messenger.html) middleware for basic per-bus pipelining.
It enables streamlined chaining of the messages created by message handlers. For instance, when handler (hdl1) processes
message (msg1), it creates a subsequent message (msg2), triggering the invocation of the next
handler (hdl2), which may, in turn, produce yet another new message, and this cycle continues.

[![Build Status](https://github.com/phphd/pipeline-bundle/actions/workflows/ci.yaml/badge.svg?branch=main)](https://github.com/phphd/pipeline-bundle/actions?query=branch%3Amain)
[![Psalm level](https://shepherd.dev/github/phphd/pipeline-bundle/level.svg)](https://shepherd.dev/github/phphd/pipeline-bundle)
[![Psalm coverage](https://shepherd.dev/github/phphd/pipeline-bundle/coverage.svg)](https://shepherd.dev/github/phphd/pipeline-bundle)
[![Codecov](https://codecov.io/gh/phphd/pipeline-bundle/graph/badge.svg?token=GZRXWYT55Z)](https://codecov.io/gh/phphd/pipeline-bundle)
[![Licence](https://img.shields.io/github/license/phphd/pipeline-bundle.svg)](https://github.com/phphd/pipeline-bundle/blob/main/LICENSE)

## Installation 📥

1. Install via composer

    ```sh
    composer require phphd/pipeline-bundle
    ```

2. Enable the bundle in the `bundles.php`

    ```php
    PhPhD\PipelineBundle\PhdPipelineBundle::class => ['all' => true],
    ```

## Configuration ⚒️

To leverage chain of pipelined handlers for your command/query buses, 
you should add `phd_pipeline.forwarding` middleware to the list:

```diff
framework:
    messenger:
        buses:
            command.bus:
                middleware:
                    - doctrine_transaction
+                   - phd_pipeline.forwarding
                    - validation
            query.bus:
                middleware:
+                   - phd_pipeline.forwarding
                    - validation
```

## Usage 🚀

Consider having this _original message_ that is initially dispatched to the message bus:

```php
final readonly class CreateVacationRequestCommandDto
{
    public function __construct(
        public int $userId,
        public int $vacationTypeId,
        #[Assert\DateTime]
        public string $startDate,
        #[Assert\DateTime]
        public string $endDate,
    ) {
    }
}
```

Thy upfront message handler _returns a new message_ that will be used for subsequent redispatch:

```php
#[AsMessageHandler(bus: 'command.bus')]
final readonly class ConvertVacationRequestCommandHandler
{
    public function __invoke(CreateVacationRequestCommandDto $dto): CreateVacationRequestCommand
    {
        $employee = $this->employeeRepository->find($dto->userId);
        $vacationType = $this->vacationTypeRepository->find($dto->vacationTypeId);

        $vacationPeriod = VacationPeriod::fromStringDates($dto->startDate, $dto->endDate);

        return new CreateVacationRequestCommand($employee, $vacationType, $vacationPeriod);
    }
}
```

The _new created message_ conveys basically the same business concept, but on the _higher level of abstraction_ than
initially. Thereof, instead of scalar types, it has business objects (e.g. `VacationType` entity instead
of `$vacationTypeId` scalar). Basically, new class no longer merely represents the DTO. It now embodies the complete
domain object.

You should add `#[PipeForward]` _attribute to enable forwarding_ of this new message to the next handler:

```php
use PhPhD\Pipeline\PipeForward;

#[PipeForward]
final readonly class CreateVacationRequestCommand
{
    public function __construct(
        public Employee $employee,
        public VacationType $vacationType,
        public VacationPeriod $vacationPeriod,
    ) {
    }
}
```

> Messages lacking `#[PipeForward]` attribute will not be pipelined. This attribute must be put on each message
> expected of redispatching.

Finally, one _ultimate handler_ must implement the core business logic.
It may or may not return a result to the calling code.

```php
#[AsMessageHandler(bus: 'command.bus')]
final readonly class CreateVacationRequestHandler
{
    public function __invoke(CreateVacationRequestCommand $command)
    {
        // The core business logic that deals with domain entities rather than primitives...
    }
}
```

> You may chain as many message handlers as needed, even in a recursive manner,
> by returning an instance of the same class as the original message, 
> provided that it has the forwarding attribute enabled.
