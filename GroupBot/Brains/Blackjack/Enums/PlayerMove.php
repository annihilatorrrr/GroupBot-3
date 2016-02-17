<?php
/**
 * Created by PhpStorm.
 * User: Alexander
 * Date: 18/11/2015
 * Time: 7:01 PM
 */

namespace GroupBot\Brains\Blackjack\Enums;


class PlayerMove extends \GroupBot\Brains\CardGame\Enums\PlayerMove
{
    const Hit = 5;
    const Stand = 6;
    const DoubleDown = 7;
    const Split = 8;
    const Surrender = 9;
}