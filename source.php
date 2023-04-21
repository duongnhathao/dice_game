<?php

class Player
{
    public int $user_id;
    public int $number_of_dice_per_roll;
    public bool $first_roll = true;
    public int $score = 0;
    public array $last_roll = [];
    public array $roll_histories = [];

    public function __construct(int $number_of_dice_per_roll, int $user_id = null)
    {
        $this->number_of_dice_per_roll = $number_of_dice_per_roll;
        $this->user_id = $user_id;
    }

    /**
     * Calculate score for player
     * @return void
     */
    public final function calculateScore(): void
    {
        //add 1 score for each dice has value 6 from last roll and remove it from last roll
        $this->score += count(array_filter($this->last_roll, function ($dice) {
            return $dice == 6;
        }));
        $this->last_roll = array_filter($this->last_roll, function ($dice) {
            return $dice != 6;
        });
    }


    /**
     * Get number of dice has value 1 from last roll
     * @return int
     */
    public final function numberOfDiceHasValue1FromLastRoll(): int
    {
        return count(array_filter($this->last_roll, function ($dice) {
            return $dice == 1;
        }));
    }


    /**
     * Remove dices has value 1 from last roll
     * @return void
     */
    public final function removeDiceHasValue1FromLastRoll(): void
    {
        $this->last_roll = array_filter($this->last_roll, function ($dice) {
            return $dice != 1;
        });
    }

    /**
     * Add number of dice has value 1 to last roll
     * @param int $number_of_dice
     * @return void
     */
    public final function addNumberOfDiceHasValue1ToLastRoll(int $number_of_dice): void
    {
        for ($i = 0; $i < $number_of_dice; $i++) {
            $this->last_roll[] = 1;
        }
    }

    /**
     * Check player can play or not by number of dice in last roll
     * @return bool
     */
    public final function canPlay(): bool
    {
        return $this->numberOfDiceInLastRoll() > 0;
    }

    /**
     * Get number of dice in last roll
     * @return int
     */
    public final function numberOfDiceInLastRoll(): int
    {
        return count($this->last_roll) ?? 0;
    }

    /**
     * Roll dice
     * @return void
     */
    public final function roll(): void
    {
        $numberOfDice = $this->numberOfDiceInLastRoll();
        if ($this->first_roll) {
            $this->first_roll = false;
            $numberOfDice = $this->number_of_dice_per_roll;
        }
        $this->last_roll = [];
        for ($i = 0; $i < $numberOfDice; $i++) {
            $this->last_roll[] = rand(1, 6);
        }
        $this->roll_histories[] = $this->last_roll;
    }
}

class RollDiceGame
{
    public array $players;
    public int $round = 1;
    public array $player_results = [];
    public int $number_of_dice_per_roll = 1;
    public bool $show_log = false;
    public int|false $round_limit = false;

    public function __construct(array|int $playerIds, int $number_of_dice_per_roll = 1, bool $show_log = false, int|false $round_limit = false)
    {
        if (is_int($playerIds)) {
            $playerIds = range(1, $playerIds);
        }

        echo "Game created...\n";
        $this->number_of_dice_per_roll = $number_of_dice_per_roll;

        echo "Adding players...\n";
        foreach ($playerIds as $playerId) {
            $this->players[] = new Player($this->number_of_dice_per_roll, $playerId);
        }
        $this->show_log = $show_log;
        $this->round_limit = $round_limit;
    }

    /**
     * Play game
     * @return void
     */
    public final function play(): void
    {
        echo "Game started...\n";
        //check game is over or not
        if ($this->isGameOver()) {
            $this->showResult();
            return;
        }
        echo "First rolling dice for each player...\n";
        $this->evaluate();
        //do not let game play more than 100 times
        $round = 1;
        while (!$this->isGameOver() && ($this->round_limit === false || $round < $this->round_limit)) {
            $this->evaluate();
            $round++;
        }
        $this->showResult();
    }

    /**
     * Check game is over or not
     * @return bool
     */
    public final function isGameOver(): bool
    {
        return count($this->players) <= 1;
    }

    /**
     * Show result of game
     * @return void
     */
    public final function showResult(): void
    {
        echo "\n>>>>>>>>>>>>Game over<<<<<<<<<<<<<\n\n";
        $winners = $this->getWinner();
        echo "==================== Game Result ====================\n";
        if (count($winners) == 1) {
            echo "Player {$winners[0]->user_id} is winner with score {$winners[0]->score}\n";
        } else {
            $winnerIds = [];
            foreach ($winners as $winner) {
                $winnerIds[] = $winner->user_id;
            }
            echo "Players " . implode(', ', $winnerIds) . " are winners with score {$winners[0]->score}\n";
        }

        echo "==================== Game Detail ====================\n";
        foreach ($this->player_results as $player) {
            echo "Player {$player->user_id} has score {$player->score}\n";
        }
        //history of each player
        echo "==================== Player Histories ====================\n";
        foreach ($this->player_results as $player) {
            //show round played by player
            $round = count($player->roll_histories);
            echo "Player {$player->user_id} played {$round} rounds\n";
        }
    }


    /**
     * Get winner of game
     * @return array
     */
    public final function getWinner(): array
    {
        echo "Getting winner...\n";
        //get element from players array and add to player result
        $this->player_results = [...$this->player_results, ...$this->players];
        $maxScore = 0;
        //get max score in player result
        foreach ($this->player_results as $player) {
            if ($player->score > $maxScore) {
                $maxScore = $player->score;
            }
        }
        $winners = [];
        //get all player has max score
        foreach ($this->player_results as $player) {
            if ($player->score == $maxScore) {
                $winners[] = $player;
            }
        }
        return $winners;
    }

    /**
     * Evaluate game
     * @return void
     */
    public final function evaluate(): void
    {
        echo "\n\n==================== Round {$this->round} ====================\n";
        echo "------------Rolling dice for each player------------\n\n";
        foreach ($this->players as $player) {
            if ($player instanceof Player) {
                $player->roll();
            } else {
                echo "Player {$player->user_id} is not instance of Player class\n";
            }
            echo "Player {$player->user_id} ({$player->score}) rolled: " . implode(', ', $player->last_roll) . "\n";
        }
        echo "\n------------Evaluating scores------------\n";
        $this->evaluateScores();
        $this->round++;
    }

    /**
     * Evaluate scores
     * @return void
     */
    public final function evaluateScores(): void
    {
        $transferDice = [];
        foreach (array_keys($this->players) as $index => $key) {
            $player = $this->players[$key];
            //calculate score for each player
            $player->calculateScore();

            //number of dice has value 1 from last roll
            $numberOfDiceHasValue1FromLastRoll = $player->numberOfDiceHasValue1FromLastRoll();

            //check last roll has dice has value 1 or not
            if ($numberOfDiceHasValue1FromLastRoll > 0) {

                if ($this->show_log) {
                    echo "\nPlayer {$player->user_id} has {$numberOfDiceHasValue1FromLastRoll} dice has value 1\n";
                    echo "Remove dice has value 1 from player {$player->user_id}\n";
                }
                //remove dices has value 1 from last roll
                $player->removeDiceHasValue1FromLastRoll();

                //get next player if not exist get first player
                $nextPlayer = isset(array_keys($this->players)[$index + 1]) ? $this->players[array_keys($this->players)[$index + 1]] : reset($this->players);
                if ($this->show_log) {
                    echo "Transfer {$numberOfDiceHasValue1FromLastRoll} dice 1 from player {$player->user_id} to {$nextPlayer->user_id}\n";
                }
                //check has next player or not
                $transferDice[$nextPlayer->user_id][] = [
                    'number_of_dice' => $numberOfDiceHasValue1FromLastRoll,
                    'from_player' => $player->user_id
                ];
            }
        }

        if (count($transferDice)) {

            //show transfer dice
            if ($this->show_log) {
                echo "Transfer dice item:\n";
                print_r($transferDice);
                echo "\n------Transfer dice to next player...-----\n";
            }


            //add dice to next player using transferDice
            foreach ($transferDice as $userId => $transferDiceForUser) {
                foreach ($transferDiceForUser as $transferDiceForUserItem) {
                    //find player has user id
                    $player = array_filter($this->players, function ($player) use ($userId) {
                        return $player->user_id == $userId;
                    });
                    $player = array_shift($player);
                    if ($player instanceof Player) {
                        if ($this->show_log) {
                            echo "Transfer {$transferDiceForUserItem['number_of_dice']} dice from player {$transferDiceForUserItem['from_player']} to player {$player->user_id}\n";
                        }
                        //add dice to next player
                        $player->addNumberOfDiceHasValue1ToLastRoll($transferDiceForUserItem['number_of_dice']);
                    } else {
                        if ($this->show_log) {
                            echo "Player {$player->user_id} is not instance of Player class\n";
                        }
                    }
                }
            }
            if ($this->show_log) {
                echo "------------------------------------------\n";
            }
        } else {
            if ($this->show_log) {
                echo "No dice to transfer\n";
            }
        }

        echo "After evaluate scores...\n";
        //show last roll for each player and score
        foreach ($this->players as $player) {
            echo "Player {$player->user_id} ({$player->score}) and last roll: " . implode(', ', $player->last_roll) . "\n";
        }

        if ($this->show_log) {
            echo "\n\nRemove player has no dice in last roll...\n";
        }
        $check = false;
        //add player has no dice in last roll to player result and remove from players
        foreach ($this->players as $index => $player) {
            if (!$player->canPlay()) {
                $check = true;
                $this->player_results[] = $player;
                if ($this->show_log) {
                    echo "Remove {$player->user_id}\n";
                }
                unset($this->players[$index]);
            }
        }
        if (!$check) {
            if ($this->show_log) {
                echo "No player has no dice in last roll\n";
            }
        }
        if ($this->show_log) {
            //show player after remove
            foreach ($this->players as $player) {
                echo "Player {$player->user_id} ({$player->score}) and last roll: " . implode(', ', $player->last_roll) . "\n";
            }
        }

    }

}


//run the game
//User Ids must be an int number or an array unique id and greater than 0 for running the game correctly
$game = new RollDiceGame(10, 20);
$game->play();
