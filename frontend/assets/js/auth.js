(() => {
  const API = '/api';

  // --- Tabs ---
  const tabs  = document.querySelectorAll('.tab');
  const forms = document.querySelectorAll('.form');

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t  => { t.classList.remove('active'); t.setAttribute('aria-selected', 'false'); });
      forms.forEach(f => f.classList.remove('active'));
      tab.classList.add('active');
      tab.setAttribute('aria-selected', 'true');
      document.getElementById(tab.dataset.tab).classList.add('active');
      if (tab.dataset.tab === 'signup') loadSectors();
    });
  });

  // --- Helpers ---
  function setMsg(form, text, type) {
    const p = form.querySelector('.msg');
    p.textContent = text;
    p.className = 'msg ' + (type || '');
  }

  function setLoading(btn, on) {
    btn.disabled    = on;
    btn.textContent = on ? 'Aguarde…' : btn.dataset.label;
  }

  async function post(endpoint, data) {
    const res  = await fetch(API + endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
    });
    return { ok: res.ok, data: await res.json() };
  }

  // --- Sectors ---
  const sectorGrid  = document.getElementById('sector-grid');
  const sectorInput = document.getElementById('signup-sector');
  let sectorsLoaded = false;

  async function loadSectors() {
    if (sectorsLoaded) return;

    setSectorStatus('Carregando setores…', false);

    try {
      const res  = await fetch(API + '/auth/sectors');
      const data = await res.json();

      if (!res.ok || !data.sectors?.length) {
        setSectorStatus('Nenhum setor disponível.', true);
        return;
      }

      sectorGrid.innerHTML = '';

      data.sectors.forEach(name => {
        const btn = document.createElement('button');
        btn.type            = 'button';
        btn.className       = 'sector-card';
        btn.textContent     = name;
        btn.dataset.sector  = name;
        btn.setAttribute('role', 'radio');
        btn.setAttribute('aria-checked', 'false');

        btn.addEventListener('click', () => {
          sectorGrid.querySelectorAll('.sector-card').forEach(c => {
            c.classList.remove('selected');
            c.setAttribute('aria-checked', 'false');
          });
          btn.classList.add('selected');
          btn.setAttribute('aria-checked', 'true');
          sectorInput.value = name;
        });

        sectorGrid.appendChild(btn);
      });

      sectorsLoaded = true;
    } catch {
      setSectorStatus('Erro ao carregar setores.', true);
    }
  }

  function setSectorStatus(text, isError) {
    sectorGrid.innerHTML = `<span class="sector-status${isError ? ' error' : ''}">${text}</span>`;
  }

  // --- Forgot password panel ---
  const tabsEl    = document.querySelector('.tabs');
  const openForgot = document.getElementById('open-forgot');
  const backLogin  = document.getElementById('back-login');

  openForgot.addEventListener('click', () => {
    document.getElementById('login').classList.remove('active');
    tabsEl.style.display = 'none';
    document.getElementById('forgot').classList.add('active');
  });

  backLogin.addEventListener('click', () => {
    document.getElementById('forgot').classList.remove('active');
    tabsEl.style.display = '';
    document.getElementById('login').classList.add('active');
    const fp = document.getElementById('forgot-form');
    fp.reset();
    setMsg(fp, '', '');
  });

  const forgotForm = document.getElementById('forgot-form');
  const forgotBtn  = forgotForm.querySelector('.btn-primary');
  forgotBtn.dataset.label = forgotBtn.textContent;

  forgotForm.addEventListener('submit', async e => {
    e.preventDefault();
    const email = forgotForm.querySelector('#forgot-email').value.trim();

    if (!email) { setMsg(forgotForm, 'Informe seu e-mail.', 'error'); return; }

    setLoading(forgotBtn, true);
    setMsg(forgotForm, '', '');

    try {
      await post('/auth/forgot-password', { email });
      setMsg(forgotForm, 'Se o e-mail existir, você receberá o link em breve.', 'success');
      forgotForm.reset();
    } catch {
      setMsg(forgotForm, 'Erro de conexão. Tente novamente.', 'error');
    } finally {
      setLoading(forgotBtn, false);
    }
  });

  // --- Login ---
  const loginForm = document.getElementById('login');
  const loginBtn  = loginForm.querySelector('.btn-primary');
  loginBtn.dataset.label = loginBtn.textContent;

  loginForm.addEventListener('submit', async e => {
    e.preventDefault();
    const email    = loginForm.querySelector('#login-email').value.trim();
    const password = loginForm.querySelector('#login-pass').value;

    if (!email || !password) { setMsg(loginForm, 'Preencha todos os campos.', 'error'); return; }

    setLoading(loginBtn, true);
    setMsg(loginForm, '', '');

    try {
      const { ok, data } = await post('/auth/login', { email, password });
      if (!ok) { setMsg(loginForm, data.error || 'Falha no login.', 'error'); return; }
      localStorage.setItem('token', data.token);
      setMsg(loginForm, 'Bem-vindo!', 'success');
      setTimeout(() => { window.location.href = '/'; }, 800);
    } catch {
      setMsg(loginForm, 'Erro de conexão. Tente novamente.', 'error');
    } finally {
      setLoading(loginBtn, false);
    }
  });

  // --- Sign up ---
  const signupForm = document.getElementById('signup');
  const signupBtn  = signupForm.querySelector('.btn-primary');
  signupBtn.dataset.label = signupBtn.textContent;

  signupForm.addEventListener('submit', async e => {
    e.preventDefault();
    const name     = signupForm.querySelector('#signup-name').value.trim();
    const email    = signupForm.querySelector('#signup-email').value.trim();
    const password = signupForm.querySelector('#signup-pass').value;
    const setor    = sectorInput.value;

    if (!name || !email || !password) { setMsg(signupForm, 'Preencha todos os campos.', 'error'); return; }
    if (password.length < 8)          { setMsg(signupForm, 'Senha deve ter no mínimo 8 caracteres.', 'error'); return; }
    if (!setor)                        { setMsg(signupForm, 'Selecione seu setor.', 'error'); return; }

    setLoading(signupBtn, true);
    setMsg(signupForm, '', '');

    try {
      const { ok, data } = await post('/auth/register', { name, email, password, setor });
      if (!ok) { setMsg(signupForm, data.error || 'Falha no cadastro.', 'error'); return; }
      setMsg(signupForm, 'Conta criada! Redirecionando…', 'success');
      setTimeout(() => { window.location.href = '/login.html'; }, 1000);
    } catch {
      setMsg(signupForm, 'Erro de conexão. Tente novamente.', 'error');
    } finally {
      setLoading(signupBtn, false);
    }
  });
})();
