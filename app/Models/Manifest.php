<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Manifest extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'note', 'updated_at', 'status', 'waybill_id', 'date_manifest'
    ];

    protected $dates =['date_manifest'];

    public function waybill(): BelongsTo{
        return $this->belongsTo(Waybill::class, 'waybill_id', 'id');
    }
}
