class GameModel {
    constructor() {
        this.secretNumber = '';
        this.attempts = [];
        this.isWon = false;
        this.playerName = '';
        this.database = new Database();
        this.gameId = null;
    }

    async init() {
        await this.database.init();
    }

    async startNewGame(playerName) {
        this.playerName = playerName;
        this.secretNumber = this.generateSecretNumber();
        this.attempts = [];
        this.isWon = false;

        this.gameId = await this.database.saveGame({
            player_name: this.playerName,
            secret_number: this.secretNumber,
            outcome: 'in_progress',
            attempts: []
        });

        return this.gameId;
    }

    generateSecretNumber() {
        const digits = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];

        for (let i = digits.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [digits[i], digits[j]] = [digits[j], digits[i]];
        }

        if (digits[0] === 0) {
            [digits[0], digits[1]] = [digits[1], digits[0]];
        }

        return digits.slice(0, 3).join('');
    }

    makeGuess(guess) {
        console.log('Делаем догадку:', guess); // Для отладки

        if (!/^[0-9]{3}$/.test(guess)) {
            return { error: 'Введите ровно 3 цифры' };
        }

        if (new Set(guess).size !== 3) {
            return { error: 'Все цифры должны быть уникальными' };
        }

        const hints = this.getHints(guess);
        const attemptNumber = this.attempts.length + 1;

        const attempt = {
            number: attemptNumber,
            guess: guess,
            result: hints
        };

        this.attempts.push(attempt);

        // Проверяем победу
        const won = guess === this.secretNumber;
        this.isWon = won;

        // Определяем исход игры
        let outcome = 'in_progress';
        if (won) {
            outcome = 'won';
        } else if (this.attempts.length >= 10) {
            outcome = 'lost';
        }

        console.log('Результат догадки:', { won, attempts: this.attempts.length, outcome }); // Для отладки

        // Обновляем игру в базе данных
        this.database.updateGame(this.gameId, {
            outcome: outcome,
            attempts: this.attempts
        });

        return {
            hints: hints,
            won: won,
            attempt: attempt
        };
    }

    getHints(guess) {
        const hints = [];
        const secretDigits = this.secretNumber.split('');
        const guessDigits = guess.split('');

        for (let i = 0; i < 3; i++) {
            if (guessDigits[i] === secretDigits[i]) {
                hints.push('hot');
            } else if (secretDigits.includes(guessDigits[i])) {
                hints.push('warm');
            } else {
                hints.push('cold');
            }
        }

        return hints.sort();
    }

    getSecretNumber() {
        return this.secretNumber;
    }

    getAttempts() {
        return this.attempts;
    }

    isGameWon() {
        return this.isWon;
    }

    async getAllGames() {
        return await this.database.getAllGames();
    }

    async loadGame(id) {
        return await this.database.getGameById(id);
    }
}