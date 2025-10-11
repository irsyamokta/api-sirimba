<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @property string $id
 * @property string $title
 * @property float $amount
 * @property \Carbon\Carbon $transaction_date
 * @property string $category_id
 * @property string|null $note
 * @property string $type
 * @property string $evidence
 * @property string $payment_method
 * @property string $user_id
 * @property string|null $public_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Transaction extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'transactions';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'title',
        'amount',
        'transaction_date',
        'category_id',
        'note',
        'type',
        'evidence',
        'payment_method',
        'user_id',
        'public_id',
    ];

    protected $casts = [
        'amount' => 'float',
        'type' => 'string',
        'payment_method' => 'string',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
