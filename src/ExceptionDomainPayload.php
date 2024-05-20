<?php declare(strict_types=1);

namespace Polus\Adr;

use PayloadInterop\DomainPayload;
use PayloadInterop\DomainStatus;

final readonly class ExceptionDomainPayload implements DomainPayload
{
    public function __construct(
        private \Throwable $exception,
    ) {}

    public function getException(): \Throwable
    {
        return $this->exception;
    }

    public function getStatus(): string
    {
        return DomainStatus::ERROR;
    }

    /**
     * @return array<string, mixed>
     */
    public function getResult(): array
    {
        return [
            'exception' => [
                'message' => $this->exception->getMessage(),
                'code' => $this->exception->getCode(),
            ]
        ];
    }
}
