<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Waybill extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'waybill', 'courier', 'origin', 'destination', 'origin_address', 'destination_address', 'status', 'user_id', 'status_loop'
    ];

    public function manifests(): HasMany{
        return $this->hasMany(Manifest::class, 'waybill_id', 'id');
    }
}
