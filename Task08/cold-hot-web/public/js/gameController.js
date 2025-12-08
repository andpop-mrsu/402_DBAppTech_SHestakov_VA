class GameController {
    constructor() {
        this.model = new GameModel();
        this.view = new GameView();
        this.maxAttempts = 10;
        this.isGameActive = false;

        this.view.onStartGame = (name) => this.startNewGame(name);
        this.view.onMakeGuess = (guess) => this.makeGuess(guess);
        this.view.onShowGamesList = () => this.getAllGames();
    }

    async startNewGame(name) {
        try {
            await this.model.startNewGame(name);
            this.isGameActive = true;
            this.view.startGame(name);
        } catch (e) { this.view.showError(e.message); }
    }

    async makeGuess(guess) {
        if (!this.isGameActive) return;
        const res = await this.model.makeGuess(guess);
        if (res.error) { this.view.showError(res.error); return; }

        this.view.updateGameState(this.model.getAttempts());

        if (res.won) {
            this.isGameActive = false;
            const g = await this.model.loadGame(this.model.gameId);
            this.view.showWin(g.attempts.length, g.secret_number);
        } else if (this.model.getAttempts().length >= this.maxAttempts) {
            this.isGameActive = false;
            const g = await this.model.loadGame(this.model.gameId);
            this.view.showLoss(g.attempts.length, g.secret_number);
        }
    }

    async getAllGames() {
        try { return await this.model.getAllGames(); }
        catch (e) { this.view.showError(e.message); return []; }
    }

    async replayGame(id) {
        const g = await this.model.loadGame(id);
        if (g) this.view.showReplay(g);
        else this.view.showError('Игра не найдена');
    }
}