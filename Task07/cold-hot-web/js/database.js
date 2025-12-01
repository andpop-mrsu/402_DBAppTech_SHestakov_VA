class Database {
    constructor() {
        this.dbName = 'ColdHotGame';
        this.version = 1;
        this.db = null;
    }

    async init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.version);

            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                this.db = request.result;
                resolve(this.db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                if (!db.objectStoreNames.contains('games')) {
                    const store = db.createObjectStore('games', {
                        keyPath: 'id',
                        autoIncrement: true
                    });
                    store.createIndex('date', 'date', { unique: false });
                    store.createIndex('player_name', 'player_name', { unique: false });
                    store.createIndex('outcome', 'outcome', { unique: false });
                }
            };
        });
    }

    async saveGame(gameData) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['games'], 'readwrite');
            const store = transaction.objectStore('games');

            const game = {
                date: new Date().toISOString(),
                player_name: gameData.player_name,
                secret_number: gameData.secret_number,
                outcome: gameData.outcome,
                attempts: gameData.attempts
            };

            const request = store.add(game);

            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    async updateGame(id, gameData) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['games'], 'readwrite');
            const store = transaction.objectStore('games');

            const request = store.get(id);

            request.onsuccess = () => {
                const game = request.result;
                if (game) {
                    game.outcome = gameData.outcome;
                    game.attempts = gameData.attempts;

                    const updateRequest = store.put(game);
                    updateRequest.onsuccess = () => resolve();
                    updateRequest.onerror = () => reject(updateRequest.error);
                } else {
                    reject(new Error('Game not found'));
                }
            };

            request.onerror = () => reject(request.error);
        });
    }

    async getAllGames() {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['games'], 'readonly');
            const store = transaction.objectStore('games');
            const index = store.index('date');
            const request = index.openCursor(null, 'prev'); // Сортировка по убыванию даты

            const games = [];

            request.onsuccess = (event) => {
                const cursor = event.target.result;
                if (cursor) {
                    games.push({
                        id: cursor.value.id,
                        ...cursor.value
                    });
                    cursor.continue();
                } else {
                    resolve(games);
                }
            };

            request.onerror = () => reject(request.error);
        });
    }

    async getGameById(id) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['games'], 'readonly');
            const store = transaction.objectStore('games');
            const request = store.get(id);

            request.onsuccess = () => {
                if (request.result) {
                    resolve(request.result);
                } else {
                    resolve(null);
                }
            };

            request.onerror = () => reject(request.error);
        });
    }
}