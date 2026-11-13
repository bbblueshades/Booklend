import { api, debounce, escapeHtml, showAlert } from './api.js';

const apiBase = document.getElementById('booksApp').dataset.api;
const body = document.getElementById('booksBody');
const alertBox = document.getElementById('alertBox');
const search = document.getElementById('searchBooks');
const statusFilter = document.getElementById('statusFilter');

const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
let books = [];
let selected = null;

const render = () => {
    if (!books.length) {
        body.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No records found.</td></tr>';
        return;
    }
    body.innerHTML = books.map(({ book_id, title, owner, subject_name, availability }) => `<tr>
        <td>${book_id}</td>
        <td>${escapeHtml(title)}</td>
        <td>${escapeHtml(owner)}</td>
        <td>${escapeHtml(subject_name)}</td>
        <td>${escapeHtml(availability)}</td>
        <td>
            <button class="btn btn-sm btn-outline-secondary" data-action="view" data-id="${book_id}">View</button>
            <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${book_id}">Delete</button>
        </td>
    </tr>`).join('');
};

const load = async () => {
    const params = new URLSearchParams({ q: search.value.trim(), status: statusFilter.value });
    try {
        ({ books } = await api(`${apiBase}/books_list.php?${params}`));
        render();
    } catch (err) {
        showAlert(alertBox, 'danger', err.message);
    }
};

search.addEventListener('input', debounce(load));
statusFilter.addEventListener('change', load);

body.addEventListener('click', ({ target }) => {
    const btn = target.closest('button[data-action]');
    if (!btn) return;
    selected = books.find((b) => Number(b.book_id) === Number(btn.dataset.id));
    if (!selected) return;

    if (btn.dataset.action === 'view') {
        const { title, author, isbn, book_condition, availability, owner, subject_name, cover_url } = selected;
        document.getElementById('viewBody').innerHTML = `
            ${cover_url ? `<img src="${escapeHtml(cover_url)}" alt="Cover of ${escapeHtml(title)}" class="img-fluid mb-3" style="max-height:200px">` : ''}
            <h3 class="h5">${escapeHtml(title)}</h3>
            <p class="mb-1">Author: ${escapeHtml(author)}</p>
            <p class="mb-1">ISBN: ${escapeHtml(isbn ?? '-')}</p>
            <p class="mb-1">${escapeHtml(subject_name)} | Condition: ${escapeHtml(book_condition)}</p>
            <p class="mb-1">Owner: ${escapeHtml(owner)}</p>
            <p class="mb-0">Status: ${escapeHtml(availability)}</p>`;
        viewModal.show();
    } else {
        document.getElementById('deleteModalName').textContent = `${selected.title} (owner: ${selected.owner})`;
        deleteModal.show();
    }
});

document.getElementById('deleteConfirm').addEventListener('click', async () => {
    try {
        const data = await api(`${apiBase}/book_delete.php`, {
            method: 'POST', body: { book_id: selected.book_id },
        });
        deleteModal.hide();
        showAlert(alertBox, 'success', data.message);
        load();
    } catch (err) {
        deleteModal.hide();
        showAlert(alertBox, 'danger', err.message);
    }
});

load();