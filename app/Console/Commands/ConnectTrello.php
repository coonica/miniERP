<?php

namespace App\Console\Commands;

use App\Mail\SynchTrelloMessage;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Booker;
use App\Models\CardMember;
use App\Models\InvoiceTask;
use App\Models\ListCard;
use App\Models\Member;
use App\Models\MemberCardTime;
use App\Models\User;
use App\Services\TrelloApi;
use App\Services\TrelloSync;
use Faker\Generator;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ConnectTrello extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    /*
     * php artisan sync:trello boards
     * php artisan sync:trello lists
     */
    protected $signature = 'sync:trello {upd?*}';

    /**
     * The console command description.
     *
     * @var string
     *
     * php artisan sync:trello
     * php artisan sync:trello boards
     * php artisan sync:trello lists
     * php artisan sync:trello cards
     * php artisan sync:trello members
     *
     */
    protected $description = 'Getting data via API Trello and updating model\'s data. Wright argument boards for synchronization boards, lists - for synchronization lists, cards - for synchronization cards, members - for synchronization cards and members (members cannot be synced without cards) or write command without arguments for sync all elements';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(TrelloSync $trelloSync)
    {
        $faker = Container::getInstance()->make(Generator::class);
        // going through boards of existing bookers
        $bookers = Booker::all();
        $missedInfo = [];
        $syncList = $this->argument('upd');
        foreach ($bookers as $booker) {
            $api = new TrelloApi($booker->trello_token);
            $boards = $api->getBoardsByMember($booker->user->name);
            $createdBoards = $updatedBoards = $createdLists = $updatedLists = $createdCards = $updatedCards = $cardsWithMultipleTags = 0;
            $message = 'При синхронизации с Trello были выделены карточки с несколькими тегами: ';
            foreach ($boards as $board) {
                $existBoard = Board::find($board['id']);
                // sync boards
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

                if (count($syncList) > 0 && !in_array('boards', $syncList)){
                    $this->error("Boards has been created/updated automatically");
                }

                $this->info("Boards has been created: $createdBoards");
                $this->info("Boards has been updated: $updatedBoards");

                if (count($syncList) == 0 || in_array('lists', $syncList)){
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
                    $this->info("Lists has been created: $createdLists");
                    $this->info("Lists has been updated: $updatedLists");
                }
                if (count($syncList) == 0 || in_array('cards', $syncList) || in_array('members', $syncList)){
                    // sync lists cards
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
                        if(InvoiceTask::where('tag', $tag)->first()) {
                            $data['invoice_task_tag'] = $tag;
                        }
                        if (!$existCard) {
                            ListCard::create($data);
                            $createdCards++;
                        } else {
                            $existCard->update($data);
                            $updatedCards++;
                        }
                        if (count($syncList) == 0 || in_array('members', $syncList)){
                            //check members for card
                            $members = $api->getMembersOfCard($card['id']);
                            foreach ($members as $trello_member) {
                                $member = $trelloSync->saveMember($trello_member);
                                // add member to card if needed
                                if (!$member->listCards->find($card['id'])) {
                                    $member->listCards()->attach($card['id']);
                                    $member = $member->fresh();
                                }
                            }
                            // sync member first estimate and member spend time records from the card
                            $trelloSync->saveMemberCardTime($card['id']);
                        }
                    }
                    if (!in_array('cards', $syncList) && in_array('members', $syncList)){
                        $this->error("Members cannot be synced without cards. Cards will be created and updated.");
                    }
                    if (count($syncList) == 0 || in_array('members', $syncList)){
                        $this->info("Membres has been created");
                    }
                    $this->info("Cards has been created: $createdCards");
                    $this->info("Cards has been updated: $updatedCards");
                }
            }
        }
        if($cardsWithMultipleTags > 0) {
            foreach ($missedInfo as $item) {
                $message .= "<br/>$item[card] - был сохранен только первый тег: $item[saved_tag], остальные были проигнорированы: ";
                foreach ($item['missed_tags'] as $tag) {
                    if(last($item['missed_tags']) !== $tag) {
                        $message .= "$tag, ";
                    } else {
                        $message .= "$tag";
                    }
                }
            }
            // $users = User::all();
            // foreach ($users as $user) {
            //     if ($user->isAdmin()) {
            //         Mail::to($user->email)->send(new SynchTrelloMessage($message));
            //     }
            // }
        }
        return Command::SUCCESS;
    }

    public function sendMessage($recipient, $message) {
        mail($recipient, 'Synchronize trello tags', $message);
    }
}
