<?php

namespace App\Services;

use App\Models\Board;
use App\Models\BoardList;
use App\Models\CardMember;
use App\Models\InvoiceTask;
use App\Models\ListCard;
use App\Models\Member;
use App\Models\MemberCardTime;
use App\Models\User;
use Carbon\Carbon;
use Faker\Generator;
use Illuminate\Support\Str;
use Illuminate\Container\Container;


class TrelloSync
{
  protected $faker;
  protected $createdBoards;
  protected $updatedBoards;
  protected $createdLists;
  protected $updatedLists;
  protected $createdCards;
  protected $updatedCards;
  protected $cardsWithMultipleTags;

  public function __construct(protected TrelloApi $api)
  {
    $this->faker = Container::getInstance()->make(Generator::class);
  }

  public function syncBoard($board)
  {
    $existBoard = Board::find($board['id']);
    $data = [
      'idBoard' => $board['id'],
      'name' => $board['name']
    ];
    if (!$existBoard) {
      Board::create($data);
      $this->createdBoards++;
    } else {
      $existBoard->update($data);
      $this->updatedBoards++;
    }

    return [$this->createdBoards, $this->updatedBoards,];
  }

  public function syncBoardList($boardList)
  {
    $existList = BoardList::find($boardList['id']);
    $data = [
      'idList' => $boardList['id'],
      'name' => $boardList['name'],
      'pos' => $boardList['pos'],
      'idBoard' => $boardList['idBoard']
    ];
    if (!$existList) {
      BoardList::create($data);
      $this->createdLists++;
    } else {
      $existList->update($data);
      $this->updatedLists++;
    }

    return [$this->createdLists, $this->updatedLists,];
  }

  public function syncListCard ($card) { 
    $existCard = ListCard::find($card['id']);
    $tag = null;
    $countTags = Str::substrCount($card['name'], ' #');
    if ($countTags > 0) {
      $tags = Str::substr($card['name'], mb_strpos($card['name'], '#'));
      if ($countTags == 1) {
        $tag = $tags;
      } else {
        // there is more than 1 hashtag - send warning and sync only first
        $tags = explode(' ', $tags);
        $tag = $tags[0];
        unset($tags[0]);
        $missedInfo[] = [
          'card' => $card['name'],
          'saved_tag' => $tag,
          'missed_tags' => $tags
        ];
        $this->cardsWithMultipleTags++;
      }
    }
    $data = [
      'idCard' => $card['id'],
      'name' => $card['name'],
      'pos' => $card['pos'],
      'due' => $card['due'],
      'idList' => $card['idList'],
      'urlSource' => $card['shortUrl'], //or $card['url']
    ];

    if (InvoiceTask::where('tag', $tag)->first()) {
      $data['invoice_task_tag'] = $tag;
    }
    if (!$existCard) {
      ListCard::create($data);
      $this->createdCards++;
    } else {
      $existCard->update($data);
      $this->updatedCards++;
    }

    return [$this->createdCards, $this->updatedCards, $this->cardsWithMultipleTags];
  }

  public function saveMember($trello_member)
  {
    $member = Member::find($trello_member['id']);
    if (!$member) {
      //adding user with faker's help
      $user = User::create([
        'name' => $trello_member['username'],
        'email' => $this->faker->unique()->safeEmail(),
        'password' => $this->faker->password(),
        'isActive' => 0
      ]);
      $member = Member::create([
        'id' => $trello_member['id'],
        'user_name' => $trello_member['username'],
        'user_id' => $user->id
      ]);
    }
    return $member;
  }

  public function saveMemberCardTime($card_id)
  {
    $comments = $this->api->getCommentsByCard($card_id);
    foreach ($comments as $comment) {
      $text = $comment['data']['text'];
      if (Str::startsWith($text, 'plus! ') === false) {
        // not comment with time - skip it
        continue;
      }
      $member = Member::find($comment['idMemberCreator']);

      if (!$member) {
        // if user tracked time to the card but he was not its member - we'll create CardMember record
        $memberCard = CardMember::firstOrCreate(['list_card_idCard' => $card_id, 'member_id' => $member->id]);
        $memberCardId = $memberCard->id;
        //adding estimate hour into pivot table
        if (Str::startsWith($text, 'plus! 0/')) {
          $time = $this->getTimeFromComment($comment['data']['text']);
          $estHour = $time[1];
          $member->listCards()->updateExistingPivot($card_id, ['est_hour' => $estHour]);
          continue;
        }
        // adding new spent time records
        if (Str::startsWith($text, 'plus! ')) {
          $time = $this->getTimeFromComment($text);
          $note = $this->getNoteFromComment($text);
          $time_record_data = [
            'members_cards_id' => $memberCardId,
            'date' => Carbon::parse($comment['date'])->format('Y-m-d H:i:s'),
            'spent_time' => (float)$time[0],
          ];
          $time_record = MemberCardTime::where($time_record_data)->first();
          if ($time[0] > 0 && $time_record === null) {
            MemberCardTime::create(array_merge($time_record_data, ['note' => $note ?? null,]));
          }
        }
      }
    }
  }


  public function getTimeFromComment(string $comment)
  {
    return explode('/', explode(' ', $comment)[1]);
  }

  public function getNoteFromComment(string $comment)
  {
    $arr = explode(' ', $comment);
    unset($arr[0]);
    unset($arr[1]);
    return implode(' ', $arr);
  }
}
