<?php declare(strict_types=1);

namespace Polus\Adr\ActionDispatcher;

use PayloadInterop\DomainPayload;
use Polus\Adr\ActionHandler\DomainActionHandler;
use Polus\Adr\ActionHandler\Handler;
use Polus\Adr\DefaultExceptionHandler;
use Polus\Adr\EmptyDomainPayload;
use Polus\Adr\Interfaces\Action;
use Polus\Adr\Interfaces\ActionDispatcher;
use Polus\Adr\Interfaces\ExceptionHandler;
use Polus\Adr\Interfaces\Resolver;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class HandlerActionDispatcher implements ActionDispatcher
{
    /** @var Handler[] */
    private array $handlers;

    public static function default(
        Resolver $resolver,
        ResponseFactoryInterface $responseFactory,
    ): self {
        return new self(
            $resolver,
            $responseFactory,
            new DefaultExceptionHandler(),
            new DomainActionHandler($resolver)
        );
    }

    public function __construct(
        private readonly Resolver $resolver,
        private readonly ResponseFactoryInterface $responseFactory,
        private ExceptionHandler $exceptionHandler,
        Handler ...$handlers,
    ) {
        $this->handlers = $handlers;
    }

    public function addHandler(Handler $handler): void
    {
        $this->handlers[] = $handler;
    }

    public function setExceptionHandler(ExceptionHandler $exceptionHandler): void
    {
        $this->exceptionHandler = $exceptionHandler;
    }

    public function dispatch(Action $action, ServerRequestInterface $request): ResponseInterface
    {
        $payload = null;
        try {
            foreach ($this->handlers as $handler) {
                if ($handler->support($action)) {
                    $payload = $handler->handle($action, $request);
                    break;
                }
            }
        }
        catch (Throwable $de) {
            $result = $this->exceptionHandler->handle($de);
            if ($result instanceof ResponseInterface) {
                return $result;
            }

            if ($result instanceof DomainPayload) {
                $payload = $result;
            }
        }
        if (!$payload) {
            $payload = new EmptyDomainPayload();
        }

        $responder = $this->resolver->resolveResponder($action->getResponder());
        return $responder($request, $this->responseFactory->createResponse(), $payload);
    }
}
