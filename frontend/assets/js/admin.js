(() => {
  const API           = '/api';
  const ADMIN_SECTORS = ['ADMINISTRAÇÃO', 'DESENVOLVEDOR'];
  const ALL_SECTORS   = ['ARTES', 'PRODUÇÃO', 'EXPEDIÇÃO', 'ADMINISTRAÇÃO', 'DESENVOLVEDOR'];

  const token = localStorage.getItem('token');
  const user  = JSON.parse(localStorage.getItem('user') || 'null');

  if (!token || !user || !ADMIN_SECTORS.includes(user.setor)) {
    window.location.href = '/';
  }

  document.getElementById('user-name').textContent  = user.name;
  document.getElementById('user-setor').textContent = user.setor;

  document.getElementById('btn-logout').addEventListener('click', () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = '/';
  });

  const tbody   = document.getElementById('users-tbody');
  const table   = document.getElementById('users-table');
  const msgEl   = document.getElementById('admin-msg');

  // --- Load users ---
  async function loadUsers() {
    try {
      const res = await fetch(API + '/admin/users', {
        headers: { Authorization: 'Bearer ' + token },
      });

      if (res.status === 401 || res.status === 403) {
        window.location.href = '/';
        return;
      }

      const data = await res.json();
      renderUsers(data.users || []);
    } catch {
      msgEl.textContent = 'Erro ao carregar usuários.';
    }
  }

  function renderUsers(users) {
    tbody.innerHTML = '';

    if (!users.length) {
      msgEl.textContent = 'Nenhum usuário cadastrado.';
      return;
    }

    msgEl.style.display = 'none';
    table.hidden = false;
    users.forEach(u => tbody.appendChild(buildRow(u)));
  }

  // --- Row builder ---
  function buildRow(u) {
    const tr = document.createElement('tr');

    tr.innerHTML = `
      <td>${esc(u.name)}</td>
      <td class="td-email">${esc(u.email)}</td>
      <td class="cell-setor">${esc(u.setor)}</td>
      <td class="cell-action">
        <button class="btn-edit">Editar</button>
      </td>`;

    tr.querySelector('.btn-edit').addEventListener('click', () => startEdit(tr, u));
    return tr;
  }

  // --- Inline edit ---
  function startEdit(tr, u) {
    const cellSetor  = tr.querySelector('.cell-setor');
    const cellAction = tr.querySelector('.cell-action');

    const select = document.createElement('select');
    select.className = 'sector-select';

    ALL_SECTORS.forEach(s => {
      const opt     = document.createElement('option');
      opt.value     = s;
      opt.textContent = s;
      opt.selected  = s === u.setor;
      select.appendChild(opt);
    });

    cellSetor.innerHTML  = '';
    cellSetor.appendChild(select);

    cellAction.innerHTML = `
      <button class="btn-save">Salvar</button>
      <button class="btn-cancel">Cancelar</button>`;

    cellAction.querySelector('.btn-save').addEventListener('click',   () => saveEdit(tr, u, select.value));
    cellAction.querySelector('.btn-cancel').addEventListener('click', () => cancelEdit(tr, u));
  }

  async function saveEdit(tr, u, newSetor) {
    const saveBtn       = tr.querySelector('.btn-save');
    saveBtn.disabled    = true;
    saveBtn.textContent = '…';

    try {
      const res  = await fetch(API + '/admin/users/sector', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: 'Bearer ' + token,
        },
        body: JSON.stringify({ user_id: u.id, setor: newSetor }),
      });
      const data = await res.json();

      if (!res.ok) {
        alert(data.error || 'Erro ao salvar.');
        cancelEdit(tr, { ...u });
        return;
      }

      tr.replaceWith(buildRow({ ...u, setor: newSetor }));

    } catch {
      alert('Erro de conexão.');
      cancelEdit(tr, u);
    }
  }

  function cancelEdit(tr, u) {
    tr.replaceWith(buildRow(u));
  }

  // --- Helpers ---
  function esc(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  loadUsers();
})();
