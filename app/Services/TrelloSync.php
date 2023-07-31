<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MemberCardTime;
use App\Models\User;
use Faker\Generator;
use Illuminate\Container\Container;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class TrelloSync
{
    protected $faker;

    public function __construct(protected TrelloApi $api){
        $this->faker = Container::getInstance()->make(Generator::class);
    }

    // sync members
    public function saveMember($trello_member){
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

    // get member estimate and spend time
    public function saveMemberCardTime($card_id){
        $comments = $this->api->getCommentsByCard($card_id);
        foreach ($comments as $comment) {
            $text = $comment['data']['text'];
            if (Str::startsWith($text, 'plus! ') === false) {
                // not comment with time - skip it
                continue;
            }
            $member = Member::find($comment['idMemberCreator']);
            // if user tracked time to the card, but he was not its member - we'll create MemberCard record
            if (!$member) {
                $memberCard = MemberCard::firstOrCreate(['list_card_idCard' => $card_id, 'member_id' => $member->id]);
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
                        'spent_time' => (double)$time[0],
                    ];
                    $time_record = MemberCardTime::where($time_record_data)->first();
                    if ($time[0] > 0 && $time_record === null) {
                        MemberCardTime::create(array_merge($time_record_data, ['note' => $note ?? null,]));
                    }
                }

            }
        }
    }
    // sync boards

    // sync boards lists

    // sync boards lists cards


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
