<?php

namespace Atom\Event\Test;

use Atom\Event\Contracts\EventContract;
use Atom\Event\Contracts\EventListenerContract;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class BaseEventTest extends TestCase
{
    protected function getClassName($className)
    {
        $path = explode('\\', $className);
        return array_pop($path);
    }
    /**
     * @return EventListenerContract | MockObject
     */
    protected function buildListener()
    {
        return $this->getMockBuilder(EventListenerContract::class)
            ->setMockClassName($this->getClassName(EventListenerContract::class)."_".rand())->getMock();
    }

    /**
     * @return EventContract | MockObject
     */
    protected function buildEvent()
    {
        return  $this->getMockBuilder(EventContract::class)
            ->setMockClassName($this->getClassName(EventContract::class)."_".rand())->getMock();
    }
}
