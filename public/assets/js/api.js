const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

export async function api(url, { method = 'GET', body } = {}) {
    let res;
    try {
        res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: body ? JSON.stringify(body) : undefined,
        });
    } catch {
        throw new Error('Unable to connect to the server.');
    }

    let data;
    try {
        data = await res.json();
    } catch {
        throw new Error('Unexpected response from the server.');
    }
    if (!res.ok || !data.success) {
        throw new Error(data.message ?? 'Something went wrong.');
    }
    return data;
}

export const debounce = (fn, delay = 300) => {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
};

export const escapeHtml = (text) => {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
};

export function showAlert(container, type, message) {
    container.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
        ${escapeHtml(message)}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>`;
}

import { api, debounce, escapeHtml, showAlert, showToast } from './api.js';