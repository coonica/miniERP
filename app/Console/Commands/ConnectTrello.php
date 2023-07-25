<?php

namespace App\Console\Commands;

use App\Mail\SynchTrelloMessage;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Booker;
use App\Models\InvoiceTask;
use App\Models\ListCard;
use App\Models\Member;
use App\Models\CardMember;
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
use Symfony\Component\Console\Command\Command as CommandAlias;

class ConnectTrello extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'sync:trello
    {--a|all : Command for sync Boards, Cards and members}
    {--b|boards : Command for sync Boards}
    {--c|cards : Command for sync Cards}
    {--m|members : Command for sync Members}';

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
  public function handle(TrelloSync $trelloSync)
  {
    $syncAll = $this->option('all');
    $syncBoards = $this->option('boards');
    $syncCards = $this->option('cards');
    $syncMembers = $this->option('members');
    $syncCardsFlag = $syncMembersFlag = false;

    // going through boards of existing bookers
    $bookers = Booker::all();
    $missedInfo = [];
    foreach ($bookers as $booker) {
      $api = new TrelloApi($booker->trello_token);
      $boards = $api->getBoardsByMember($booker->user->name);
      $createdBoards = $updatedBoards = $createdLists = $updatedLists = $createdCards = $updatedCards = $cardsWithMultipleTags = 0;
      $message = 'При синхронизации с Trello были выделены карточки с несколькими тегами: ';
      if ($syncBoards) {
        $this->info('Start sync boards');
      };
      foreach ($boards as $board) {
        // sync boards
        if ($syncBoards || $syncAll) {

          [$createdBoards, $updatedBoards] = $trelloSync->syncBoard($board);

          // sync boards lists
          $lists = $api->getListsByBoard($board['id']);

          $bar = $this->output->createProgressBar(count($lists));
          $bar->start();

          foreach ($lists as $list) {

            [$createdLists, $updatedLists] = $trelloSync->syncBoardList($list);

            $bar->advance();

          }

          $bar->finish();

          $this->newLine();

          $this->info("BoardLists has been created: $createdLists");
          $this->info("BoardLists has been updated: $updatedLists");

          $this->info("Boards has been created: $createdBoards");
          $this->info("Boards has been updated: $updatedBoards");

          $this->info("Boards synced successfully");

          if (!$syncCards && !$syncMembers && !$syncAll) {
            return CommandAlias::SUCCESS;
          }
        }
        // sync lists cards
        $cards = $api->getCardsByBoard($board['id']);

        if ($syncCards) {

          $this->info('Start sync cards');

        }
        if (($syncCards && $syncMembers) || $syncAll) {

          $this->info('Start sync members');

        }
        $bar = $this->output->createProgressBar(count($cards));

        foreach ($cards as $card) {

          if ($syncCards || $syncAll) {

            $syncCardsFlag = true;
            
            [$createdCards, $updatedCards, $cardsWithMultipleTags] = $trelloSync->syncListCard($card);

          }
          //check members for card
          if (!$syncAll && !$syncCardsFlag) {

            $this->info('Start sync members');

          }
          if ($syncMembers || $syncAll) {

            $syncMembersFlag = true;
            
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
          $bar->advance();
        }
        $bar->finish();
        if ($syncMembersFlag) {
          $this->newLine();
          $this->info("Members synced successfully");
        }
        if ($syncCardsFlag) {
          $this->info("Cards has been created: $createdCards");
          $this->info("Cards has been updated: $updatedCards");
        }
      }
    }

    if ($cardsWithMultipleTags > 0) {
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
      $users = User::all();
      foreach ($users as $user) {
        if ($user->isAdmin()) {
          Mail::to($user->email)->send(new SynchTrelloMessage($message));
        }
      }
    }
    return Command::SUCCESS;
  }




  public function sendMessage($recipient, $message)
  {
    mail($recipient, 'Synchronize trello tags', $message);
  }
}
