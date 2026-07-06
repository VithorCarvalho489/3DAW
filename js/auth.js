function switchTab(t) {
    document.getElementById('form-login').style.display = t === 'login' ? '' : 'none';
    document.getElementById('form-cad').style.display   = t === 'cad'   ? '' : 'none';
    document.getElementById('tab-login').classList.toggle('active', t === 'login');
    document.getElementById('tab-cad').classList.toggle('active',   t === 'cad');
    hideAlert('login'); hideAlert('cad');
}

function togglePw(id, btn) {
    const inp = document.getElementById(id);
    const show = inp.type === 'password';
    inp.type = show ? 'text' : 'password';
    btn.querySelector('circle') && (btn.querySelector('path').style.opacity = show ? '0.4' : '1');
}

function showAlert(form, msg, type) {
    const el = document.getElementById('alert-' + form);
    el.textContent = msg;
    el.className = 'alert ' + type;
    el.style.display = 'block';
}
function hideAlert(form) {
    const el = document.getElementById('alert-' + form);
    if (el) el.style.display = 'none';
}

function setLoading(btn, loading) {
    if (loading) {
        btn.disabled = true;
        btn.dataset.label = btn.textContent;
        btn.innerHTML = '<span class="spinner"></span>Aguarde...';
    } else {
        btn.disabled = false;
        btn.textContent = btn.dataset.label || btn.textContent;
    }
}

async function post(data) {
    const fd = new FormData();
    Object.entries(data).forEach(([k, v]) => fd.append(k, v));
    const r = await fetch('api.php', { method: 'POST', body: fd });
    return r.json();
}

async function doLogin() {
    hideAlert('login');
    const email = document.getElementById('login-email').value.trim();
    const senha = document.getElementById('login-senha').value;
    if (!email || !senha) { showAlert('login', 'Preencha e-mail e senha.', 'error'); return; }

    const btn = document.getElementById('btn-login');
    setLoading(btn, true);
    try {
        const res = await post({ action: 'login', email, senha });
        if (res.success) {
            document.getElementById('welcome-name').textContent = 'Olá, ' + res.nome + '!';
            document.getElementById('welcome').classList.add('show');
            setTimeout(() => { location.reload(); }, 1400);
        } else {
            showAlert('login', res.msg, 'error');
            setLoading(btn, false);
        }
    } catch(e) {
        showAlert('login', 'Erro de comunicação. Tente novamente.', 'error');
        setLoading(btn, false);
    }
}

async function doCadastro() {
    hideAlert('cad');
    const nome  = document.getElementById('cad-nome').value.trim();
    const email = document.getElementById('cad-email').value.trim();
    const senha = document.getElementById('cad-senha').value;
    const tel   = document.getElementById('cad-tel').value.trim();

    if (!nome || !email || !senha) { showAlert('cad', 'Preencha nome, e-mail e senha.', 'error'); return; }

    const btn = document.getElementById('btn-cad');
    setLoading(btn, true);
    try {
        const res = await post({ action: 'cadastro', nome, email, senha, telefone: tel });
        if (res.success) {
            showAlert('cad', res.msg, 'success');
            setTimeout(() => switchTab('login'), 1800);
        } else {
            showAlert('cad', res.msg, 'error');
        }
        setLoading(btn, false);
    } catch(e) {
        showAlert('cad', 'Erro de comunicação. Tente novamente.', 'error');
        setLoading(btn, false);
    }
}

document.addEventListener('keydown', e => {
    if (e.key !== 'Enter') return;
    if (document.getElementById('form-login').style.display !== 'none') doLogin();
    else doCadastro();
});
