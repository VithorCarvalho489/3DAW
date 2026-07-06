<?php
require_once __DIR__ . '/api.php';

getDB();
$logado = isset($_SESSION['user_id']);
$nomeUsuario = $_SESSION['user_nome'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Beleza Noir — Salão de Beleza</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../frontend/style.css">
</head>

<?php if (!$logado): ?>
<body class="auth-body">

<section class="hero">

    <div class="scissors-wrap" aria-hidden="true">
        <svg viewBox="0 0 144 176" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g class="blade-top">
                <ellipse cx="30" cy="30" rx="22" ry="22" fill="none" stroke="#C9A84C" stroke-width="3"/>
                <ellipse cx="30" cy="30" rx="10" ry="10" fill="#C9A84C" opacity=".25"/>
                <line x1="47" y1="47" x2="114" y2="114" stroke="#C9A84C" stroke-width="5" stroke-linecap="round"/>
            </g>
            <g class="blade-bottom">
                <ellipse cx="30" cy="146" rx="22" ry="22" fill="none" stroke="#C9A84C" stroke-width="3"/>
                <ellipse cx="30" cy="146" rx="10" ry="10" fill="#C9A84C" opacity=".25"/>
                <line x1="47" y1="129" x2="114" y2="62" stroke="#C9A84C" stroke-width="5" stroke-linecap="round"/>
            </g>
            <circle cx="72" cy="88" r="5" fill="#C9A84C"/>
        </svg>
    </div>

    <p class="hero-tag">Salão Premium · Desde 2010</p>
    <div class="gold-line"></div>
    <h1>Realce sua <em>beleza</em><br>com quem entende de estilo.</h1>
    <p>Agende cortes, colorações e tratamentos com os melhores profissionais da cidade — tudo no conforto do seu celular.</p>
</section>

<div class="form-panel">

    <div class="tabs" role="tablist">
        <button class="tab-btn active" id="tab-login" role="tab" aria-selected="true"  onclick="switchTab('login')">Entrar</button>
        <button class="tab-btn"        id="tab-cad"   role="tab" aria-selected="false" onclick="switchTab('cad')">Criar conta</button>
    </div>

    <div id="form-login">
        <h2 class="form-title">Bem-vindo de volta</h2>
        <p class="form-subtitle">Acesse sua conta para gerenciar seus agendamentos.</p>

        <div class="field">
            <label for="login-email">E-mail</label>
            <input type="email" id="login-email" placeholder="seu@email.com" autocomplete="email">
        </div>
        <div class="field">
            <label for="login-senha">Senha</label>
            <div class="pw-wrap">
                <input type="password" id="login-senha" placeholder="••••••••" autocomplete="current-password">
                <button class="pw-toggle" type="button" onclick="togglePw('login-senha', this)" aria-label="Mostrar senha">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
        </div>

        <button class="btn-primary" id="btn-login" onclick="doLogin()">Entrar</button>
        <div class="alert" id="alert-login"></div>

        <div class="extra-links">
            Não tem conta? <a href="#" onclick="switchTab('cad'); return false">Cadastre-se grátis</a>
        </div>
    </div>

    <div id="form-cad" style="display:none">
        <h2 class="form-title">Crie sua conta</h2>
        <p class="form-subtitle">Rápido e gratuito. Comece a agendar em instantes.</p>

        <div class="field">
            <label for="cad-nome">Nome completo</label>
            <input type="text" id="cad-nome" placeholder="Maria Silva" autocomplete="name">
        </div>
        <div class="field">
            <label for="cad-email">E-mail</label>
            <input type="email" id="cad-email" placeholder="seu@email.com" autocomplete="email">
        </div>
        <div class="field">
            <label for="cad-tel">Telefone (opcional)</label>
            <input type="tel" id="cad-tel" placeholder="(21) 99999-9999" autocomplete="tel">
        </div>
        <div class="field">
            <label for="cad-senha">Senha</label>
            <div class="pw-wrap">
                <input type="password" id="cad-senha" placeholder="mínimo 6 caracteres" autocomplete="new-password">
                <button class="pw-toggle" type="button" onclick="togglePw('cad-senha', this)" aria-label="Mostrar senha">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
        </div>

        <button class="btn-primary" id="btn-cad" onclick="doCadastro()">Criar conta</button>
        <div class="alert" id="alert-cad"></div>

        <div class="extra-links">
            Já tem conta? <a href="#" onclick="switchTab('login'); return false">Faça login</a>
        </div>
    </div>
</div>

<div class="welcome-overlay" id="welcome">
    <div class="wicon">✂️</div>
    <h2 id="welcome-name">Olá!</h2>
    <p>Login realizado com sucesso. Redirecionando...</p>
</div>

<script src="../js/auth.js"></script>
</body>

<?php else: ?>
<body>

<div class="app-body">

<aside class="sidebar">
    <div class="brand">
        <svg viewBox="0 0 144 176" fill="none" xmlns="http://www.w3.org/2000/svg">
            <ellipse cx="30" cy="30" rx="20" ry="20" fill="none" stroke="#C9A84C" stroke-width="4"/>
            <ellipse cx="30" cy="146" rx="20" ry="20" fill="none" stroke="#C9A84C" stroke-width="4"/>
            <line x1="46" y1="46" x2="114" y2="114" stroke="#C9A84C" stroke-width="6" stroke-linecap="round"/>
            <line x1="46" y1="130" x2="114" y2="62" stroke="#C9A84C" stroke-width="6" stroke-linecap="round"/>
            <circle cx="72" cy="88" r="6" fill="#C9A84C"/>
        </svg>
        <span>Beleza <em>Noir</em></span>
    </div>

    <div class="user-card">
        <div class="user-avatar" id="user-initial"></div>
        <div class="user-info">
            <p id="user-nome"><?php echo htmlspecialchars($nomeUsuario); ?></p>
            <span>Cliente</span>
        </div>
    </div>

    <button class="nav-btn active" data-view="agendar" onclick="switchView('agendar')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        Agendar horário
    </button>
    <button class="nav-btn" data-view="meus" onclick="switchView('meus')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg>
        Meus agendamentos
    </button>
    <button class="nav-btn" data-view="profissionais" onclick="switchView('profissionais')">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.8 4.6a5 5 0 0 0-7.1 0L12 6.3l-1.7-1.7a5 5 0 1 0-7.1 7.1L12 20.3l8.8-8.6a5 5 0 0 0 0-7.1z"/></svg>
        Profissionais
    </button>

    <div class="nav-spacer"></div>

    <button class="logout-btn" onclick="location.href='api.php?logout=1'">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
        Sair
    </button>
</aside>

<main class="main">

    <div class="view active" id="view-agendar">
        <div class="page-header">
            <p class="tag">Novo Agendamento</p>
            <h1>Vamos marcar seu horário</h1>
        </div>

        <div class="steps">
            <div class="step active" data-step="1"><div class="step-num">1</div><div class="step-label">Serviço</div></div>
            <div class="step" data-step="2"><div class="step-num">2</div><div class="step-label">Profissional</div></div>
            <div class="step" data-step="3"><div class="step-num">3</div><div class="step-label">Data e horário</div></div>
            <div class="step" data-step="4"><div class="step-num">4</div><div class="step-label">Confirmação</div></div>
        </div>

        <div class="wizard-panel active" id="panel-1">
            <div class="chip-row" id="tipos-cabelo-chips"></div>
            <div class="card-grid" id="servicos-grid"><div class="spinner-block"><span class="spinner-inline"></span>Carregando serviços...</div></div>
            <div class="wizard-actions">
                <button class="btn btn-gold" id="btn-step1-next" disabled onclick="goToStep(2)">Continuar</button>
            </div>
        </div>

        <div class="wizard-panel" id="panel-2">
            <div class="card-grid" id="cabeleireiros-grid"><div class="spinner-block"><span class="spinner-inline"></span>Carregando profissionais...</div></div>
            <div class="wizard-actions">
                <button class="btn btn-ghost" onclick="goToStep(1)">Voltar</button>
                <button class="btn btn-gold" id="btn-step2-next" disabled onclick="goToStep(3)">Continuar</button>
            </div>
        </div>

        <div class="wizard-panel" id="panel-3">
            <div class="date-field">
                <label for="input-data">Escolha a data</label>
                <input type="date" id="input-data" onchange="carregarHorarios()">
            </div>
            <div id="horarios-container"><p class="empty-msg">Selecione uma data para ver os horários disponíveis.</p></div>
            <div class="wizard-actions">
                <button class="btn btn-ghost" onclick="goToStep(2)">Voltar</button>
                <button class="btn btn-gold" id="btn-step3-next" disabled onclick="goToStep(4)">Continuar</button>
            </div>
        </div>

        <div class="wizard-panel" id="panel-4">
            <div class="summary-card" id="resumo-card"></div>
            <div class="textarea-field">
                <label for="input-obs">Observações (opcional)</label>
                <textarea id="input-obs" placeholder="Alguma preferência ou informação para o profissional?"></textarea>
            </div>
            <div class="wizard-actions">
                <button class="btn btn-ghost" onclick="goToStep(3)">Voltar</button>
                <button class="btn btn-gold" id="btn-confirmar" onclick="confirmarAgendamento()">Confirmar agendamento</button>
            </div>
            <div class="alert" id="alert-agendar"></div>
        </div>
    </div>

    <div class="view" id="view-meus">
        <div class="page-header">
            <p class="tag">Histórico</p>
            <h1>Meus agendamentos</h1>
        </div>
        <div class="book-list" id="meus-agendamentos-list"><div class="spinner-block"><span class="spinner-inline"></span>Carregando...</div></div>
    </div>

    <div class="view" id="view-profissionais">
        <div class="page-header">
            <p class="tag">Nossa Equipe</p>
            <h1>Profissionais</h1>
        </div>
        <div class="card-grid" id="profissionais-lista-grid"><div class="spinner-block"><span class="spinner-inline"></span>Carregando...</div></div>
    </div>

</main>

</div>

<div class="toast" id="toast"></div>

<script src="../js/app.js"></script>
</body>
<?php endif; ?>
</html>
