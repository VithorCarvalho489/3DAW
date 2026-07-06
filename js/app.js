const state = {
    tipoCabeloId: null,
    servico: null,
    cabeleireiro: null,
    data: null,
    hora: null
};

document.getElementById('user-initial').textContent =
    document.getElementById('user-nome').textContent.trim().charAt(0).toUpperCase();

function showToast(msg) {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}

async function api(action, params = {}, method = 'GET') {
    if (method === 'GET') {
        const qs = new URLSearchParams({ action, ...params }).toString();
        const r = await fetch('api.php?' + qs);
        return r.json();
    }
    const fd = new FormData();
    fd.append('action', action);
    Object.entries(params).forEach(([k, v]) => fd.append(k, v));
    const r = await fetch('api.php', { method: 'POST', body: fd });
    return r.json();
}

function switchView(view) {
    document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
    document.getElementById('view-' + view).classList.add('active');
    document.querySelectorAll('.nav-btn').forEach(b => b.classList.toggle('active', b.dataset.view === view));

    if (view === 'meus') carregarMeusAgendamentos();
    if (view === 'profissionais') carregarProfissionaisLista();
}

function goToStep(n) {
    document.querySelectorAll('.wizard-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel-' + n).classList.add('active');
    document.querySelectorAll('.step').forEach(s => {
        const num = parseInt(s.dataset.step);
        s.classList.toggle('active', num === n);
        s.classList.toggle('done', num < n);
    });
    if (n === 4) montarResumo();
}

async function carregarTiposCabelo() {
    const res = await api('listar_tipos_cabelo');
    if (!res.success) return;
    const box = document.getElementById('tipos-cabelo-chips');
    box.innerHTML = '';

    const chipTodos = document.createElement('div');
    chipTodos.className = 'chip selected';
    chipTodos.textContent = 'Todos os tipos';
    chipTodos.onclick = () => selecionarTipoCabelo(null, chipTodos);
    box.appendChild(chipTodos);

    res.dados.forEach(tipo => {
        const chip = document.createElement('div');
        chip.className = 'chip';
        chip.textContent = tipo.nome;
        chip.onclick = () => selecionarTipoCabelo(tipo.id, chip);
        box.appendChild(chip);
    });

    carregarServicos();
}

function selecionarTipoCabelo(id, el) {
    state.tipoCabeloId = id;
    document.querySelectorAll('#tipos-cabelo-chips .chip').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    carregarServicos();
}

async function carregarServicos() {
    const grid = document.getElementById('servicos-grid');
    grid.innerHTML = '<div class="spinner-block"><span class="spinner-inline"></span>Carregando serviços...</div>';
    const params = state.tipoCabeloId ? { tipo_cabelo_id: state.tipoCabeloId } : {};
    const res = await api('listar_servicos', params);
    if (!res.success || res.dados.length === 0) {
        grid.innerHTML = '<p class="empty-msg">Nenhum serviço encontrado para este tipo de cabelo.</p>';
        return;
    }
    grid.innerHTML = '';
    res.dados.forEach(s => {
        const card = document.createElement('div');
        card.className = 'pick-card';
        if (state.servico && state.servico.id == s.id) card.classList.add('selected');
        card.innerHTML = `
            <h3>${s.nome}</h3>
            <p>${s.descricao || ''}</p>
            <div class="meta">
                <span class="price">R$ ${parseFloat(s.preco).toFixed(2).replace('.', ',')}</span>
                <span class="duration">${s.duracao_min} min</span>
            </div>`;
        card.onclick = () => {
            state.servico = s;
            document.querySelectorAll('#servicos-grid .pick-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            document.getElementById('btn-step1-next').disabled = false;
        };
        grid.appendChild(card);
    });
}

async function carregarCabeleireiros() {
    const grid = document.getElementById('cabeleireiros-grid');
    grid.innerHTML = '<div class="spinner-block"><span class="spinner-inline"></span>Carregando profissionais...</div>';
    const res = await api('listar_cabeleireiros');
    if (!res.success) return;
    grid.innerHTML = '';
    res.dados.forEach(c => {
        const card = document.createElement('div');
        card.className = 'prof-card';
        if (state.cabeleireiro && state.cabeleireiro.id == c.id) card.classList.add('selected');
        card.innerHTML = `
            <div class="prof-avatar">${c.nome.charAt(0)}</div>
            <h3>${c.nome}${c.favorito ? ' ★' : ''}</h3>
            <p>${c.especialidade || ''}</p>`;
        card.onclick = () => {
            state.cabeleireiro = c;
            document.querySelectorAll('#cabeleireiros-grid .prof-card').forEach(el => el.classList.remove('selected'));
            card.classList.add('selected');
            document.getElementById('btn-step2-next').disabled = false;
        };
        grid.appendChild(card);
    });
}

function carregarHorarios() {
    const data = document.getElementById('input-data').value;
    state.data = data;
    state.hora = null;
    document.getElementById('btn-step3-next').disabled = true;
    const container = document.getElementById('horarios-container');

    if (!data) {
        container.innerHTML = '<p class="empty-msg">Selecione uma data para ver os horários disponíveis.</p>';
        return;
    }

    container.innerHTML = '<div class="spinner-block"><span class="spinner-inline"></span>Buscando horários...</div>';

    api('listar_horarios', {
        cabeleireiro_id: state.cabeleireiro.id,
        servico_id: state.servico.id,
        data: data
    }).then(res => {
        if (!res.success) {
            container.innerHTML = '<p class="empty-msg">' + (res.msg || 'Erro ao buscar horários.') + '</p>';
            return;
        }
        if (res.dados.length === 0) {
            container.innerHTML = '<p class="empty-msg">Nenhum horário disponível nesta data. Tente outro dia.</p>';
            return;
        }
        const grid = document.createElement('div');
        grid.className = 'slot-grid';
        res.dados.forEach(hora => {
            const btn = document.createElement('button');
            btn.className = 'slot-btn';
            btn.textContent = hora;
            btn.onclick = () => {
                state.hora = hora;
                document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('selected'));
                btn.classList.add('selected');
                document.getElementById('btn-step3-next').disabled = false;
            };
            grid.appendChild(btn);
        });
        container.innerHTML = '';
        container.appendChild(grid);
    });
}

function montarResumo() {
    const dataFormatada = new Date(state.data + 'T00:00:00').toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'long' });
    document.getElementById('resumo-card').innerHTML = `
        <div class="summary-row"><span class="lbl">Serviço</span><span class="val">${state.servico.nome}</span></div>
        <div class="summary-row"><span class="lbl">Profissional</span><span class="val">${state.cabeleireiro.nome}</span></div>
        <div class="summary-row"><span class="lbl">Data</span><span class="val">${dataFormatada}</span></div>
        <div class="summary-row"><span class="lbl">Horário</span><span class="val">${state.hora}</span></div>
        <div class="summary-row"><span class="lbl">Duração</span><span class="val">${state.servico.duracao_min} min</span></div>
        <div class="summary-row"><span class="lbl">Valor</span><span class="val">R$ ${parseFloat(state.servico.preco).toFixed(2).replace('.', ',')}</span></div>
    `;
}

async function confirmarAgendamento() {
    const btn = document.getElementById('btn-confirmar');
    const alertEl = document.getElementById('alert-agendar');
    alertEl.classList.remove('show');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-inline"></span>Confirmando...';

    const res = await api('criar_agendamento', {
        cabeleireiro_id: state.cabeleireiro.id,
        servico_id: state.servico.id,
        data: state.data,
        hora: state.hora,
        observacoes: document.getElementById('input-obs').value.trim()
    }, 'POST');

    if (res.success) {
        showToast(res.msg);
        resetWizard();
        switchView('meus');
    } else {
        alertEl.textContent = res.msg;
        alertEl.className = 'alert error show';
        btn.disabled = false;
        btn.textContent = 'Confirmar agendamento';
    }
}

function resetWizard() {
    state.servico = null;
    state.cabeleireiro = null;
    state.data = null;
    state.hora = null;
    document.getElementById('btn-step1-next').disabled = true;
    document.getElementById('btn-step2-next').disabled = true;
    document.getElementById('btn-step3-next').disabled = true;
    document.getElementById('input-data').value = '';
    document.getElementById('input-obs').value = '';
    document.getElementById('horarios-container').innerHTML = '<p class="empty-msg">Selecione uma data para ver os horários disponíveis.</p>';
    document.getElementById('btn-confirmar').disabled = false;
    document.getElementById('btn-confirmar').textContent = 'Confirmar agendamento';
    goToStep(1);
    carregarServicos();
    carregarCabeleireiros();
}

const statusLabel = { pendente: 'Pendente', confirmado: 'Confirmado', cancelado: 'Cancelado', concluido: 'Concluído' };

async function carregarMeusAgendamentos() {
    const list = document.getElementById('meus-agendamentos-list');
    list.innerHTML = '<div class="spinner-block"><span class="spinner-inline"></span>Carregando...</div>';
    const res = await api('listar_meus_agendamentos');
    if (!res.success || res.dados.length === 0) {
        list.innerHTML = '<p class="empty-msg">Você ainda não possui agendamentos.</p>';
        return;
    }
    list.innerHTML = '';
    res.dados.forEach(a => {
        const dataObj = new Date(a.data_hora.replace(' ', 'T'));
        const dataFmt = dataObj.toLocaleDateString('pt-BR', { day: '2-digit', month: 'short', year: 'numeric' });
        const horaFmt = dataObj.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

        const item = document.createElement('div');
        item.className = 'book-item';
        item.innerHTML = `
            <div class="book-info">
                <h3>${a.servico_nome}</h3>
                <p>${a.cabeleireiro_nome} · ${dataFmt} às ${horaFmt} · R$ ${parseFloat(a.preco).toFixed(2).replace('.', ',')}</p>
                ${a.observacoes ? `<p class="obs">"${a.observacoes}"</p>` : ''}
            </div>
            <div class="book-actions">
                <span class="badge ${a.status}">${statusLabel[a.status] || a.status}</span>
                ${(a.status === 'pendente' || a.status === 'confirmado') ? `<button class="cancel-btn" data-id="${a.id}">Cancelar</button>` : ''}
            </div>`;
        list.appendChild(item);
    });

    list.querySelectorAll('.cancel-btn').forEach(btn => {
        btn.onclick = async () => {
            if (!confirm('Deseja realmente cancelar este agendamento?')) return;
            btn.disabled = true;
            btn.textContent = 'Cancelando...';
            const res = await api('cancelar_agendamento', { id: btn.dataset.id }, 'POST');
            if (res.success) {
                showToast(res.msg);
                carregarMeusAgendamentos();
            } else {
                showToast(res.msg);
                btn.disabled = false;
                btn.textContent = 'Cancelar';
            }
        };
    });
}

async function carregarProfissionaisLista() {
    const grid = document.getElementById('profissionais-lista-grid');
    grid.innerHTML = '<div class="spinner-block"><span class="spinner-inline"></span>Carregando...</div>';
    const res = await api('listar_cabeleireiros');
    if (!res.success) return;
    grid.innerHTML = '';
    res.dados.forEach(c => {
        const card = document.createElement('div');
        card.className = 'prof-card';
        card.innerHTML = `
            <button class="fav-star ${c.favorito ? 'active' : ''}" data-id="${c.id}" title="Favoritar">
                <svg viewBox="0 0 24 24" fill="currentColor" stroke="none"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z"/></svg>
            </button>
            <div class="prof-avatar">${c.nome.charAt(0)}</div>
            <h3>${c.nome}</h3>
            <p>${c.especialidade || ''}</p>`;
        grid.appendChild(card);
    });

    grid.querySelectorAll('.fav-star').forEach(btn => {
        btn.onclick = async (e) => {
            e.stopPropagation();
            const res = await api('toggle_favorito', { cabeleireiro_id: btn.dataset.id }, 'POST');
            if (res.success) {
                btn.classList.toggle('active', res.favorito);
                showToast(res.favorito ? 'Adicionado aos favoritos.' : 'Removido dos favoritos.');
            }
        };
    });
}

(function init() {
    const hoje = new Date().toISOString().split('T')[0];
    document.getElementById('input-data').min = hoje;
    carregarTiposCabelo();
    carregarCabeleireiros();
})();
