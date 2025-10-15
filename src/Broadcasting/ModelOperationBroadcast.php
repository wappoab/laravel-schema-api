<?php

declare(strict_types=1);

namespace Wappo\LaravelSchemaApi\Broadcasting;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Wappo\LaravelSchemaApi\Support\ModelOperation;

class ModelOperationBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param mixed $userId
     * @param \Wappo\LaravelSchemaApi\Support\ModelOperation $operation
     */
    public function __construct(
        public mixed $userId,
        public ModelOperation $operation,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->userId}"),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'model.operation';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->operation->id,
            'type' => $this->operation->type,
            'op' => $this->operation->op->value,
            'attr' => $this->operation->attr,
        ];
    }
}
