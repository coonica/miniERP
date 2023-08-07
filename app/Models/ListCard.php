<?php

namespace App\Models;

use App\Services\TrelloApi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListCard extends Model
{
    use HasFactory;

    protected $primaryKey = 'idCard';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['idCard', 'name', 'idList', 'pos', 'due', 'urlSource', 'invoice_task_tag'];

    public function boardList() {
        return $this->belongsTo(BoardList::class, 'idList');
    }

    public function members() {
        return $this->belongsToMany(Member::class, 'card_member')->using(CardMember::class)->withPivot('id', 'est_hour');
    }

    public function invoiceTask() {
        return $this->belongsTo(InvoiceTask::class, 'invoice_task_tag');
    }
    protected static function booted()
    {
        static::creating(function ($card) {
//            $api = new TrelloApi();
//            $trello_card = $api->addCard($card);
////            dd($trello_card);
//            $card->idCard = $trello_card['id'];
//            $card->pos = $trello_card['pos'];
//            $card->urlSource = $trello_card['url'];
////            dd($card);
          if (!isset($card->idCard)){
                // calling from SOA - we need card id from trello
                $api = new TrelloApi();
                $trello_card = $api->addCard($card);
                $card->idCard = $trello_card['id'];
                $card->pos = $trello_card['pos'];
                $card->urlSource = $trello_card['url'];
            }
        });
    }
}
