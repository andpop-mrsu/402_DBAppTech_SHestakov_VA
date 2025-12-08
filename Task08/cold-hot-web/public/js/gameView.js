class GameView {
    constructor() {
        this.screens = {
            welcome: document.getElementById('welcomeScreen'),
            game: document.getElementById('gameScreen'),
            list: document.getElementById('listScreen')
        };

        this.elements = {
            playerForm: document.getElementById('playerForm'),
            playerName: document.getElementById('playerName'),
            currentPlayer: document.getElementById('currentPlayer'),
            attemptsCount: document.getElementById('attemptsCount'),
            secretNumber: document.getElementById('secretNumber'),
            guessInput: document.getElementById('guessInput'),
            submitGuess: document.getElementById('submitGuess'),
            attemptsList: document.getElementById('attemptsList'),
            gamesList: document.getElementById('gamesList'),
            winModal: document.getElementById('winModal'),
            winSecretNumber: document.getElementById('winSecretNumber'),
            winAttemptsCount: document.getElementById('winAttemptsCount'),
            newGameAfterWin: document.getElementById('newGameAfterWin'),
            closeWinModal: document.getElementById('closeWinModal'),
            lossModal: document.getElementById('lossModal'),
            lossSecretNumber: document.getElementById('lossSecretNumber'),
            lossAttemptsCount: document.getElementById('lossAttemptsCount'),
            newGameAfterLoss: document.getElementById('newGameAfterLoss'),
            closeLossModal: document.getElementById('closeLossModal')
        };

        this.isGameActive = false;
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        this.elements.playerForm.addEventListener('submit', e => {
            e.preventDefault();
            const name = this.elements.playerName.value.trim();
            if (name) this.onStartGame(name);
        });

        this.elements.submitGuess.addEventListener('click', () => this.submitGuess());
        this.elements.guessInput.addEventListener('keypress', e => {
            if (e.key === 'Enter') this.submitGuess();
        });
        this.elements.guessInput.addEventListener('input', e => {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });

        document.getElementById('newGameBtn').addEventListener('click', () => this.showScreen('welcome'));
        document.getElementById('listGamesBtn').addEventListener('click', () => this.showGamesList());
        document.getElementById('backFromList').addEventListener('click', () => this.showScreen('welcome'));

        this.elements.newGameAfterWin.addEventListener('click', () => {
            this.hideWinModal(); this.showScreen('welcome');
        });
        this.elements.closeWinModal.addEventListener('click', () => {
            this.hideWinModal(); this.showScreen('welcome');
        });
        this.elements.newGameAfterLoss.addEventListener('click', () => {
            this.hideLossModal(); this.showScreen('welcome');
        });
        this.elements.closeLossModal.addEventListener('click', () => {
            this.hideLossModal(); this.showScreen('welcome');
        });

        this.elements.winModal.addEventListener('click', e => {
            if (e.target === this.elements.winModal) {
                this.hideWinModal(); this.showScreen('welcome');
            }
        });
        this.elements.lossModal.addEventListener('click', e => {
            if (e.target === this.elements.lossModal) {
                this.hideLossModal(); this.showScreen('welcome');
            }
        });
    }

    showScreen(name) {
        Object.values(this.screens).forEach(el => el.classList.remove('active'));
        this.screens[name].classList.add('active');
        if (name === 'welcome') this.elements.playerForm.reset();
    }

    onStartGame(callback) { this.onStartGame = callback; }
    onMakeGuess(callback) { this.onMakeGuess = callback; }
    onShowGamesList(callback) { this.onShowGamesList = callback; }

    startGame(playerName) {
        this.elements.currentPlayer.textContent = playerName;
        this.elements.attemptsCount.textContent = '0';
        this.elements.secretNumber.textContent = '???';
        this.elements.attemptsList.innerHTML = '';
        this.elements.guessInput.value = '';
        this.elements.guessInput.disabled = false;
        this.elements.submitGuess.disabled = false;
        this.isGameActive = true;
        this.elements.guessInput.focus();
        this.showScreen('game');
    }

    submitGuess() {
        if (!this.isGameActive) return;
        const guess = this.elements.guessInput.value;
        if (guess.length === 3) {
            this.onMakeGuess(guess);
            this.elements.guessInput.value = '';
        }
    }

    updateGameState(attempts) {
        this.elements.attemptsCount.textContent = attempts.length;
        this.elements.attemptsList.innerHTML = '';
        attempts.forEach(attempt => {
            const el = this.createAttemptElement(attempt);
            this.elements.attemptsList.appendChild(el);
        });
        this.elements.attemptsList.scrollTop = this.elements.attemptsList.scrollHeight;
    }

    createAttemptElement(attempt) {
        const div = document.createElement('div');
        div.className = 'attempt';
        const hintsHtml = attempt.result.map(hint =>
            `<span class="hint ${hint}">${this.getHintText(hint)}</span>`
        ).join(' ');
        div.innerHTML = `
            <span class="attempt-number">${attempt.number}.</span>
            <span class="attempt-guess">${attempt.guess}</span>
            <div class="hints">${hintsHtml}</div>
        `;
        return div;
    }

    getHintText(hint) {
        return { cold: 'Холодно', warm: 'Тепло', hot: 'Горячо' }[hint] || hint;
    }

    showWin(attempts, secret) {
        this.endGame();
        this.elements.secretNumber.textContent = secret;
        this.elements.winSecretNumber.textContent = secret;
        this.elements.winAttemptsCount.textContent = attempts;
        this.showWinModal();
    }

    showLoss(attempts, secret) {
        this.endGame();
        this.elements.secretNumber.textContent = secret;
        this.elements.lossSecretNumber.textContent = secret;
        this.elements.lossAttemptsCount.textContent = attempts;
        this.showLossModal();
    }

    showWinModal() { this.elements.winModal.classList.remove('hidden'); }
    hideWinModal() { this.elements.winModal.classList.add('hidden'); }
    showLossModal() { this.elements.lossModal.classList.remove('hidden'); }
    hideLossModal() { this.elements.lossModal.classList.add('hidden'); }

    endGame() {
        this.isGameActive = false;
        this.elements.guessInput.disabled = true;
        this.elements.submitGuess.disabled = true;
    }

    showError(msg) {
        alert(msg);
    }

    async showGamesList() {
        const games = await this.onShowGamesList();
        if (games.length === 0) {
            this.elements.gamesList.innerHTML = '<p>Нет сохранённых игр</p>';
        } else {
            this.elements.gamesList.innerHTML = games.map(g => `
                <div class="game-item ${g.outcome === 'won' ? 'won' : 'lost'}"
                     onclick="gameController.replayGame(${g.id})">
                    <strong>Игра #${g.id}</strong> — ${new Date(g.date).toLocaleString()}<br>
                    Игрок: ${g.player_name} | Число: ${g.secret_number}<br>
                    Результат: ${g.outcome === 'won' ? 'Победа' : 'Поражение'} | 
                    Попыток: ${g.attempts?.length || 0}
                </div>
            `).join('');
        }
        this.showScreen('list');
    }

    showReplay(gameData) {
        this.elements.currentPlayer.textContent = gameData.player_name;
        this.elements.attemptsCount.textContent = gameData.attempts.length;
        this.elements.secretNumber.textContent = gameData.secret_number;
        this.elements.attemptsList.innerHTML = '';
        gameData.attempts.forEach(attempt => {
            this.elements.attemptsList.appendChild(this.createAttemptElement(attempt));
        });
        this.elements.guessInput.disabled = true;
        this.elements.submitGuess.disabled = true;
        this.isGameActive = false;
        this.showScreen('game');

        if (gameData.outcome === 'won') {
            this.showWin(gameData.attempts.length, gameData.secret_number);
        } else {
            this.showLoss(gameData.attempts.length, gameData.secret_number);
        }
    }
}