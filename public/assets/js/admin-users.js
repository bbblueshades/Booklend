import { api, debounce, escapeHtml, showAlert } from './api.js';

const app = document.getElementById('usersApp');
const apiBase = app.dataset.api;
const me = Number(app.dataset.me);
const body = document.getElementById('usersBody');
const alertBox = document.getElementById('alertBox');
const search = document.getElementById('searchUsers');

const roleModal = new bootstrap.Modal(document.getElementById('roleModal'));
const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
let selected = null;

const render = (users) => {
    if (!users.length) {
        body.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No records found.</td></tr>';
        return;
    }
    body.innerHTML = users.map(({ user_id, full_name, email, role, created_at }) => {
        const isMe = Number(user_id) === me;
        return `<tr>
            <td data-label="ID">${user_id}</td>
            <td data-label="Name">${escapeHtml(full_name)}${isMe ? ' <span class="badge text-bg-secondary">You</span>' : ''}</td>
            <td data-label="Email">${escapeHtml(email)}</td>
            <td data-label="Role">${escapeHtml(role)}</td>
            <td data-label="Joined">${escapeHtml(created_at.slice(0, 10))}</td>
            <td data-label="Actions">
                <button class="btn btn-sm btn-outline-secondary" data-action="edit" data-id="${user_id}" ${isMe ? 'disabled' : ''}>Edit</button>
                <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${user_id}" ${isMe ? 'disabled' : ''}>Delete</button>
            </td>
        </tr>`;
    }).join('');
};

let users = [];
const load = async () => {
    try {
        const data = await api(`${apiBase}/users_list.php?q=${encodeURIComponent(search.value.trim())}`);
        users = data.users;
        render(users);
    } catch (err) {
        showAlert(alertBox, 'danger', err.message);
    }
};

search.addEventListener('input', debounce(load));

body.addEventListener('click', ({ target }) => {
    const btn = target.closest('button[data-action]');
    if (!btn) return;
    selected = users.find((u) => Number(u.user_id) === Number(btn.dataset.id));
    if (!selected) return;

    if (btn.dataset.action === 'edit') {
        document.getElementById('roleModalName').textContent = `${selected.full_name} (${selected.email})`;
        document.getElementById('roleSelect').value = selected.role;
        roleModal.show();
    } else {
        document.getElementById('deleteModalName').textContent = `${selected.full_name} (${selected.email})`;
        deleteModal.show();
    }
});

document.getElementById('roleSave').addEventListener('click', async () => {
    try {
        const role = document.getElementById('roleSelect').value;
        const data = await api(`${apiBase}/user_role.php`, {
            method: 'POST', body: { user_id: selected.user_id, role },
        });
        roleModal.hide();
        showAlert(alertBox, 'success', data.message);
        load();
    } catch (err) {
        roleModal.hide();
        showAlert(alertBox, 'danger', err.message);
    }
});

document.getElementById('deleteConfirm').addEventListener('click', async () => {
    try {
        const data = await api(`${apiBase}/user_delete.php`, {
            method: 'POST', body: { user_id: selected.user_id },
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