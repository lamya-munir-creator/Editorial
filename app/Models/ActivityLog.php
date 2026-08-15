<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action_type',
        'action_label',
        'target_name',
        'target_url',
    ];

    /**
     * العلاقة مع المستخدم الذي قام بالنشاط
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}