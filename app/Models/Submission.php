<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @property string $id
 * @property float $total_honey
 * @property \Carbon\Carbon $submission_date
 * @property string $member_id
 * @property string $evidence
 * @property float $amount
 * @property string|null $public_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Submission extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'submissions';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'total_honey',
        'submission_date',
        'member_id',
        'evidence',
        'amount',
        'public_id',
    ];

    protected $casts = [
        'total_honey' => 'float',
        'amount' => 'float',
    ];

    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }
}
