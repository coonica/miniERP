<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id', 'user_name', 'user_id', 'rate'];

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function listCards() {
        return $this->belongsToMany(ListCard::class, 'card_member', 'member_id', 'list_card_idCard')
            ->using(CardMember::class)->withPivot('id', 'est_hour');
    }
}
