<?php


namespace Atom\Event;

use InvalidArgumentException;
use Atom\Event\Contracts\EventContract;
use Atom\Event\Contracts\EventListenerContract;

abstract class AbstractEventListener implements EventListenerContract
{

    const PRIORITY_NORMAL = 0;
    const PRIORITY_MEDIUM = 100;
    const PRIORITY_HIGH = 200;
    const PRIORITY_SEVERE = 300;
    protected $priority = self::PRIORITY_NORMAL;

    /**
     * @var int
     */
    protected $calls = 0;
    protected $maxCall;

    abstract public function on($event):void;

    public function handle(EventContract $event): void
    {
        $this->calls++;
        $this->on($event);
    }

    public function once():self
    {
        $this->exactly(1);
        return $this;
    }

    public function never()
    {
        $this->exactly(0);
        return $this;
    }

    public function exactly(int $times):self
    {
        if ($times < 0) {
            throw new InvalidArgumentException("Parameter 1 of [exactly] should be a positive integer or 0");
        }
        $this->maxCall = $times;
        return $this;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function setPriority(int $priority):AbstractEventListener
    {
        $this->priority = $priority;
        return $this;
    }

    public function withPriority(int $priority):AbstractEventListener
    {
        $clone = clone $this;
        $clone->setPriority($priority);
        return $clone;
    }

    public function withHighPriority():AbstractEventListener
    {
        return $this->withPriority(self::PRIORITY_HIGH);
    }

    public function withNormalPriority():AbstractEventListener
    {
        return $this->withPriority(self::PRIORITY_NORMAL);
    }

    public function withMediumPriority():AbstractEventListener
    {
        return $this->withPriority(self::PRIORITY_MEDIUM);
    }

    public function withSeverePriority():AbstractEventListener
    {
        return $this->withPriority(self::PRIORITY_SEVERE);
    }

    public function canBeCalled(): bool
    {
        return $this->maxCall === null || $this->calls < $this->maxCall;
    }

    public function getCalls():int
    {
        return $this->calls;
    }

    public function getMaxCall():int
    {
        return $this->maxCall;
    }
}
