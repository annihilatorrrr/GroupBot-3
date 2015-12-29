<?php
/**
 * Created by PhpStorm.
 * User: Alexander
 * Date: 18/11/2015
 * Time: 6:51 PM
 */

namespace GroupBot\Brains\Blackjack\Database;


class SQL
{
    private $db;

    public function __construct()
    {
        $DbControl = new \GroupBot\Base\DbControl();
        $this->db = $DbControl->getObject();
    }

    public function insert_game($chat_id, $turn)
    {
        $sql = 'INSERT INTO bj_games (chat_id, turn) VALUES (:chat_id, :turn)';

        $query = $this->db->prepare($sql);
        $query->bindValue(':turn', $turn);
        $query->bindValue(':chat_id', $chat_id);

        return $query->execute();
    }

    public function insert_player($game_id, $user_id, $user_name, $card_str, $player_state, $player_no, $bet, $free_bet, $split)
    {
        $sql = 'INSERT INTO bj_players (user_id, user_name, game_id, cards, state, player_no, bet, free_bet, split)
                VALUES (:user_id, :user_name, :game_id, :cards, :state, :player_no, :bet, :free_bet, :split)';

        $query = $this->db->prepare($sql);
        $query->bindValue(':user_id', $user_id);
        $query->bindValue(':user_name', $user_name);
        $query->bindValue(':game_id', $game_id);
        $query->bindValue(':cards', $card_str);
        $query->bindValue(':state', $player_state);
        $query->bindValue(':player_no', $player_no);
        $query->bindValue(':bet', $bet);
        $query->bindValue(':free_bet', $free_bet);
        $query->bindValue(':split', $split);

        return $query->execute();
    }

    public function updatePlayer($id, $user_id, $game_id, $player_no, $card_str, $player_state, $bet, $split)
    {
        $sql = 'UPDATE bj_players
                SET cards = :cards, state = :state, bet = :bet, split = :split, player_no = :player_no
                WHERE user_id = :user_id AND game_id = :game_id AND id = :id';

        $query = $this->db->prepare($sql);
        $query->bindValue(':id', $id);
        $query->bindValue(':user_id', $user_id);
        $query->bindValue(':game_id', $game_id);
        $query->bindValue(':player_no', $player_no);
        $query->bindValue(':cards', $card_str);
        $query->bindValue(':state', $player_state);
        $query->bindValue(':bet', $bet);
        $query->bindValue(':split', $split);

        return $query->execute();
    }

    public function updateGame($game_id, $turn)
    {
        $sql = 'UPDATE bj_games SET turn = :turn WHERE id = :game_id';

        $query = $this->db->prepare($sql);
        $query->bindValue(':turn', $turn);
        $query->bindValue(':game_id', $game_id);

        return $query->execute();
    }

    public function delete($chat_id, $game_id)
    {
        $sql = 'DELETE FROM bj_games WHERE chat_id = :chat_id;
                DELETE FROM bj_players WHERE game_id = :game_id;';

        $query = $this->db->prepare($sql);
        $query->bindValue(':game_id', $game_id);
        $query->bindValue(':chat_id', $chat_id);

        return $query->execute();
    }

    public function select_game($chat_id)
    {
        $sql = 'SELECT id, turn FROM bj_games WHERE chat_id = :chat_id';

        $query = $this->db->prepare($sql);
        $query->bindValue(':chat_id', $chat_id);

        $query->execute();

        if ($query->rowCount()) {
            return $query->fetch();
        } else {
            return false;
        }
    }

    public function select_players($game_id)
    {
        $sql = 'SELECT id, user_id, user_name, cards, state, player_no, bet, free_bet, split FROM bj_players WHERE game_id = :game_id ORDER BY player_no ASC';

        $query = $this->db->prepare($sql);
        $query->bindValue(':game_id', $game_id);

        $query->execute();

        if ($query->rowCount()) {
            return $query->fetchAll();
        } else {
            return false;
        }
    }
}