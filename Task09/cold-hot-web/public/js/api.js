// public/js/api.js
const API_BASE = '/api';

export async function apiGetGames() {
    const res = await fetch(`${API_BASE}/games`);
    if (!res.ok) throw new Error('Ошибка получения списка игр');
    return res.json();
}

export async function apiGetGame(id) {
    const res = await fetch(`${API_BASE}/games/${id}`);
    if (!res.ok) throw new Error('Ошибка получения игры');
    return res.json();
}

export async function apiCreateGame({ player_name, secret_number }) {
    const res = await fetch(`${API_BASE}/games`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ player_name, secret_number })
    });
    if (!res.ok) {
        const err = await res.json().catch(()=>({}));
        throw new Error(err.error || 'Ошибка создания игры');
    }
    return res.json();
}

export async function apiAddStep(gameId, { guess, result, outcome }) {
    const res = await fetch(`${API_BASE}/step/${gameId}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ guess, result, outcome })
    });
    if (!res.ok) {
        const err = await res.json().catch(()=>({}));
        throw new Error(err.error || 'Ошибка сохранения хода');
    }
    return res.json();
}
