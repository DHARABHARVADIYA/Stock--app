<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    protected $table = 'businesses';

    protected $fillable = [
        'business_name',
        'owner_name',
        'owner_number',
        'gst_number',
        'email',
    ];

    protected $appends = [
        'name',
        'ownerName',
        'ownerNumber',
        'gst',
    ];

    /* ================= ACCESSORS (SAFE) ================= */

    public function getNameAttribute()
    {
        return $this->attributes['business_name'] ?? null;
    }

    public function getOwnerNameAttribute()
    {
        return $this->attributes['owner_name'] ?? null;
    }

    public function getOwnerNumberAttribute()
    {
        return $this->attributes['owner_number'] ?? null;
    }

    public function getGstAttribute()
    {
        return $this->attributes['gst_number'] ?? null;
    }

    /* ================= RELATION ================= */

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }
}
