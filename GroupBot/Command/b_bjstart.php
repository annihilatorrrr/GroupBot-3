<?php
/**
 * Created by PhpStorm.
 * User: Alexander
 * Date: 8/11/2015
 * Time: 12:14 AM
 */
namespace GroupBot\Command;

use GroupBot\Brains\Blackjack\BlackjackTelegram;
use GroupBot\Brains\Blackjack\Enums\PlayerMove;
use GroupBot\Types\Command;

class b_bjstart extends Command
{
    public function b_bjstart()
    {
        $bet = $this->isParam() ? $this->getAllParams() : 0;
        $Move = new PlayerMove(PlayerMove::StartGame);
        return BlackjackTelegram::getResponse($this->Message, $Move, $bet);
    }
}