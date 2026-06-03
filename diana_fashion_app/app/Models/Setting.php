<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type'
    ];

    /**
     * Helper to get typed value.
     */
    public function getTypedValueAttribute()
    {
        $val = $this->value;
        switch ($this->type) {
            case 'integer':
                return intval($val);
            case 'boolean':
                return filter_var($val, FILTER_VALIDATE_BOOLEAN);
            case 'float':
                return floatval($val);
            default:
                return $val;
        }
    }
}
