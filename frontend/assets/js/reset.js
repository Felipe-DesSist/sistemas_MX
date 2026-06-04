(() => {
  const API   = '/api';
  const token = new URLSearchParams(window.location.search).get('token');
  const form  = document.getElementById('reset-form');
  const btn   = form.querySelector('.btn-primary');

  btn.dataset.label = btn.textContent;

  function setMsg(text, type) {
    const p = form.querySelector('.msg');
    p.textContent = text;
    p.className   = 'msg ' + (type || '');
  }

  function setLoading(on) {
    btn.disabled    = on;
    btn.textContent = on ? 'Aguarde…' : btn.dataset.label;
  }

  if (!token) {
    setMsg('Link inválido ou expirado.', 'error');
    btn.disabled = true;
  }

  form.addEventListener('submit', async e => {
    e.preventDefault();
    const newPass  = form.querySelector('#new-pass').value;
    const confirm  = form.querySelector('#confirm-pass').value;

    if (newPass.length < 8)      { setMsg('Senha deve ter no mínimo 8 caracteres.', 'error'); return; }
    if (newPass !== confirm)     { setMsg('As senhas não coincidem.', 'error'); return; }

    setLoading(true);
    setMsg('', '');

    try {
      const res  = await fetch(API + '/auth/reset-password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token, password: newPass }),
      });
      const data = await res.json();

      if (!res.ok) { setMsg(data.error || 'Erro ao redefinir senha.', 'error'); setLoading(false); return; }

      setMsg('Senha redefinida! Redirecionando…', 'success');
      setTimeout(() => { window.location.href = '/login.html'; }, 1500);

    } catch {
      setMsg('Erro de conexão. Tente novamente.', 'error');
      setLoading(false);
    }
  });
})();
