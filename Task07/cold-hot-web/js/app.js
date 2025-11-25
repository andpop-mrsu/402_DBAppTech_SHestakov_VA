// Инициализация приложения
let gameController;

document.addEventListener('DOMContentLoaded', async () => {
    try {
        gameController = new GameController();
        console.log('Игра "Холодно-Горячо" загружена успешно!');

        // Проверяем, что все элементы загружены
        console.log('Элементы загружены:', {
            winModal: document.getElementById('winModal'),
            lossModal: document.getElementById('lossModal'),
            gameScreen: document.getElementById('gameScreen')
        });
    } catch (error) {
        console.error('Ошибка при инициализации игры:', error);
        alert('Произошла ошибка при загрузке игры. Пожалуйста, обновите страницу.');
    }
});