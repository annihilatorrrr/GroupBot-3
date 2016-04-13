<?php
/**
 * Created by PhpStorm.
 * User: Alexander
 * Date: 18/11/2015
 * Time: 6:51 PM
 */

namespace GroupBot\Brains\Blackjack;


use GroupBot\Brains\CardGame\Enums\GameResult;
use GroupBot\Brains\Blackjack\Types\Game;
use GroupBot\Brains\Blackjack\Types\Player;
use GroupBot\Brains\Blackjack\Types\Stats;
use GroupBot\Brains\CardGame\Enums\GameType;
use GroupBot\Database\User;

class SQL extends \GroupBot\Brains\CardGame\SQL
{
    /**
     * @param $chat_id
     * @return bool
     */
    public function insert_game($chat_id)
    {
        $sql = 'INSERT INTO bj_games (chat_id, turn) VALUES (:chat_id, \'join\')';

        $query = $this->db->prepare($sql);
        $query->bindValue(':chat_id', $chat_id);

        return $query->execute();
    }

    /**
     * @param $game_id
     * @param Player $player
     * @return bool
     */
    public function insert_player($game_id, \GroupBot\Brains\CardGame\Types\Player $player)
    {
        $sql = 'INSERT INTO bj_players (user_id, user_name, game_id, cards, state, player_no, bet, free_bet, split, last_move_time)
                VALUES (:user_id, :user_name, :game_id, :cards, :state, :player_no, :bet, :free_bet, :split, NOW())';
        $query = $this->db->prepare($sql);
        $query->bindValue(':user_id', $player->user->user_id);
        $query->bindValue(':user_name', $player->user->user_name);
        $query->bindValue(':game_id', $game_id);
        $query->bindValue(':cards', $player->Hand->handToDbString());
        $query->bindValue(':state', $player->State);
        $query->bindValue(':player_no', $player->player_no);
        $query->bindValue(':bet', $player->bet);
        $query->bindValue(':free_bet', $player->free_bet);
        $query->bindValue(':split', $player->split);

        return $query->execute();
    }

    /**
     * @param Player $player
     * @param $game_id
     * @return bool
     */
    public function update_player(\GroupBot\Brains\CardGame\Types\Player $player, $game_id)
    {
        $sql = 'UPDATE bj_players
                SET cards = :cards, state = :state, bet = :bet, split = :split, player_no = :player_no,
                no_stands = :no_stands, no_hits = :no_hits, no_blackjacks = :no_blackjacks, no_splits = :no_splits,
                no_doubledowns = :no_doubledowns, no_surrenders = :no_surrenders, last_move_time = NOW()
                WHERE user_id = :user_id AND game_id = :game_id AND id = :id';

        $query = $this->db->prepare($sql);
        $query->bindValue(':id', $player->id);
        $query->bindValue(':user_id', $player->user->user_id);
        $query->bindValue(':game_id', $game_id);
        $query->bindValue(':player_no', $player->player_no);
        $query->bindValue(':cards', $player->Hand->handToDbString());
        $query->bindValue(':state', $player->State);
        $query->bindValue(':bet', $player->bet);
        $query->bindValue(':split', $player->split);

        $query->bindValue(':no_hits', $player->no_hits);
        $query->bindValue(':no_stands', $player->no_stands);
        $query->bindValue(':no_blackjacks', $player->no_blackjacks);
        $query->bindValue(':no_splits', $player->no_splits);
        $query->bindValue(':no_doubledowns', $player->no_doubledowns);
        $query->bindValue(':no_surrenders', $player->no_surrenders);

        return $query->execute();
    }

    /**
     * @param \GroupBot\Brains\CardGame\Types\Game $game
     * @return bool
     */
    public function update_game(\GroupBot\Brains\CardGame\Types\Game $game)
    {
        $sql = 'UPDATE bj_games SET turn = :turn WHERE id = :game_id';

        $query = $this->db->prepare($sql);
        $query->bindValue(':turn', $game->turn);
        $query->bindValue(':game_id', $game->game_id);

        return $query->execute();
    }

    /**
     * @param $chat_id
     * @param $game_id
     * @return bool
     */
    public function delete_game($chat_id, $game_id)
    {
        $sql = 'DELETE FROM bj_games WHERE chat_id = :chat_id';
        $query1 = $this->db->prepare($sql);
        $query1->bindParam(':chat_id', $chat_id, \PDO::PARAM_INT);

        $sql =  'DELETE FROM bj_players WHERE game_id = :game_id';
        $query2 = $this->db->prepare($sql);
        $query2->bindParam(':game_id', $game_id, \PDO::PARAM_INT);

        return $query1->execute() && $query2->execute();
    }

    /**
     * @param $chat_id
     * @return bool|Game
     */
    public function select_game($chat_id)
    {
        $sql = 'SELECT id, turn FROM bj_games WHERE chat_id = :chat_id';

        $query = $this->db->prepare($sql);
        $query->bindValue(':chat_id', $chat_id);

        $query->execute();

        if ($query->rowCount()) {
            $game = $query->fetch();
            return new Game($this->db, new GameType(GameType::Blackjack), $chat_id, $game['id'], $game['turn'], $this->select_players($game['id']));
        }
        return false;
    }

    /**
     * @param $game_id
     * @return Player|bool
     */
    public function select_players($game_id)
    {
        $sql = 'SELECT * FROM bj_players WHERE game_id = :game_id ORDER BY player_no ASC';

        $query = $this->db->prepare($sql);
        $query->bindValue(':game_id', $game_id);
        $query->execute();

        if ($query->rowCount()) {
            $Players = $query->fetchAll(\PDO::FETCH_CLASS, 'GroupBot\Brains\Blackjack\Types\Player');

            usort($Players, function($a, $b) {
                if ($a->player_no == $b->player_no) return 0;
                return $a->player_no < $b->player_no ? -1 : 1;
            });

            $DbUser = new User($this->db);
            foreach ($Players as $key => $player) {
                $Players[$key]->user = $DbUser->getUserFromId($player->user_id);
                unset($Players[$key]->user_id);
            }

            return $Players;
        }
        return false;
    }

    /**
     * @param $user_id
     * @return Stats|bool
     */
    public function select_player_stats($user_id)
    {
        $sql = 'SELECT * FROM bj_stats WHERE user_id = :user_id';

        $query = $this->db->prepare($sql);
        $query->bindValue(':user_id', $user_id);
        $query->execute();

        if ($query->rowCount()) {
            $query->setFetchMode(\PDO::FETCH_CLASS, 'GroupBot\Brains\Blackjack\Types\Stats');
            return $query->fetch();
        }
        return false;
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function update_stats(\GroupBot\Brains\CardGame\Types\Player $player)
    {
        $sql = 'INSERT INTO bj_stats
                  (user_id, games_played, wins, losses, draws, hits, stands, blackjacks, splits, doubledowns, surrenders, total_coin_bet, coin_won, coin_lost, free_bets)
                VALUES
                  (:user_id, 1, :wins, :losses, :draws, :hits, :stands, :blackjacks, :splits, :doubledowns, :surrenders, :bet, :coin_won, :coin_lost, :free_bets)
                ON DUPLICATE KEY UPDATE
                  games_played = games_played + 1,
                  hits = hits + :hits2,
                  stands = stands + :stands2,
                  blackjacks = blackjacks + :blackjacks2,
                  splits = splits + :splits2,
                  doubledowns = doubledowns + :doubledowns2,
                  surrenders = surrenders + :surrenders2,
                  total_coin_bet = total_coin_bet + :bet2,
                  free_bets = free_bets + :free_bets2,
                  coin_won = coin_won + :coin_won2,
                  coin_lost = coin_lost + :coin_lost2,
                  ';
        switch ($player->game_result) {
            case GameResult::Win:
                $sql .= 'wins = wins + 1';
                break;
            case GameResult::Loss:
                $sql .= 'losses = losses + 1';
                break;
            case GameResult::Draw:
                $sql .= 'draws = draws + 1';
                break;
        }

        $query = $this->db->prepare($sql);

        switch ($player->game_result) {
            case GameResult::Win:
                $query->bindValue(':wins', 1);
                $query->bindValue(':losses', 0);
                $query->bindValue(':draws', 0);
                break;
            case GameResult::Loss:
                $query->bindValue(':wins', 0);
                $query->bindValue(':losses', 1);
                $query->bindValue(':draws', 0);
                break;
            case GameResult::Draw:
                $query->bindValue(':wins', 0);
                $query->bindValue(':losses', 0);
                $query->bindValue(':draws', 1);
                break;
        }

        $query->bindValue(':hits', $player->no_hits);
        $query->bindValue(':stands', $player->no_stands);
        $query->bindValue(':blackjacks', $player->no_blackjacks);
        $query->bindValue(':splits', $player->no_splits);
        $query->bindValue(':doubledowns', $player->no_doubledowns);
        $query->bindValue(':surrenders', $player->no_surrenders);
        $query->bindValue(':bet', $player->free_bet ? 0 : $player->bet);
        $query->bindValue(':free_bets', $player->free_bet ? 1 : 0);

        $query->bindValue(':hits2', $player->no_hits);
        $query->bindValue(':stands2', $player->no_stands);
        $query->bindValue(':blackjacks2', $player->no_blackjacks);
        $query->bindValue(':splits2', $player->no_splits);
        $query->bindValue(':doubledowns2', $player->no_doubledowns);
        $query->bindValue(':surrenders2', $player->no_surrenders);
        $query->bindValue(':bet2', $player->free_bet ? 0 : $player->bet);
        $query->bindValue(':free_bets2', $player->free_bet ? 1 : 0);

        if ($player->bet_result > 0) {
            $query->bindValue(':coin_won', $player->bet_result);
            $query->bindValue(':coin_lost', 0);
            $query->bindValue(':coin_won2', $player->bet_result);
            $query->bindValue(':coin_lost2', 0);
        } elseif ($player->bet_result < 0) {
            $query->bindValue(':coin_won', 0);
            $query->bindValue(':coin_lost', abs($player->bet_result));
            $query->bindValue(':coin_won2', 0);
            $query->bindValue(':coin_lost2', abs($player->bet_result));
        } else {
            $query->bindValue(':coin_won', 0);
            $query->bindValue(':coin_lost', 0);
            $query->bindValue(':coin_won2', 0);
            $query->bindValue(':coin_lost2', 0);
        }

        $query->bindValue(':user_id', $player->user->user_id);

        return $query->execute();
    }
}