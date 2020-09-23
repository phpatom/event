<?php

namespace Atom\Event\Contracts;

use Psr\EventDispatcher\StoppableEventInterface;

interface EventContract extends StoppableEventInterface
{
    public function stopPropagation();
}
