class GameModel {
    constructor() {
        this.gameId = null;
        this.attempts = [];
    }

    async startNewGame(playerName) {
        const res = await fetch('/games', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ player_name: playerName })
        });
        if (!res.ok) throw new Error('Не удалось создать игру');
        const data = await res.json();
        this.gameId = data.id;
        this.attempts = [];
        return this.gameId;
    }

    async makeGuess(guess) {
        if (!/^[0-9]{3}$/.test(guess)) return { error: 'Введите ровно 3 цифры' };
        if (new Set(guess).size !== 3) return { error: 'Цифры должны быть уникальными' };

        const res = await fetch(`/step/${this.gameId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ guess })
        });
        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            return { error: err.message || 'Ошибка хода' };
        }
        const data = await res.json();
        this.attempts = data.attempts || [];
        return { won: data.outcome === 'won', outcome: data.outcome };
    }

    async getAllGames() {
        const res = await fetch('/games');
        if (!res.ok) throw new Error('Не удалось загрузить игры');
        return await res.json();
    }

    async loadGame(id) {
        const res = await fetch(`/games/${id}`);
        if (!res.ok) return null;
        return await res.json();
    }

    getAttempts() { return this.attempts; }
}