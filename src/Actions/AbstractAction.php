<?php declare(strict_types=1);

namespace Polus\Adr\Actions;

use Polus\Adr\Interfaces\Action;

abstract class AbstractAction implements Action
{
    protected ?string $input = null;
    protected ?string $responder = null;

    public function getInput(): ?string
    {
        return $this->input;
    }

    public function getResponder(): ?string
    {
        return $this->responder;
    }
}
