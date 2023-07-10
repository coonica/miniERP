<?php

namespace App\Console\Commands;

use App\Mail\SynchTrelloMessage;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Booker;
use App\Models\InvoiceTask;
use App\Models\ListCard;
use App\Models\Member;
use App\Models\MemberCard;
use App\Models\MemberCardTime;
use App\Models\User;
use App\Services\TrelloApi;
use Faker\Generator;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Mockery\Exception;

class ConnectTrello extends Command
{
    /**
     * The name and signature of the console command.
     * php artisan sync:trello b -- sync only boards
     * php artisan sync:trello bc -- sync only boards and cards
     * php artisan sync:trello all -- sync all
     *
     *
     * @var string
     */
    protected $signature = 'sync:trello
                            {target? : Target subjects of sync (possible variants b, u, m, or combinations, and all combinations - all)}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Getting data via API Trello and updating model\'s data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //обработка аргумента
        $target = $this->argument('target') ?? 'all';

        $membersSyncCommands = ['all', 'm', 'cm', 'bm'];
        $cardsSyncCommands = ['all', 'c', 'bc', 'cm'];
        $boardsSyncCommands = ['all', 'b', 'bc', 'bm'];
        $allPossibleCommands = array_unique(array_merge($membersSyncCommands, $cardsSyncCommands, $boardsSyncCommands));
        if (!in_array($target, $allPossibleCommands, false)) {
            $this->error('Unhandled argument');
            return Command::INVALID;
        }

        $this->info("Start sync");
        $barCount = ($target === 'all') ? 3 : strlen($target);
        $bar = $this->output->createProgressBar($barCount);
        $bar->start();

        $faker = Container::getInstance()->make(Generator::class);
        // going through boards of existing bookers
        $bookers = Booker::all();
        $missedInfo = [];
        foreach ($bookers as $booker) {
            $api = new TrelloApi($booker->trello_token);
            $boards = $api->getBoardsByMember($booker->user->name);
            $createdBoards = $updatedBoards = $createdLists = $updatedLists = $createdCards = $updatedCards = $cardsWithMultipleTags = 0;
            $message = 'При синхронизации с Trello были выделены карточки с несколькими тегами: ';
            foreach ($boards as $board) {
                // sync boards
                if (in_array($target, $boardsSyncCommands, false)) {
                    $existBoard = Board::find($board['id']);
                    $data = [
                        'idBoard' => $board['id'],
                        'name' => $board['name']
                    ];
                    if (!$existBoard) {
                        Board::create($data);
                        $createdBoards++;
                    } else {
                        $existBoard->update($data);
                        $updatedBoards++;
                    }
                    // sync boards lists
                    $lists = $api->getListsByBoard($board['id']);
                    foreach ($lists as $list) {
                        $existList = BoardList::find($list['id']);
                        $data = [
                            'idList' => $list['id'],
                            'name' => $list['name'],
                            'pos' => $list['pos'],
                            'idBoard' => $list['idBoard']
                        ];
                        if (!$existList) {
                            BoardList::create($data);
                            $createdLists++;
                        } else {
                            $existList->update($data);
                            $updatedLists++;
                        }
                    }
                    $bar->advance();
                    $this->info(' : boards sync successfully');
                }


                // sync lists cards
                if (in_array($target, $cardsSyncCommands, false)) {
                    $cards = $api->getCardsByBoard($board['id']);
                    foreach ($cards as $card) {
                        //creating or adding card
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
                                $cardsWithMultipleTags++;
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

                        try {
                            if (InvoiceTask::where('tag', $tag)->first()) {
                                $data['invoice_task_tag'] = $tag;
                            }
                            if (!$existCard) {
                                ListCard::create($data);
                                $createdCards++;
                            } else {
                                $existCard->update($data);
                                $updatedCards++;
                            }
                        } catch (\Throwable $e) {
                            //нету листа такого, то есть надо борду обновить
                            continue;
                        }

                        //check members for card
                        if (in_array($target, $membersSyncCommands, false)) {
                            $members = $api->getMembersOfCard($card['id']);
                            foreach ($members as $trello_member) {
                                $member = Member::find($trello_member['id']);
                                if (!$member) {
                                    //adding user with faker's help
                                    $user = User::create([
                                        'name' => $trello_member['username'],
                                        'email' => $faker->unique()->safeEmail(),
                                        'password' => $faker->password(),
                                        'isActive' => 0
                                    ]);
                                    $member = Member::create([
                                        'id' => $trello_member['id'],
                                        'user_name' => $trello_member['username'],
                                        'user_id' => $user->id
                                    ]);
                                }
                                // add member to card if needed
                                if (!$member->listCards->find($card['id'])) {
                                    $member->listCards()->attach($card['id']);
                                    $member = $member->fresh();
                                }
                            }

                            // sync member first estimate and member spend time records from the card
                            $comments = $api->getCommentsByCard($card['id']);
                            foreach ($comments as $comment) {
                                $text = $comment['data']['text'];
                                if (Str::startsWith($text, 'plus! ') === false) {
                                    // not comment with time - skip it
                                    continue;
                                }
                                $member = Member::find($comment['idMemberCreator']);
                                // if user tracked time to the card but he was not its member - we'll create MemberCard record
                                if (!$member) {
                                    $memberCard = MemberCard::firstOrCreate(['list_card_idCard' => $card['id'], 'member_id' => $member->id]);
                                    $memberCardId = $memberCard->id;
                                    //adding estimate hour into pivot table
                                    if (Str::startsWith($text, 'plus! 0/')) {
                                        $time = $this->getTimeFromComment($comment['data']['text']);
                                        $estHour = $time[1];
                                        $member->listCards()->updateExistingPivot($card['id'], ['est_hour' => $estHour]);
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
                    }

                    if (in_array($target, $membersSyncCommands, false)) {
                        $bar->advance();
                        $bar->display();
                        $this->info(" : members for card sync successfully");

                    }

                    $bar->advance();
                    $bar->display();
                    $this->info(' : cards sync successfully');
                }

                if (!in_array($target, $cardsSyncCommands, false) && in_array($target, $membersSyncCommands, false)) {
                    $cards = ListCard::all();
                    foreach ($cards as $card) {
                        $members = $api->getMembersOfCard($card['idCard']);
                        foreach ($members as $trello_member) {
                            $member = Member::find($trello_member['id']);
                            if (!$member) {
                                //adding user with faker's help
                                $user = User::create([
                                    'name' => $trello_member['username'],
                                    'email' => $faker->unique()->safeEmail(),
                                    'password' => $faker->password(),
                                    'isActive' => 0
                                ]);
                                $member = Member::create([
                                    'id' => $trello_member['id'],
                                    'user_name' => $trello_member['username'],
                                    'user_id' => $user->id
                                ]);
                            }
                            // add member to card if needed
                            if (!$member->listCards->find($card['idCard'])) {
                                $member->listCards()->attach($card['idCard']);
                                $member = $member->fresh();
                            }

                        }

                        // sync member first estimate and member spend time records from the card
                        $comments = $api->getCommentsByCard($card['idCard']);
                        foreach ($comments as $comment) {
                            $text = $comment['data']['text'];
                            if (Str::startsWith($text, 'plus! ') === false) {
                                // not comment with time - skip it
                                continue;
                            }
                            $member = Member::find($comment['idMemberCreator']) ?? null;
                            if ($member->id !== null) {
                                try {
                                    // if user tracked time to the card but he was not its member - we'll create MemberCard record
                                    $memberCard = MemberCard::firstOrCreate(['list_card_idCard' => $card['idCard'], 'member_id' => $member->id]);
                                    $memberCardId = $memberCard->id;
                                    //adding estimate hour into pivot table
                                    if (Str::startsWith($text, 'plus! 0/')) {
                                        $time = $this->getTimeFromComment($comment['data']['text']);
                                        $estHour = $time[1];
                                        $member->listCards()->updateExistingPivot($card['idCard'], ['est_hour' => $estHour]);
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
                                } catch (\Throwable $e) {
                                    continue;
                                }
                            }

                        }
                    }

                    $bar->advance();
                    $bar->display();
                    $this->info(" : members for card sync successfully");

                }
            }
        }
        if (isset($cardsWithMultipleTags) && $cardsWithMultipleTags > 0) {
            foreach ($missedInfo as $item) {
                $message .= "<br/>$item[card] - был сохранен только первый тег: $item[saved_tag], остальные были проигнорированы: ";
                foreach ($item['missed_tags'] as $tag) {
                    if (last($item['missed_tags']) !== $tag) {
                        $message .= "$tag, ";
                    } else {
                        $message .= "$tag";
                    }
                }
            }
            /*
            $users = User::all();
            foreach ($users as $user) {
                if ($user->isAdmin()) {
                    Mail::to($user->email)->send(new SynchTrelloMessage($message));
                }
            }
            */
        }

        $this->newLine();
        $this->info('finished successfully');
        $this->newLine();

        return Command::SUCCESS;
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

    public function sendMessage($recipient, $message)
    {
        mail($recipient, 'Synchronize trello tags', $message);
    }

}
