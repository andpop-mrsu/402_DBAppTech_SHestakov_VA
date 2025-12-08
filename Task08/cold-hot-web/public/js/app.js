let gameController;

document.addEventListener('DOMContentLoaded', () => {
    try {
        gameController = new GameController();
        console.log('SPA "Холодно-Горячо" загружено успешно!');
    } catch (error) {
        console.error('Ошибка инициализации:', error);
        alert('Ошибка загрузки приложения. Обновите страницу.');
    }
});