<?php

namespace App\Message;

class SendMailReminder
{
private int $eventId;

    public function __construct(int $eventId)
    {
        $this->eventId = $eventId;
    }
    public function getEventId(): int
    {
        return $this->eventId;
    }
}