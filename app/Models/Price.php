<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @property string $id
 * @property float $price
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Price extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'prices';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'price',
    ];

    protected $casts = [
        'price' => 'float',
    ];
}
