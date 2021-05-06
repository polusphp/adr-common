<?php declare(strict_types=1);

namespace Polus\Tests\Adr\ActionHandler;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PayloadInterop\DomainPayload;
use PHPUnit\Framework\TestCase;
use Polus\Adr\ActionDispatcher\HandlerActionDispatcher;
use Polus\Adr\ActionHandler\DomainActionHandler;
use Polus\Adr\DefaultExceptionHandler;
use Polus\Adr\EmptyDomainPayload;
use Polus\Adr\ExceptionDomainPayload;
use Polus\Adr\Interfaces\DomainAction;
use Polus\Adr\Interfaces\Resolver;
use Polus\Adr\Interfaces\Responder;

class DomainActionHandlerTest extends TestCase
{
    public function testEmptyAction(): void
    {
        $testResponse = new Response();

        $responder = $this->createMock(Responder::class);
        $responder
            ->method('__invoke')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->isInstanceOf(EmptyDomainPayload::class)
            )
            ->willReturn($testResponse);

        $resolver = $this->createMock(Resolver::class);
        $resolver
            ->method('resolveResponder')
            ->willReturn($responder);

        $dispatcher = HandlerActionDispatcher::default(
            $resolver,
            new Psr17Factory(),
        );

        $response = $dispatcher->dispatch(
            new class implements DomainAction {
                public function getInput(): ?string
                {
                    return null;
                }

                public function getResponder(): ?string
                {
                    return null;
                }

                public function getDomain(): ?string
                {
                    return null;
                }
            },
            new ServerRequest('GET', '/')
        );

        $this->assertSame($testResponse, $response);
    }

    public function testInputAndDomain(): void
    {
        $testResponse = new Response();
        $responder = $this->createMock(Responder::class);
        $responder->method('__invoke')->willReturn($testResponse);

        $testPayload = $this->createMock(DomainPayload::class);

        $resolver = new class ($responder, $testPayload) implements Resolver {
            private Responder $responder;
            private DomainPayload $payload;

            public function __construct(Responder $responder, DomainPayload $payload)
            {
                $this->responder = $responder;
                $this->payload = $payload;
            }

            public function resolve(?string $class): callable
            {
                if ($class === 'domain') {
                    return function () {
                        return $this->payload;
                    };
                }
                return static function () {
                    return [];
                };
            }

            public function resolveResponder(?string $responder): Responder
            {
                return $this->responder;
            }
        };

        $dispatcher = new HandlerActionDispatcher(
            $resolver,
            new Psr17Factory(),
            new DefaultExceptionHandler(),
            new DomainActionHandler($resolver),
        );

        $response = $dispatcher->dispatch(
            new class () implements DomainAction
            {
                public function getInput(): ?string
                {
                    return 'input';
                }

                public function getResponder(): ?string
                {
                    return null;
                }

                public function getDomain(): ?string
                {
                    return 'domain';
                }
            },
            new ServerRequest('GET', '/')
        );

        $this->assertSame($testResponse, $response);
    }

    public function testResolveException(): void
    {
        $testResponse = new Response();

        $responder = $this->createMock(Responder::class);
        $responder
            ->method('__invoke')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->isInstanceOf(ExceptionDomainPayload::class)
            )
            ->willReturn($testResponse);

        $resolver = $this->createMock(Resolver::class);
        $resolver
            ->expects($this->once())
            ->method('resolve')
            ->willThrowException(new \DomainException('Resolver failed'));
        $resolver
            ->method('resolveResponder')
            ->willReturn($responder);

        $dispatcher = new HandlerActionDispatcher(
            $resolver,
            new Psr17Factory(),
            new DefaultExceptionHandler(),
            new DomainActionHandler($resolver),
        );

        $response = $dispatcher->dispatch(
            new class implements DomainAction {
                public function getInput(): ?string
                {
                    return null;
                }

                public function getResponder(): ?string
                {
                    return null;
                }

                public function getDomain(): ?string
                {
                    return 'error';
                }
            },
            new ServerRequest('GET', '/')
        );

        $this->assertSame($testResponse, $response);
    }
}
