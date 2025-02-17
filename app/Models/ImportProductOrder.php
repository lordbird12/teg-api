<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportProductOrder extends Model
{
    use HasFactory;

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function registerImporter()
    {
        return $this->belongsTo(RegisterImporter::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
