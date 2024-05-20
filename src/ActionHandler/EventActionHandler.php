<?php declare(strict_types=1);

namespace Polus\Adr\ActionHandler;

use PayloadInterop\DomainPayload;
use Polus\Adr\Interfaces\Action;
use Polus\Adr\Interfaces\EventAction;
use Polus\Adr\Interfaces\PayloadAware;
use Polus\Adr\Interfaces\Resolver;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class EventActionHandler implements Handler
{
    public function __construct(
        private Resolver $resolver,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function support(Action $action): bool
    {
        return $action instanceof EventAction;
    }

    public function handle(Action $action, ServerRequestInterface $request): ?DomainPayload
    {
        if (!$action instanceof EventAction) {
            return null;
        }
        $input = $this->resolver->resolve($action->getInput());
        $event = $this->eventDispatcher->dispatch($input($request));
        if ($event instanceof PayloadAware) {
            return $event->getPayload();
        }

        return null;
    }
}
