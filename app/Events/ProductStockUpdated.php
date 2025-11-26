<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductStockUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $product;
    public $oldStock;
    public $newStock;

    /**
     * Create a new event instance.
     */
    public function __construct(Product $product, int $oldStock, int $newStock)
    {
        $this->product = $product;
        $this->oldStock = $oldStock;
        $this->newStock = $newStock;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('products'),
        ];
    }

    /**
     * Data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'product_id' => $this->product->id,
            'name' => $this->product->name,
            'old_stock' => $this->oldStock,
            'new_stock' => $this->newStock,
            'stock' => $this->newStock,
            'price' => $this->product->price,
            'is_active' => $this->product->is_active,
            'image' => $this->product->image,
            'updated_at' => $this->product->updated_at,
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'stock.updated';
    }
}
