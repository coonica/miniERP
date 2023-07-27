<?php

namespace App\Console\Commands;

use App\Services\TrelloApi;
use Illuminate\Console\Command;

class CleanTrelloCards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:cards';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean Trello cards';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(TrelloApi $api)
    {
        $cards = $api->getCardsByList('5bbb6f21fab0827f387951da');
        foreach($cards as $card){
            $api->deleteCard($card['id']);
        }

        return Command::SUCCESS;
    }
}
