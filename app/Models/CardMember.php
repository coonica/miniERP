<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CardMember extends Pivot
{
    protected $table = 'card_member';
    public $incrementing = true;

    protected $fillable = ['est_hour'];
    public $timestamps = false;

    public function memberCardTime() {
        return $this->hasMany(MemberCardTime::class, 'members_cards_id');
    }
}
