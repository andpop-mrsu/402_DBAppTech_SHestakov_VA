@echo off
setlocal

set DB=self_logger.db
set USER=%USERNAME%

:: Формируем дату и время
for /f "tokens=1-2 delims= " %%a in ('date /t') do set DATE=%%a
for /f "tokens=1" %%a in ('time /t') do set TIME=%%a
set DATETIME=%DATE% %TIME%

:: Путь к sqlite3.exe (без кавычек в переменной!)
set SQLITE=C:\sqlite\sqlite3.exe

:: Если базы нет — создаём таблицу
if not exist %DB% (
    "%SQLITE%" %DB% "CREATE TABLE logs(user TEXT, run_date TEXT);"
)

:: Записываем запуск
"%SQLITE%" %DB% "INSERT INTO logs(user, run_date) VALUES('%USER%', '%DATETIME%');"

:: Считаем статистику
for /f %%a in ('%SQLITE% %DB% "SELECT COUNT(*) FROM logs;"') do set COUNT=%%a
for /f %%a in ('%SQLITE% %DB% "SELECT run_date FROM logs ORDER BY run_date ASC LIMIT 1;"') do set FIRST_RUN=%%a

:: Вывод информации
echo Имя программы: self-logger.bat
echo Количество запусков: %COUNT%
echo Первый запуск: %FIRST_RUN%
echo ---------------------------------------------
echo User      ^| Date
echo ---------------------------------------------
"%SQLITE%" -header -column %DB% "SELECT user, run_date FROM logs;"

endlocal
