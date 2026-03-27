<?php
// =================================================================
//  KONEX CREATIVE — PAINEL ADMIN (COMPLETO, SEGURO, SEM DUPLICATAS)
// =================================================================
header('Content-Type: text/html; charset=utf-8');
// SENHA NUNCA É EXPOSTA NO CLIENTE — validação só no servidor (api.php)
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KONEX Admin — Painel de Controle</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#0b0f1a;--panel:#111827;--card:#1f2937;--border:#374151;
  --primary:#6366f1;--primary2:#8b5cf6;--success:#10b981;
  --danger:#ef4444;--warn:#f59e0b;--txt:#f9fafb;--muted:#9ca3af;--txt2:#d1d5db
}
body{background:var(--bg);color:var(--txt);font-family:'Inter',sans-serif;min-height:100vh}
::-webkit-scrollbar{width:6px}
::-webkit-scrollbar-track{background:var(--bg)}
::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px}

/* ── LOGIN ── */
#loginScreen{min-height:100vh;display:flex;align-items:center;justify-content:center;
  background:radial-gradient(ellipse at 30% 50%,rgba(99,102,241,.15) 0%,transparent 60%),
             radial-gradient(ellipse at 80% 20%,rgba(139,92,246,.1) 0%,transparent 50%),var(--bg)}
.login-card{background:var(--panel);border:1px solid var(--border);border-radius:20px;
  padding:48px 40px;width:min(420px,95vw);text-align:center;box-shadow:0 25px 60px rgba(0,0,0,.5)}
.login-logo{font-size:32px;font-weight:900;letter-spacing:2px;margin-bottom:8px}
.login-logo span{color:var(--primary)}
.login-sub{color:var(--muted);font-size:13px;margin-bottom:32px}
.login-card input[type="password"],.login-card input[type="text"]{
  width:100%;background:var(--card);border:1px solid var(--border);color:var(--txt);
  padding:14px 18px;border-radius:12px;font-size:14px;outline:none;transition:.2s;margin-bottom:0}
.login-card input:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(99,102,241,.2)}
.btn-login{width:100%;background:linear-gradient(135deg,var(--primary),var(--primary2));border:none;
  color:#fff;padding:14px;border-radius:12px;font-size:15px;font-weight:700;cursor:pointer;
  transition:.2s;letter-spacing:.5px;margin-top:10px}
.btn-login:hover{opacity:.9;transform:translateY(-1px)}
.btn-login:disabled{opacity:.5;cursor:not-allowed;transform:none}
.login-err{color:var(--danger);font-size:12px;margin-top:12px;height:15px}
.checkbox-container{display:flex;align-items:center;gap:8px;justify-content:flex-start;
  margin-bottom:15px;margin-top:14px;font-size:13px;color:var(--muted);cursor:pointer}
.checkbox-container input{accent-color:var(--primary);cursor:pointer}

/* ── APP ── */
#adminApp{display:none;min-height:100vh;flex-direction:row}
#adminApp.visible{display:flex}

/* ── SIDEBAR ── */
.sidebar{width:220px;min-height:100vh;background:var(--panel);border-right:1px solid var(--border);
  display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:200;
  transition:transform .25s ease}
.sidebar-header{padding:22px 20px 18px;border-bottom:1px solid var(--border)}
.sidebar-logo{font-weight:900;font-size:20px;letter-spacing:1px;color:var(--txt)}
.sidebar-logo span{color:var(--primary)}
.sidebar-sub{font-size:10px;color:var(--muted);margin-top:2px;text-transform:uppercase;letter-spacing:.5px}
.sidebar-nav{flex:1;padding:10px 10px;overflow-y:auto}
.sidebar-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;
  font-size:13px;font-weight:600;color:var(--muted);cursor:pointer;transition:.18s;
  user-select:none;margin-bottom:2px;white-space:nowrap}
.sidebar-item:hover{background:rgba(255,255,255,.05);color:var(--txt)}
.sidebar-item.active{background:rgba(99,102,241,.15);color:var(--primary)}
.sidebar-item .si-icon{font-size:16px;width:22px;text-align:center;flex-shrink:0}
.sidebar-section{font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;
  letter-spacing:.8px;padding:12px 12px 4px;margin-top:4px}
.sidebar-footer{padding:14px 10px;border-top:1px solid var(--border)}
.sidebar-footer .badge-admin{display:block;text-align:center;margin-bottom:8px;
  background:rgba(99,102,241,.2);border:1px solid rgba(99,102,241,.4);
  color:var(--primary);font-size:11px;font-weight:700;padding:4px 12px;border-radius:99px}

/* ── MAIN AREA ── */
#mainArea{flex:1;margin-left:220px;min-height:100vh;display:flex;flex-direction:column}
.topbar{background:var(--panel);border-bottom:1px solid var(--border);padding:12px 24px;
  display:flex;align-items:center;position:sticky;top:0;z-index:100}
.topbar-left{display:flex;align-items:center;gap:10px}
.topbar-title{font-weight:700;font-size:16px}
.badge-admin{background:rgba(99,102,241,.2);border:1px solid rgba(99,102,241,.4);
  color:var(--primary);font-size:11px;font-weight:700;padding:4px 12px;border-radius:99px}
.btn-logout{background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3);
  padding:8px 16px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer}
.btn-hamburger{display:none;background:none;border:1px solid var(--border);color:var(--txt);
  padding:6px 10px;border-radius:8px;cursor:pointer;font-size:16px}
.content{padding:24px;flex:1}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:199}

/* ── CARDS / STATS ── */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:28px}
.stat-card{background:var(--panel);border:1px solid var(--border);border-radius:16px;padding:22px;
  position:relative;overflow:hidden;transition:.2s}
.stat-card::before{content:'';position:absolute;top:0;right:0;width:80px;height:80px;
  border-radius:50%;opacity:.08;transform:translate(20px,-20px)}
.stat-card.indigo::before{background:var(--primary)}
.stat-card.green::before{background:var(--success)}
.stat-card.amber::before{background:var(--warn)}
.stat-card.red::before{background:var(--danger)}
.stat-card.cyan::before{background:#06b6d4}
.stat-card.violet::before{background:#a855f7}
.stat-icon{font-size:24px;margin-bottom:10px}
.stat-val{font-size:28px;font-weight:900;margin-bottom:4px}
.stat-label{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px}

/* ── TABLES ── */
.table-card{background:var(--panel);border:1px solid var(--border);border-radius:16px;
  overflow:hidden;margin-bottom:20px}
.table-header{display:flex;align-items:center;justify-content:space-between;
  padding:18px 22px;border-bottom:1px solid var(--border)}
.table-title{font-weight:700;font-size:14px}
table{width:100%;border-collapse:collapse}
th{background:rgba(99,102,241,.08);color:var(--muted);font-size:11px;font-weight:700;
  text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;white-space:nowrap}
td{padding:13px 16px;font-size:13px;border-top:1px solid rgba(255,255,255,.04)}
tr:hover td{background:rgba(255,255,255,.02)}

/* ── BADGES ── */
.b{display:inline-block;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700}
.b-green{background:rgba(16,185,129,.15);color:#34d399;border:1px solid rgba(16,185,129,.3)}
.b-red{background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3)}
.b-amber{background:rgba(245,158,11,.15);color:#fbbf24;border:1px solid rgba(245,158,11,.3)}
.b-indigo{background:rgba(99,102,241,.15);color:#a5b4fc;border:1px solid rgba(99,102,241,.3)}
.b-cyan{background:rgba(6,182,212,.15);color:#67e8f9;border:1px solid rgba(6,182,212,.3)}

/* ── BUTTONS ── */
.btn{padding:8px 16px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;
  transition:.2s;border:none;display:inline-flex;align-items:center;gap:6px}
.btn-primary{background:linear-gradient(135deg,var(--primary),var(--primary2));color:#fff}
.btn-primary:hover{opacity:.85}
.btn-success{background:rgba(16,185,129,.2);color:#34d399;border:1px solid rgba(16,185,129,.3)}
.btn-success:hover{background:rgba(16,185,129,.35)}
.btn-danger{background:rgba(239,68,68,.2);color:#f87171;border:1px solid rgba(239,68,68,.3)}
.btn-danger:hover{background:rgba(239,68,68,.35)}
.btn-warn{background:rgba(245,158,11,.2);color:#fbbf24;border:1px solid rgba(245,158,11,.3)}
.btn-warn:hover{background:rgba(245,158,11,.35)}
.btn-info{background:rgba(59,130,246,.15);color:#60a5fa;border:1px solid rgba(59,130,246,.3)}
.btn-info:hover{background:rgba(59,130,246,.25)}
.btn-ghost{background:transparent;color:var(--muted);border:1px solid var(--border)}
.btn-ghost:hover{border-color:var(--primary);color:var(--primary)}
.btn-logout{background:rgba(239,68,68,.15);color:#f87171;border:1px solid rgba(239,68,68,.3);
  padding:8px 16px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer}

/* ── FORMS ── */
.form-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:16px}
.form-group label{display:block;font-size:11px;font-weight:700;color:var(--muted);
  text-transform:uppercase;margin-bottom:6px}
.form-group input,.form-group select,.form-group textarea{
  width:100%;background:var(--card);border:1px solid var(--border);color:var(--txt);
  padding:11px 14px;border-radius:10px;font-size:13px;outline:none;transition:.2s}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{
  border-color:var(--primary);box-shadow:0 0 0 3px rgba(99,102,241,.15)}
.form-group select option{background:var(--card)}

/* ── MODALS ── */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.75);backdrop-filter:blur(5px);
  z-index:9000;display:none;align-items:center;justify-content:center;padding:16px}
.modal-overlay.open{display:flex}
.modal{background:var(--panel);border:1px solid var(--border);border-radius:20px;padding:28px;
  width:min(540px,100%);box-shadow:0 25px 60px rgba(0,0,0,.6);
  max-height:90vh;overflow-y:auto;position:relative;z-index:9001}
.modal *{pointer-events:auto}
.modal-title{font-weight:800;font-size:17px;margin-bottom:20px;display:flex;align-items:center;gap:10px}

/* ── CONFIRM MODAL ── */
#modalConfirm .modal{width:min(420px,100%)}
.confirm-msg{font-size:14px;color:var(--txt2);margin-bottom:24px;line-height:1.6}
.confirm-btns{display:flex;gap:10px;justify-content:flex-end}

/* ── CREDITS ── */
.credit-input-wrap{position:relative}
.credit-input-wrap input[type="number"]{font-size:22px;font-weight:900;text-align:center;
  background:linear-gradient(135deg,rgba(99,102,241,.1),rgba(139,92,246,.1));
  border:2px solid rgba(99,102,241,.4);color:#a5b4fc;padding:14px;border-radius:12px;
  letter-spacing:2px;-moz-appearance:textfield}
.credit-input-wrap input[type="number"]::-webkit-outer-spin-button,
.credit-input-wrap input[type="number"]::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}
.credit-input-wrap input[type="number"]:focus{border-color:var(--primary);box-shadow:0 0 0 4px rgba(99,102,241,.2)}
.credit-stepper{display:flex;gap:8px;justify-content:center;margin-top:8px}
.credit-stepper button{background:var(--card);border:1px solid var(--border);color:var(--txt);
  width:36px;height:36px;border-radius:8px;font-size:18px;font-weight:700;cursor:pointer;
  display:flex;align-items:center;justify-content:center;transition:.2s}
.credit-stepper button:hover{border-color:var(--primary);color:var(--primary)}
.saldo-badge{background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.3);color:#34d399;
  border-radius:12px;padding:10px 16px;font-size:13px;font-weight:700;
  display:flex;align-items:center;gap:8px;margin-bottom:16px}

/* ── PAGINATION ── */
.pagination{display:flex;gap:8px;align-items:center;justify-content:center;
  margin-top:16px;flex-wrap:wrap}
.page-btn{background:var(--card);border:1px solid var(--border);color:var(--txt);
  padding:8px 14px;border-radius:8px;cursor:pointer;font-size:13px;transition:.2s}
.page-btn:hover,.page-btn.active{background:var(--primary);border-color:var(--primary);color:#fff}

/* ── TAB SECTIONS ── */
.tab-section{display:none}
.tab-section.active{display:block}

/* ── TOAST ── */
#toast{position:fixed;bottom:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:8px}
.toast-item{background:var(--panel);border:1px solid var(--border);border-radius:12px;
  padding:14px 18px;font-size:13px;display:flex;align-items:center;gap:10px;
  min-width:280px;box-shadow:0 10px 30px rgba(0,0,0,.4);animation:slideUp .3s ease;pointer-events:none}
.toast-item.success{border-color:rgba(16,185,129,.5)}
.toast-item.error{border-color:rgba(239,68,68,.5)}
.toast-item.info{border-color:rgba(99,102,241,.5)}
.toast-item.warn{border-color:rgba(245,158,11,.5)}
@keyframes slideUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}

/* ── MISC ── */
.search-box{background:var(--card);border:1px solid var(--border);color:var(--txt);
  padding:10px 16px;border-radius:10px;font-size:13px;outline:none;width:250px}
.search-box:focus{border-color:var(--primary)}
.config-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px}
.config-card{background:var(--panel);border:1px solid var(--border);border-radius:16px;padding:22px}
.config-card-title{font-weight:700;font-size:14px;margin-bottom:16px;color:var(--primary);
  display:flex;align-items:center;gap:8px}
.user-count-bar{display:flex;align-items:center;gap:10px;padding:10px 22px;
  background:rgba(99,102,241,.05);border-bottom:1px solid var(--border);
  font-size:12px;color:var(--muted)}
.user-count-bar b{color:var(--txt)}
.spinner{display:inline-block;width:18px;height:18px;border:2px solid var(--border);
  border-top-color:var(--primary);border-radius:50%;animation:spin .6s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.loading-row td{text-align:center;padding:40px;color:var(--muted)}

@media(max-width:768px){
  .sidebar{transform:translateX(-100%)}
  .sidebar.open{transform:translateX(0)}
  .sidebar-overlay.open{display:block}
  #mainArea{margin-left:0}
  .btn-hamburger{display:inline-flex;align-items:center}
  .topbar{padding:10px 14px}
  .content{padding:14px}
  .stats-grid{grid-template-columns:1fr 1fr}
  .search-box{width:150px}
}
</style>
</head>
<body>

<div id="loginScreen">
  <div class="login-card">
    <div class="login-logo">KONEX<span>.</span></div>
    <div class="login-sub">Painel Administrativo Seguro</div>
    <div style="position:relative;margin-bottom:14px;width:100%">
      <input type="password" id="loginSenha" placeholder="🔒 Senha de Acesso"
        onkeydown="if(event.key==='Enter'){event.preventDefault();doLogin();}"
        style="padding-right:40px">
      <span id="toggleEye" onclick="togglePassword()"
        style="position:absolute;right:14px;top:14px;cursor:pointer;color:var(--muted);font-size:16px;user-select:none"
        title="Mostrar/Ocultar Senha">👁️</span>
    </div>
    <label class="checkbox-container">
      <input type="checkbox" id="lembrarSenha"> Lembrar minha senha
    </label>
    <button class="btn-login" id="btnLogin" onclick="doLogin()">ENTRAR NO PAINEL</button>
    <div id="loginErr" class="login-err"></div>
  </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div id="adminApp">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <div class="sidebar-logo">KONEX<span>.</span></div>
      <div class="sidebar-sub">Painel Administrativo</div>
    </div>
    <nav class="sidebar-nav">
      <div class="sidebar-section">Visão Geral</div>
      <div class="sidebar-item active" onclick="showTab('dashboard',this)">
        <span class="si-icon">📊</span> Dashboard
      </div>
      <div class="sidebar-section">Gestão</div>
      <div class="sidebar-item" onclick="showTab('usuarios',this)">
        <span class="si-icon">👥</span> Usuários
      </div>
      <div class="sidebar-item" onclick="showTab('pedidos',this)">
        <span class="si-icon">💳</span> Pedidos
      </div>
      <div class="sidebar-item" onclick="showTab('transacoes',this)">
        <span class="si-icon">🧾</span> Transações
      </div>
      <div class="sidebar-item" onclick="showTab('financeiro',this)">
        <span class="si-icon">📈</span> Financeiro
      </div>
      <div class="sidebar-section">Configuração</div>
      <div class="sidebar-item" onclick="showTab('configuracoes',this)">
        <span class="si-icon">⚙️</span> Configurações
      </div>
      <div class="sidebar-item" onclick="showTab('loja_config',this)">
        <span class="si-icon">🛍️</span> Loja
      </div>
      <div class="sidebar-item" onclick="showTab('seguranca',this)">
        <span class="si-icon">🔒</span> Segurança
      </div>
    </nav>
    <div class="sidebar-footer">
      <span class="badge-admin">👑 ADMINISTRADOR</span>
      <button class="btn-logout" style="width:100%;margin-top:6px" onclick="doLogout()">🚪 Sair</button>
    </div>
  </aside>

  <div id="mainArea">
    <div class="topbar">
      <div class="topbar-left">
        <button class="btn-hamburger" onclick="toggleSidebar()">☰</button>
        <span class="topbar-title" id="topbarTitle">📊 Dashboard</span>
      </div>
    </div>

  <div class="content">

    <div id="tab-dashboard" class="tab-section active">
      <h2 style="margin-bottom:20px;font-size:20px">📊 Visão Geral</h2>
      <div class="stats-grid">
        <div class="stat-card indigo"><div class="stat-icon">👥</div><div class="stat-val" id="sTotal">—</div><div class="stat-label">Total de Usuários</div></div>
        <div class="stat-card green"><div class="stat-icon">✅</div><div class="stat-val" id="sAtivos">—</div><div class="stat-label">Usuários Ativos</div></div>
        <div class="stat-card violet"><div class="stat-icon">📄</div><div class="stat-val" id="sDownloads">—</div><div class="stat-label">PDFs Gerados</div></div>
        <div class="stat-card amber"><div class="stat-icon">🎫</div><div class="stat-val" id="sCred">—</div><div class="stat-label">Créditos Vendidos</div></div>
        <div class="stat-card cyan"><div class="stat-icon">🛒</div><div class="stat-val" id="sPedidos">—</div><div class="stat-label">Pedidos Aprovados</div></div>
        <div class="stat-card green"><div class="stat-icon">💰</div><div class="stat-val" id="sReceita">—</div><div class="stat-label">Receita Total (R$)</div></div>
      </div>
      <div class="table-card">
        <div class="table-header">
          <div class="table-title">Últimos Pedidos</div>
          <button class="btn btn-danger" style="font-size:11px"
            onclick="confirmar('⚠️ Apagar TODO o histórico de pedidos? Esta ação é irreversível.',limparHistoricoPedidos)">
            🗑 Limpar Histórico
          </button>
        </div>
        <div style="overflow-x:auto"><table>
          <thead><tr><th>ID</th><th>Email</th><th>Plano</th><th>Créditos</th><th>Valor</th><th>Status</th><th>Data</th></tr></thead>
          <tbody id="tbUltimosPedidos"></tbody>
        </table></div>
      </div>
    </div>

    <div id="tab-usuarios" class="tab-section">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
        <h2 style="font-size:20px">👥 Gerenciar Usuários</h2>
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
          <input class="search-box" id="searchUser" placeholder="🔍 Buscar nome ou email..." oninput="debounceSearch()">
          <button class="btn btn-success" onclick="exportCSV('usuarios')">⬇️ CSV</button>
          <button class="btn btn-primary" onclick="openModalAddUser()">➕ Novo Usuário</button>
        </div>
      </div>
      <div id="userCountBar" class="user-count-bar" style="display:none">
        Exibindo <b id="userCountShowing">0</b> de <b id="userCountTotal">0</b> usuários
      </div>
      <div class="table-card">
        <div style="overflow-x:auto"><table>
          <thead><tr><th>ID</th><th>Email</th><th>Nome</th><th>CPF</th><th>Créditos</th><th>Plano</th><th>Status</th><th>Criado em</th><th>Ações</th></tr></thead>
          <tbody id="tbUsuarios"><tr class="loading-row"><td colspan="9"><div class="spinner"></div> Carregando...</td></tr></tbody>
        </table></div>
        <div class="pagination" id="paginacao"></div>
      </div>
    </div>

    <div id="tab-pedidos" class="tab-section">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
        <h2 style="font-size:20px">💳 Pedidos</h2>
        <button class="btn btn-success" onclick="exportCSV('pedidos')">⬇️ CSV</button>
      </div>
      <div class="table-card">
        <div class="table-header"><div class="table-title">Filtros</div></div>
        <div style="padding:18px 22px">
          <div class="form-row">
            <div class="form-group"><label>Buscar (email / gateway)</label><input id="p_busca" placeholder="ex: gmail ou id MP"></div>
            <div class="form-group"><label>Status</label><select id="p_status"><option value="">Todos</option><option value="aprovado">Aprovado</option><option value="pendente">Pendente</option><option value="cancelado">Cancelado</option></select></div>
            <div class="form-group"><label>Plano</label><select id="p_plano"><option value="">Todos</option><option value="basico">Básico</option><option value="profissional">Profissional</option><option value="agencia">Agência</option></select></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label>De</label><input id="p_de" type="date"></div>
            <div class="form-group"><label>Até</label><input id="p_ate" type="date"></div>
            <div class="form-group" style="display:flex;align-items:flex-end;gap:10px">
              <button class="btn btn-primary" onclick="loadPedidos(1)">🔎 Aplicar</button>
              <button class="btn btn-ghost" onclick="resetPedidosFiltros()">↺ Limpar</button>
            </div>
          </div>
        </div>
      </div>
      <div class="table-card">
        <div class="table-header">
          <div class="table-title">Lista de Pedidos</div>
          <span style="color:var(--muted);font-size:12px" id="p_total">—</span>
        </div>
        <div style="overflow-x:auto"><table>
          <thead><tr><th>ID</th><th>Email</th><th>Plano</th><th>Créditos</th><th>Valor</th><th>Status</th><th>Gateway ID</th><th>Data</th><th>Ação</th></tr></thead>
          <tbody id="tbPedidos"></tbody>
        </table></div>
        <div class="pagination" id="paginacaoPedidos"></div>
      </div>
    </div>

    <div id="tab-transacoes" class="tab-section">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
        <h2 style="font-size:20px">🧾 Transações</h2>
        <button class="btn btn-success" onclick="exportCSV('transacoes')">⬇️ CSV</button>
      </div>
      <div class="table-card">
        <div class="table-header"><div class="table-title">Filtros</div></div>
        <div style="padding:18px 22px">
          <div class="form-row">
            <div class="form-group"><label>Buscar (email/nome/desc)</label><input id="t_busca" placeholder="ex: compra, download"></div>
            <div class="form-group"><label>Tipo</label><select id="t_tipo"><option value="">Todos</option><option value="compra">Compra</option><option value="consumo">Consumo</option><option value="bonus">Bônus</option><option value="estorno">Estorno</option><option value="manual">Manual</option></select></div>
            <div class="form-group"><label>De</label><input id="t_de" type="date"></div>
            <div class="form-group"><label>Até</label><input id="t_ate" type="date"></div>
          </div>
          <div style="display:flex;gap:10px;justify-content:flex-end">
            <button class="btn btn-primary" onclick="loadTransacoes(1)">🔎 Aplicar</button>
            <button class="btn btn-ghost" onclick="resetTransacoesFiltros()">↺ Limpar</button>
          </div>
        </div>
      </div>
      <div class="table-card">
        <div class="table-header">
          <div class="table-title">Lista de Transações</div>
          <div style="display:flex;gap:10px;align-items:center">
            <span style="color:var(--muted);font-size:12px" id="t_total">—</span>
            <button class="btn btn-danger" style="font-size:11px;padding:4px 8px"
              onclick="confirmar('⚠️ Apagar TODAS as transações? Esta ação é irreversível.',limparTransacoes)">
              🗑 Limpar Transações
            </button>
          </div>
        </div>
        <div style="overflow-x:auto"><table>
          <thead><tr><th>ID</th><th>Usuário</th><th>Tipo</th><th>Qtd</th><th>Descrição</th><th>Referência</th><th>IP</th><th>Data</th><th>Ação</th></tr></thead>
          <tbody id="tbTransacoes"></tbody>
        </table></div>
        <div class="pagination" id="paginacaoTransacoes"></div>
      </div>
    </div>

    <div id="tab-financeiro" class="tab-section">
      <h2 style="margin-bottom:20px;font-size:20px">📈 Financeiro</h2>
      <div class="table-card">
        <div class="table-header"><div class="table-title">Período</div></div>
        <div style="padding:18px 22px">
          <div class="form-row">
            <div class="form-group"><label>De</label><input id="f_de" type="date"></div>
            <div class="form-group"><label>Até</label><input id="f_ate" type="date"></div>
            <div class="form-group" style="display:flex;align-items:flex-end;gap:10px">
              <button class="btn btn-primary" onclick="loadFinance()">📊 Atualizar</button>
              <button class="btn btn-ghost" onclick="resetFinancePeriodo()">↺ Limpar</button>
            </div>
          </div>
        </div>
      </div>
      <div class="stats-grid" style="margin-top:18px">
        <div class="stat-card green"><div class="stat-icon">💰</div><div class="stat-val" id="f_receita">—</div><div class="stat-label">Receita (R$)</div></div>
        <div class="stat-card cyan"><div class="stat-icon">🛒</div><div class="stat-val" id="f_pedidos">—</div><div class="stat-label">Pedidos Aprovados</div></div>
        <div class="stat-card amber"><div class="stat-icon">🎯</div><div class="stat-val" id="f_ticket">—</div><div class="stat-label">Ticket Médio</div></div>
        <div class="stat-card violet"><div class="stat-icon">🎫</div><div class="stat-val" id="f_vendidos">—</div><div class="stat-label">Créditos Vendidos</div></div>
        <div class="stat-card red"><div class="stat-icon">📄</div><div class="stat-val" id="f_pdfs">—</div><div class="stat-label">PDFs Gerados</div></div>
        <div class="stat-card indigo"><div class="stat-icon">🏦</div><div class="stat-val" id="f_saldo">—</div><div class="stat-label">Saldo Usuários</div></div>
      </div>
      <div class="table-card">
        <div class="table-header"><div class="table-title">Receita por Dia</div></div>
        <div style="padding:18px 22px;overflow-x:auto">
          <canvas id="chartReceita" width="1100" height="280"
            style="background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.06);border-radius:14px;max-width:100%">
          </canvas>
        </div>
      </div>
    </div>

    <div id="tab-configuracoes" class="tab-section">
      <h2 style="margin-bottom:20px;font-size:20px">⚙️ Configurações do Sistema</h2>
      <div class="config-grid" id="configGrid"></div>
      <button class="btn btn-primary" style="margin-top:16px;padding:12px 28px;font-size:14px"
        onclick="salvarConfigs()">💾 Salvar Configurações</button>
    </div>

    <div id="tab-loja_config" class="tab-section">
      <h2 style="margin-bottom:20px;font-size:20px">🛍️ Configurar Loja</h2>
      <div class="config-grid">
        <div class="config-card" style="grid-column:1/-1">
          <div class="config-card-title">🎬 Vídeo de Apresentação</div>
          <div class="form-row">
            <div class="form-group" style="flex:2"><label>URL do Vídeo</label><input id="loja_video_url" oninput="previewLojaVideo()"></div>
            <div class="form-group"><label>Título do Vídeo</label><input id="loja_video_titulo"></div>
          </div>
          <div id="lojaVideoPreview" style="display:none;margin-top:12px">
            <iframe id="lojaVideoIframe" width="100%" height="220" frameborder="0"
              style="border-radius:12px;border:1px solid var(--border)"></iframe>
          </div>
        </div>
        <div class="config-card">
          <div class="config-card-title">📊 Contador Social</div>
          <div class="form-group"><label>Currículos Criados</label><input id="loja_curriculos_criados" type="number"></div>
          <div class="form-group"><label>Texto do Contador</label><input id="loja_contador_texto"></div>
          <div class="form-group"><label>⭐ Avaliação</label><input id="loja_rating" type="number" step="0.1" min="1" max="5"></div>
        </div>
        <div class="config-card">
          <div class="config-card-title">🎨 Layout Ativo</div>
          <div id="layoutSelectedInfo"
            style="background:rgba(99,102,241,.08);border:1px solid rgba(99,102,241,.25);border-radius:10px;padding:12px;margin-bottom:14px;font-size:13px">
            Layout atual: <strong id="selectedLayoutName" style="color:var(--primary)">Dark Modern</strong>
          </div>
          <input type="hidden" id="loja_layout_value" value="dark_modern">
        </div>
      </div>
      <button class="btn btn-primary" style="margin-top:16px;padding:12px 28px;font-size:14px"
        onclick="salvarLojaConfig()">💾 Salvar Configurações da Loja</button>

      <h3 style="margin:28px 0 6px;font-size:16px">🖥️ Escolher Layout da Loja</h3>
      <p style="color:var(--muted);font-size:13px;margin-bottom:16px">Clique em <strong>Aplicar</strong> para alterar a aparência da loja.</p>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px">

        <div class="layout-opt" data-layout="dark_modern" onclick="selectLayout(this)"
          style="border:2px solid var(--primary);border-radius:16px;overflow:hidden;cursor:pointer">
          <div style="background:linear-gradient(135deg,#0b0f1a,#1a1f35);padding:20px;min-height:140px">
            <div style="color:#6366f1;font-size:11px;font-weight:700;letter-spacing:2px;margin-bottom:6px">KONEX CREATIVE</div>
            <div style="color:#fff;font-size:17px;font-weight:900;line-height:1.2;margin-bottom:8px">Crie seu currículo<br>profissional</div>
            <div style="background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;padding:6px 14px;border-radius:99px;font-size:11px;font-weight:700;display:inline-block">🚀 Começar Grátis</div>
          </div>
          <div style="background:var(--card);padding:10px 14px;display:flex;justify-content:space-between;align-items:center">
            <div><div style="font-weight:700;font-size:13px">Dark Modern</div><div style="font-size:11px;color:var(--muted)">Fundo escuro</div></div>
            <button class="btn btn-primary" style="font-size:11px;padding:5px 10px"
              onclick="event.stopPropagation();aplicarLayout('dark_modern')">✅ Aplicar</button>
          </div>
        </div>

        <div class="layout-opt" data-layout="light_clean" onclick="selectLayout(this)"
          style="border:2px solid var(--border);border-radius:16px;overflow:hidden;cursor:pointer">
          <div style="background:linear-gradient(135deg,#f8fafc,#e2e8f0);padding:20px;min-height:140px">
            <div style="color:#6366f1;font-size:11px;font-weight:700;letter-spacing:2px;margin-bottom:6px">KONEX CREATIVE</div>
            <div style="color:#0f172a;font-size:17px;font-weight:900;line-height:1.2;margin-bottom:8px">Seu próximo emprego<br>começa aqui</div>
            <div style="background:#0f172a;color:#fff;padding:6px 14px;border-radius:99px;font-size:11px;font-weight:700;display:inline-block">📄 Criar Currículo</div>
          </div>
          <div style="background:var(--card);padding:10px 14px;display:flex;justify-content:space-between;align-items:center">
            <div><div style="font-weight:700;font-size:13px">Light Clean</div><div style="font-size:11px;color:var(--muted)">Fundo claro</div></div>
            <button class="btn btn-primary" style="font-size:11px;padding:5px 10px"
              onclick="event.stopPropagation();aplicarLayout('light_clean')">✅ Aplicar</button>
          </div>
        </div>

        <div class="layout-opt" data-layout="corporate_blue" onclick="selectLayout(this)"
          style="border:2px solid var(--border);border-radius:16px;overflow:hidden;cursor:pointer">
          <div style="background:linear-gradient(135deg,#1e3a8a,#1d4ed8);padding:20px;min-height:140px">
            <div style="color:#93c5fd;font-size:11px;font-weight:700;letter-spacing:2px;margin-bottom:6px">KONEX CREATIVE</div>
            <div style="color:#fff;font-size:17px;font-weight:900;line-height:1.2;margin-bottom:8px">Currículo que abre<br>portas</div>
            <div style="background:#fbbf24;color:#000;padding:6px 14px;border-radius:99px;font-size:11px;font-weight:700;display:inline-block">💼 Começar Agora</div>
          </div>
          <div style="background:var(--card);padding:10px 14px;display:flex;justify-content:space-between;align-items:center">
            <div><div style="font-weight:700;font-size:13px">Corporate Blue</div><div style="font-size:11px;color:var(--muted)">Azul corporativo</div></div>
            <button class="btn btn-primary" style="font-size:11px;padding:5px 10px"
              onclick="event.stopPropagation();aplicarLayout('corporate_blue')">✅ Aplicar</button>
          </div>
        </div>

        <div class="layout-opt" data-layout="gradient_violet" onclick="selectLayout(this)"
          style="border:2px solid var(--border);border-radius:16px;overflow:hidden;cursor:pointer">
          <div style="background:linear-gradient(135deg,#4c1d95,#7c3aed,#db2777);padding:20px;min-height:140px">
            <div style="color:#e9d5ff;font-size:11px;font-weight:700;letter-spacing:2px;margin-bottom:6px">KONEX CREATIVE</div>
            <div style="color:#fff;font-size:17px;font-weight:900;line-height:1.2;margin-bottom:8px">Destaque-se<br>com estilo</div>
            <div style="background:rgba(255,255,255,.2);color:#fff;padding:6px 14px;border-radius:99px;font-size:11px;font-weight:700;display:inline-block;border:1px solid rgba(255,255,255,.3)">✨ Criar Grátis</div>
          </div>
          <div style="background:var(--card);padding:10px 14px;display:flex;justify-content:space-between;align-items:center">
            <div><div style="font-weight:700;font-size:13px">Gradient Violet</div><div style="font-size:11px;color:var(--muted)">Roxo gradiente</div></div>
            <button class="btn btn-primary" style="font-size:11px;padding:5px 10px"
              onclick="event.stopPropagation();aplicarLayout('gradient_violet')">✅ Aplicar</button>
          </div>
        </div>

        <div class="layout-opt" data-layout="emerald_fresh" onclick="selectLayout(this)"
          style="border:2px solid var(--border);border-radius:16px;overflow:hidden;cursor:pointer">
          <div style="background:linear-gradient(135deg,#064e3b,#059669);padding:20px;min-height:140px">
            <div style="color:#6ee7b7;font-size:11px;font-weight:700;letter-spacing:2px;margin-bottom:6px">KONEX CREATIVE</div>
            <div style="color:#fff;font-size:17px;font-weight:900;line-height:1.2;margin-bottom:8px">Currículo verde:<br>ecológico</div>
            <div style="background:#fbbf24;color:#064e3b;padding:6px 14px;border-radius:99px;font-size:11px;font-weight:700;display:inline-block">🌿 Começar</div>
          </div>
          <div style="background:var(--card);padding:10px 14px;display:flex;justify-content:space-between;align-items:center">
            <div><div style="font-weight:700;font-size:13px">Emerald Fresh</div><div style="font-size:11px;color:var(--muted)">Verde esmeralda</div></div>
            <button class="btn btn-primary" style="font-size:11px;padding:5px 10px"
              onclick="event.stopPropagation();aplicarLayout('emerald_fresh')">✅ Aplicar</button>
          </div>
        </div>

      </div>
    </div>

    <div id="tab-seguranca" class="tab-section">
      <h2 style="margin-bottom:20px;font-size:20px">🔒 Segurança & Anti-Fraude</h2>
      <div class="config-grid">
        <div class="config-card">
          <div class="config-card-title">🎁 Crédito Grátis no Cadastro</div>
          <div class="form-group"><label>Crédito Grátis Ativo?</label>
            <select id="sec_credito_ativo">
              <option value="1">✅ Sim — dar crédito no cadastro</option>
              <option value="0">❌ Não — sem crédito grátis</option>
            </select>
          </div>
          <div class="form-group"><label>Quantidade de Créditos Grátis</label><input type="number" id="sec_credito_qtd" value="1" min="0" max="10"></div>
          <div class="form-group"><label>Limite de contas por IP</label><input type="number" id="sec_limite_ip" value="3" min="1" max="20"></div>
          <button class="btn btn-primary" style="width:100%;padding:10px;margin-top:4px" onclick="salvarSeguranca()">💾 Salvar Segurança</button>
        </div>
        
        <div class="config-card" style="grid-column:1/-1">
          <div class="config-card-title" style="display:flex;justify-content:space-between;align-items:center">
            🕵️ IPs Suspeitos (múltiplas contas)
            <button class="btn btn-info" onclick="loadFraudeStats()">🔄 Atualizar</button>
          </div>
          <div id="fraudeStats" style="margin-bottom:12px">
            <div style="text-align:center;padding:20px;color:var(--muted)"><div class="spinner"></div></div>
          </div>
          <div class="table-card" style="margin:0">
            <table><thead><tr><th>IP</th><th>Total de Contas</th><th>Ação</th></tr></thead>
            <tbody id="tbFraudeIPs"></tbody></table>
          </div>
        </div>
      </div>
    </div>

  </div></div></div><div id="modalConfirm" class="modal-overlay">
  <div class="modal" onclick="event.stopPropagation()">
    <div class="modal-title">⚠️ Confirmação</div>
    <p class="confirm-msg" id="confirmMsg"></p>
    <div class="confirm-btns">
      <button class="btn btn-ghost" onclick="closeModal('modalConfirm')">Cancelar</button>
      <button class="btn btn-danger" id="confirmOkBtn">Confirmar</button>
    </div>
  </div>
</div>

<div id="modalCreditos" class="modal-overlay">
  <div class="modal" onclick="event.stopPropagation()">
    <div class="modal-title">🎫 Gerenciar Créditos</div>
    <input type="hidden" id="mcUID">
    <div class="form-row" style="margin-bottom:8px">
      <div class="form-group"><label>Usuário</label><input id="mcEmail" disabled style="opacity:.7"></div>
    </div>
    <div class="saldo-badge">💎 Saldo atual: <strong id="mcSaldoAtual">—</strong> crédito(s)</div>
    <div class="form-row">
      <div class="form-group"><label>Operação</label>
        <select id="mcAcao" onchange="atualizarCorCredito()">
          <option value="add">➕ Adicionar</option>
          <option value="remove">➖ Remover</option>
          <option value="set">🎯 Definir Saldo Exato</option>
        </select>
      </div>
    </div>
    <div class="form-group" style="margin-bottom:8px"><label>Quantidade</label>
      <div class="credit-input-wrap">
        <input type="number" id="mcQtd" value="1" min="0"
          onkeydown="trapCreditKey(event)" oninput="validarQtd(this)" onclick="this.select()">
      </div>
      <div class="credit-stepper">
        <button type="button" onclick="stepCredito(-10)">-10</button>
        <button type="button" onclick="stepCredito(-5)">-5</button>
        <button type="button" onclick="stepCredito(-1)">-1</button>
        <button type="button" onclick="stepCredito(1)">+1</button>
        <button type="button" onclick="stepCredito(5)">+5</button>
        <button type="button" onclick="stepCredito(10)">+10</button>
      </div>
    </div>
    <div id="mcPreview"
      style="background:rgba(99,102,241,.08);border:1px solid rgba(99,102,241,.2);border-radius:10px;padding:10px;margin:12px 0;font-size:13px;color:var(--txt2)">
      Novo saldo: <strong id="mcNovoSaldo" style="color:#a5b4fc;font-size:16px">—</strong>
    </div>
    <div class="form-group" style="margin-bottom:16px"><label>Motivo</label>
      <input id="mcDesc" value="Ajuste manual pelo admin"
        onkeydown="if(event.key==='Enter'){event.preventDefault();confirmarCreditos();}">
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <button type="button" class="btn btn-ghost" onclick="closeModal('modalCreditos')">Cancelar</button>
      <button type="button" class="btn btn-primary" id="btnConfirmarCredito" onclick="confirmarCreditos()">✅ Confirmar</button>
    </div>
  </div>
</div>

<div id="modalAddUser" class="modal-overlay">
  <div class="modal" onclick="event.stopPropagation()">
    <div class="modal-title">➕ Criar Novo Usuário</div>
    <div class="form-row">
      <div class="form-group"><label>Nome</label><input id="nuNome" placeholder="Nome"></div>
      <div class="form-group"><label>CPF</label><input id="nuCPF" placeholder="Apenas números" maxlength="11"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>E-mail</label><input id="nuEmail" placeholder="email@exemplo.com"></div>
      <div class="form-group"><label>Senha</label><input type="password" id="nuSenha" placeholder="Mín. 6 chars"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Créditos</label><input type="number" id="nuCred" value="0"></div>
      <div class="form-group"><label>Plano</label>
        <select id="nuPlano">
          <option value="avulso">Avulso</option>
          <option value="profissional">Profissional</option>
          <option value="agencia">Agência</option>
          <option value="admin">Admin</option>
        </select>
      </div>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <button type="button" class="btn btn-ghost" onclick="closeModal('modalAddUser')">Cancelar</button>
      <button type="button" class="btn btn-primary" onclick="criarUsuario()">✅ Criar Usuário</button>
    </div>
  </div>
</div>

<div id="modalEditUser" class="modal-overlay">
  <div class="modal" onclick="event.stopPropagation()">
    <div class="modal-title">✏️ Editar Usuário</div>
    <input type="hidden" id="euUID">
    <div class="form-row">
      <div class="form-group"><label>Nome</label><input id="euNome"></div>
      <div class="form-group"><label>CPF</label><input id="euCPF"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>E-mail</label><input id="euEmail"></div>
      <div class="form-group"><label>Nova Senha</label><input type="password" id="euSenha" placeholder="Deixe branco para manter"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Plano</label>
        <select id="euPlano">
          <option value="avulso">Avulso</option>
          <option value="profissional">Profissional</option>
          <option value="agencia">Agência</option>
          <option value="admin">Admin</option>
        </select>
      </div>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <button type="button" class="btn btn-ghost" onclick="closeModal('modalEditUser')">Cancelar</button>
      <button type="button" class="btn btn-primary" onclick="salvarEdicaoUsuario()">💾 Salvar</button>
    </div>
  </div>
</div>

<div id="modalCopyAccess" class="modal-overlay">
  <div class="modal" onclick="event.stopPropagation()">
    <div class="modal-title">📋 Enviar Acesso</div>
    <div class="form-group"><label>Senha Temp</label>
      <div style="display:flex;gap:10px">
        <input id="mcClienteSenha" oninput="updateCopyText()">
        <button type="button" class="btn btn-primary" onclick="gerarEAplicarSenha()">🔑 Gerar</button>
      </div>
    </div>
    <div class="form-group"><label>Mensagem</label><textarea id="mcCopyText" rows="10"></textarea></div>
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <button type="button" class="btn btn-ghost" onclick="closeModal('modalCopyAccess')">Fechar</button>
      <button type="button" class="btn btn-primary" onclick="copyAccessText()">📋 Copiar</button>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
'use strict';
// ── UTILS
const $ = id => document.getElementById(id);
const API = 'api.php';

// Token em sessionStorage (limpo ao fechar aba) em vez de localStorage
let adminToken = sessionStorage.getItem('konex_admin_token') || '';
let currentPage = 1;
let searchTimer = null;
let currentUsersList = [];
let currentUserForCopy = null;
let usersTotalCount = 0;

// ── CSRF TOKEN (gerado uma vez por sessão, enviado em cada request)
let csrfToken = sessionStorage.getItem('konex_csrf') || (() => {
  const t = crypto.randomUUID ? crypto.randomUUID() : Math.random().toString(36).slice(2);
  sessionStorage.setItem('konex_csrf', t);
  return t;
})();

// ── XSS: sanitização segura para innerHTML
function escHtml(s) {
  const d = document.createElement('div');
  d.textContent = String(s ?? '');
  return d.innerHTML;
}

// ── API wrapper com CSRF
async function api(acao, payload = {}) {
  try {
    const r = await fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
      body: JSON.stringify({ acao, token: adminToken, _csrf: csrfToken, ...payload })
    });
    if (!r.ok) throw new Error('HTTP ' + r.status);
    return r.json();
  } catch (e) {
    toast('Erro: ' + e.message, 'error');
    return { status: 'erro', msg: e.message };
  }
}

// ── TOAST
function toast(m, t = 'info') {
  const ic = { success: '✅', error: '❌', info: 'ℹ️', warn: '⚠️' };
  const c  = { success: '#10b981', error: '#ef4444', info: '#6366f1', warn: '#f59e0b' };
  const el = document.createElement('div');
  el.className = `toast-item ${t}`;
  el.innerHTML = `<span style="font-size:16px">${ic[t] || 'ℹ️'}</span><span>${escHtml(m)}</span>`;
  el.style.borderLeftColor = c[t] || c.info;
  $('toast').appendChild(el);
  setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 3500);
}

// ── CONFIRM MODAL (substitui confirm() nativo)
function confirmar(msg, cb) {
  $('confirmMsg').textContent = msg;
  const btn = $('confirmOkBtn');
  const novo = btn.cloneNode(true); // remove listeners antigos
  btn.parentNode.replaceChild(novo, btn);
  novo.addEventListener('click', () => { closeModal('modalConfirm'); cb(); });
  openModal('modalConfirm');
}

// ── MODAL OPEN/CLOSE
function openModal(id)  { $(id).classList.add('open'); }
function closeModal(id) { $(id).classList.remove('open'); }
document.addEventListener('mousedown', e => {
  document.querySelectorAll('.modal-overlay').forEach(o => {
    if (e.target === o) o.classList.remove('open');
  });
});

// ─────────────────────────────────────────────
// AUTH
// ─────────────────────────────────────────────
function togglePassword() {
  const inp = $('loginSenha'), eye = $('toggleEye');
  if (inp.type === 'password') { inp.type = 'text';     eye.textContent = '👁️‍🗨️'; }
  else                         { inp.type = 'password'; eye.textContent = '👁️'; }
}

window.onload = () => {
  // Apenas lembra email, NUNCA senha
  const savedEmail = localStorage.getItem('konex_saved_email');
  if (savedEmail) { $('loginSenha').placeholder = '🔒 Senha de Acesso'; }
  checkStoredToken();
};

async function doLogin() {
  const senha = $('loginSenha').value.trim();
  const err   = $('loginErr');
  if (!senha) { err.textContent = 'Digite a senha.'; return; }

  err.style.color = 'var(--muted)';
  err.textContent = 'Autenticando...';
  $('btnLogin').disabled = true;

  try {
    // Uma única tentativa — sem fallback que exponha a senha mestra
    const j = await fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
      body: JSON.stringify({ acao: 'admin_login', senha, _csrf: csrfToken })
    }).then(r => r.json());

    if (j.status === 'sucesso') {
      loginSuccess(j.token);
    } else {
      err.style.color = 'var(--danger)';
      err.textContent = j.msg || 'Senha incorreta.';
    }
  } catch (e) {
    err.style.color = 'var(--danger)';
    err.textContent = 'Erro de conexão.';
  } finally {
    $('btnLogin').disabled = false;
  }
}

function loginSuccess(token) {
  adminToken = token;
  sessionStorage.setItem('konex_admin_token', adminToken); // sessionStorage, não localStorage
  $('loginScreen').style.display = 'none';
  $('adminApp').classList.add('visible');
  loadDashboard();
}

async function checkStoredToken() {
  if (!adminToken) return;
  const j = await api('admin_check');
  if (j.status === 'sucesso') {
    $('loginScreen').style.display = 'none';
    $('adminApp').classList.add('visible');
    loadDashboard();
  } else {
    adminToken = '';
    sessionStorage.removeItem('konex_admin_token');
  }
}

function doLogout() {
  confirmar('Deseja sair do painel?', () => {
    adminToken = '';
    sessionStorage.clear();
    location.reload();
  });
}

// ─────────────────────────────────────────────
// TAB NAVIGATION
// ─────────────────────────────────────────────
const TAB_TITLES = {
  dashboard:     '📊 Dashboard',
  usuarios:      '👥 Usuários',
  pedidos:       '💳 Pedidos',
  transacoes:    '🧾 Transações',
  financeiro:    '📈 Financeiro',
  configuracoes: '⚙️ Configurações',
  loja_config:   '🛍️ Loja',
  seguranca:     '🔒 Segurança',
};

function showTab(name, el) {
  document.querySelectorAll('.tab-section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.sidebar-item').forEach(t => t.classList.remove('active'));
  const tabEl = $('tab-' + name);
  if (tabEl) tabEl.classList.add('active');
  if (el)    el.classList.add('active');
  const tt = $('topbarTitle');
  if (tt) tt.textContent = TAB_TITLES[name] || name;
  // Close sidebar on mobile after navigation
  closeSidebar();

  const loaders = {
    dashboard:     loadDashboard,
    usuarios:      () => loadUsuarios(1),
    pedidos:       () => loadPedidos(1),
    transacoes:    () => loadTransacoes(1),
    financeiro:    loadFinance,
    configuracoes: loadConfigs,
    loja_config:   loadLojaConfig,
    seguranca:     loadSegurancaConfig,
  };
  loaders[name]?.();
}

function toggleSidebar() {
  $('sidebar')?.classList.toggle('open');
  $('sidebarOverlay')?.classList.toggle('open');
}

function closeSidebar() {
  $('sidebar')?.classList.remove('open');
  $('sidebarOverlay')?.classList.remove('open');
}

// ─────────────────────────────────────────────
// DASHBOARD
// ─────────────────────────────────────────────
async function loadDashboard() {
  const j = await api('admin_stats');
  if (j.status !== 'sucesso') return;
  $('sTotal').textContent    = j.total_usuarios;
  $('sAtivos').textContent   = j.usuarios_ativos;
  $('sDownloads').textContent= j.total_downloads;
  $('sCred').textContent     = j.cred_vendidos;
  $('sPedidos').textContent  = j.total_pedidos;
  $('sReceita').textContent  = 'R$ ' + parseFloat(j.receita_total).toFixed(2);

  const tb = $('tbUltimosPedidos');
  tb.innerHTML = '';
  (j.ultimos_pedidos || []).forEach(p => {
    const sc = { aprovado: 'b-green', pendente: 'b-amber', cancelado: 'b-red', reembolsado: 'b-cyan' }[p.status] || 'b-amber';
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>#${escHtml(p.id)}</td>
      <td style="color:var(--txt2)">${escHtml(p.email)}</td>
      <td><span class="b b-indigo">${escHtml(p.plano)}</span></td>
      <td style="font-weight:700">${escHtml(p.creditos)}</td>
      <td style="color:#34d399;font-weight:700">R$ ${parseFloat(p.valor).toFixed(2)}</td>
      <td><span class="b ${sc}">${escHtml(p.status)}</span></td>
      <td style="color:var(--muted)">${new Date(p.created_at).toLocaleString('pt-BR')}</td>`;
    tb.appendChild(tr);
  });
  if (!tb.children.length)
    tb.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--muted);padding:30px">Nenhum pedido ainda.</td></tr>';
}

async function limparHistoricoPedidos() {
  const j = await api('admin_limpar_pedidos');
  if (j.status === 'sucesso') { toast(j.msg, 'success'); loadDashboard(); }
  else toast('Erro.', 'error');
}

// ─────────────────────────────────────────────
// USUÁRIOS
// ─────────────────────────────────────────────
async function loadUsuarios(page = 1) {
  currentPage = page;
  const busca = ($('searchUser')?.value || '').trim();
  const tb = $('tbUsuarios');
  tb.innerHTML = '<tr class="loading-row"><td colspan="9"><div class="spinner"></div> Carregando...</td></tr>';

  const j = await api('admin_usuarios', { page, busca, limit: 50 });
  if (j.status !== 'sucesso') {
    tb.innerHTML = `<tr><td colspan="9">❌ ${escHtml(j.msg)}</td></tr>`;
    return;
  }

  currentUsersList  = j.usuarios || [];
  usersTotalCount   = j.total || currentUsersList.length;
  const bar = $('userCountBar');
  if (bar) {
    bar.style.display = usersTotalCount > 0 ? 'flex' : 'none';
    $('userCountShowing').textContent = currentUsersList.length;
    $('userCountTotal').textContent   = usersTotalCount;
  }

  tb.innerHTML = '';
  if (!currentUsersList.length) {
    tb.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:40px">Nenhum usuário.</td></tr>';
    renderPagination('paginacao', 1, 1, loadUsuarios);
    return;
  }

  currentUsersList.forEach(u => {
    const sc   = u.ativo ? 'b-green' : 'b-red';
    const st   = u.ativo ? 'Ativo'   : 'Inativo';
    const pc   = { avulso:'b-amber', profissional:'b-indigo', agencia:'b-cyan', admin:'b-green' }[u.plano] || 'b-amber';
    const credC = u.creditos > 0 ? '#a5b4fc' : '#f87171';
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>#${escHtml(u.id)}</td>
      <td style="font-weight:600">${escHtml(u.email)}</td>
      <td>${escHtml(u.nome || '—')}</td>
      <td>${u.cpf ? '***.' + String(u.cpf).slice(3,6) + '.***-**' : '—'}</td>
      <td style="font-weight:900;color:${credC}">${escHtml(u.creditos)}</td>
      <td><span class="b ${pc}">${escHtml(u.plano)}</span></td>
      <td><span class="b ${sc}">${st}</span></td>
      <td>${new Date(u.created_at).toLocaleDateString('pt-BR')}</td>
      <td>
        <div style="display:flex;gap:5px">
          <button class="btn btn-info"    data-id="${u.id}" data-action="copy">📋</button>
          <button class="btn btn-primary" data-id="${u.id}" data-action="cred">🎫</button>
          <button class="btn btn-warn"    data-id="${u.id}" data-action="edit">✏️</button>
          <button class="btn ${u.ativo?'btn-ghost':'btn-success'}" data-id="${u.id}" data-action="toggle">${u.ativo?'🚫':'✅'}</button>
          <button class="btn btn-danger"  data-id="${u.id}" data-action="del">🗑️</button>
        </div>
      </td>`;
    tb.appendChild(tr);
  });

  // Event delegation — evita onclick inline com IDs
  tb.onclick = e => {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    const uid = parseInt(btn.dataset.id);
    const act = btn.dataset.action;
    if (act === 'copy')   openModalCopy(uid);
    if (act === 'cred')   openModalCreditos(uid);
    if (act === 'edit')   openModalEditUser(uid);
    if (act === 'toggle') confirmar('Alterar status deste usuário?', () => toggleUsuario(uid));
    if (act === 'del')    confirmar('⚠️ Excluir este usuário irreversivelmente?', () => excluirUsuario(uid));
  };

  renderPagination('paginacao', page, Math.ceil(usersTotalCount / 50), loadUsuarios);
}

function debounceSearch() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => loadUsuarios(1), 450);
}

async function toggleUsuario(uid) {
  const j = await api('admin_toggle_usuario', { usuario_id: uid });
  if (j.status === 'sucesso') { toast('Status alterado!', 'success'); loadUsuarios(currentPage); }
  else toast(j.msg, 'error');
}

async function excluirUsuario(uid) {
  const j = await api('admin_delete_usuario', { usuario_id: uid });
  if (j.status === 'sucesso') { toast('Excluído!', 'success'); loadUsuarios(currentPage); }
  else toast(j.msg, 'error');
}

// ─────────────────────────────────────────────
// MODAL: ADD USER
// ─────────────────────────────────────────────
function openModalAddUser() {
  $('nuNome').value = ''; $('nuCPF').value = '';
  $('nuEmail').value = ''; $('nuSenha').value = '';
  $('nuCred').value = '0'; $('nuPlano').value = 'avulso';
  openModal('modalAddUser');
}

async function criarUsuario() {
  const nome  = $('nuNome').value.trim();
  const cpf   = $('nuCPF').value.replace(/[^0-9]/g, '').slice(0, 11);
  const email = $('nuEmail').value.trim();
  const senha = $('nuSenha').value;
  const cred  = parseInt($('nuCred').value) || 0;
  const plano = $('nuPlano').value;

  if (!email || senha.length < 6) { toast('E-mail e senha (mín. 6) são obrigatórios.', 'error'); return; }

  const j = await api('admin_add_usuario', { email, senha, nome, cpf, plano, creditos: cred });
  if (j.status !== 'sucesso') { toast(j.msg, 'error'); return; }
  toast('Criado com sucesso!', 'success');
  closeModal('modalAddUser');
  $('searchUser').value = '';
  loadUsuarios(1);
}

// ─────────────────────────────────────────────
// MODAL: EDIT USER
// ─────────────────────────────────────────────
function openModalEditUser(uid) {
  const u = currentUsersList.find(x => x.id === uid);
  if (!u) return;
  $('euUID').value  = u.id;
  $('euNome').value = u.nome  || '';
  $('euCPF').value  = u.cpf   || '';
  $('euEmail').value= u.email;
  $('euPlano').value= u.plano;
  $('euSenha').value= '';
  openModal('modalEditUser');
}

async function salvarEdicaoUsuario() {
  const payload = {
    usuario_id: $('euUID').value,
    nome:   $('euNome').value.trim(),
    email:  $('euEmail').value.trim(),
    cpf:    $('euCPF').value.replace(/[^0-9]/g, '').slice(0, 11),
    plano:  $('euPlano').value,
    senha:  $('euSenha').value
  };
  if (!payload.email) { toast('E-mail obrigatório.', 'error'); return; }
  const j = await api('admin_edit_usuario', payload);
  if (j.status === 'sucesso') { toast(j.msg, 'success'); closeModal('modalEditUser'); loadUsuarios(currentPage); }
  else toast(j.msg, 'error');
}

// ─────────────────────────────────────────────
// MODAL: CRÉDITOS
// ─────────────────────────────────────────────
function openModalCreditos(uid) {
  const u = currentUsersList.find(x => x.id === uid);
  if (!u) return;
  $('mcUID').value  = uid;
  $('mcEmail').value= u.email;
  $('mcAcao').value = 'add';
  $('mcQtd').value  = 1;
  $('mcDesc').value = 'Ajuste manual';
  $('mcSaldoAtual').textContent = u.creditos;
  $('mcQtd').dataset.saldoAtual = u.creditos;
  atualizarPreviewCredito(u.creditos);
  openModal('modalCreditos');
}

function trapCreditKey(e) {
  if (e.key === 'Enter') { e.preventDefault(); confirmarCreditos(); }
}

function validarQtd(el) {
  let v = parseInt(el.value);
  if (isNaN(v) || v < 0) v = 0;
  if (v > 99999) v = 99999;
  el.value = v;
  atualizarPreviewCredito(parseInt(el.dataset.saldoAtual || 0));
}

function atualizarPreviewCredito(s) {
  const a = $('mcAcao').value;
  const q = parseInt($('mcQtd').value) || 0;
  const n = a === 'add' ? s + q : a === 'remove' ? Math.max(0, s - q) : a === 'set' ? q : s;
  const el = $('mcNovoSaldo');
  if (el) { el.textContent = n; el.style.color = n > s ? '#34d399' : n < s ? '#f87171' : '#a5b4fc'; }
}

function atualizarCorCredito() {
  atualizarPreviewCredito(parseInt($('mcQtd').dataset.saldoAtual || 0));
}

function stepCredito(d) {
  const i = $('mcQtd');
  i.value = Math.max(0, Math.min(99999, (parseInt(i.value) || 0) + d));
  i.focus();
  atualizarPreviewCredito(parseInt(i.dataset.saldoAtual || 0));
}

async function confirmarCreditos() {
  const uid = parseInt($('mcUID').value);
  const a   = $('mcAcao').value;
  const q   = parseInt($('mcQtd').value);
  const d   = $('mcDesc').value.trim();
  if (!uid || isNaN(q) || q < 0) { toast('Dados inválidos.', 'error'); return; }

  let payload = { usuario_id: uid, quantidade: a === 'remove' ? -q : q, descricao: d };
  if (a === 'set') { payload.quantidade = q; payload.operacao = 'set'; }

  const btn = $('btnConfirmarCredito');
  btn.disabled    = true;
  btn.textContent = '⏳ Aguarde...';

  const j = await api('admin_add_creditos', payload);
  btn.disabled    = false;
  btn.textContent = '✅ Confirmar';

  if (j.status === 'sucesso') { toast('✅ ' + j.msg, 'success'); closeModal('modalCreditos'); loadUsuarios(currentPage); }
  else toast(j.msg, 'error');
}

// ─────────────────────────────────────────────
// MODAL: COPY ACCESS
// ─────────────────────────────────────────────
function openModalCopy(uid) {
  currentUserForCopy = currentUsersList.find(x => x.id === uid);
  if (!currentUserForCopy) return;
  $('mcClienteSenha').value = '';
  updateCopyText();
  openModal('modalCopyAccess');
}

function updateCopyText() {
  if (!currentUserForCopy) return;
  const s = $('mcClienteSenha').value || '[SENHA DO CADASTRO]';
  const n = currentUserForCopy.nome ? currentUserForCopy.nome.split(' ')[0] : 'Cliente';
  $('mcCopyText').value =
    `Olá, ${n}! 🎉\nAcesso liberado:\n` +
    `🌐 https://iubsites.com/konex/creative/\n` +
    `👤 Login: ${currentUserForCopy.email}\n` +
    `🔑 Senha: ${s}\n` +
    `💎 Créditos: ${currentUserForCopy.creditos}\n\n` +
    `Use nossa IA para criar textos! Sucesso! 🚀`;
}

async function gerarEAplicarSenha() {
  if (!currentUserForCopy) return;
  const n = 'konex' + Math.floor(1000 + Math.random() * 9000);
  const j = await api('admin_force_password', { usuario_id: currentUserForCopy.id, senha: n });
  if (j.status === 'sucesso') { $('mcClienteSenha').value = n; updateCopyText(); toast('Gerada!', 'success'); }
  else toast('Erro.', 'error');
}

function copyAccessText() {
  if (!navigator.clipboard) { toast('HTTPS necessário para copiar.', 'warn'); return; }
  navigator.clipboard.writeText($('mcCopyText').value)
    .then(() => { toast('Copiado!', 'success'); closeModal('modalCopyAccess'); })
    .catch(() => toast('Falha ao copiar.', 'error'));
}

// ─────────────────────────────────────────────
// PEDIDOS
// ─────────────────────────────────────────────
async function loadPedidos(page = 1) {
  const payload = {
    busca:  $('p_busca')?.value  || '',
    status: $('p_status')?.value || '',
    plano:  $('p_plano')?.value  || '',
    de:     $('p_de')?.value     || '',
    ate:    $('p_ate')?.value    || '',
    page, limit: 50
  };
  const j = await api('admin_pedidos', payload);
  if (j.status !== 'sucesso') { toast('Erro ao carregar pedidos.', 'error'); return; }

  const tb = $('tbPedidos');
  tb.innerHTML = '';
  if ($('p_total')) $('p_total').textContent = `Total: ${j.total || 0}`;

  (j.pedidos || []).forEach(p => {
    const sc = { aprovado:'b-green', pendente:'b-amber', cancelado:'b-red' }[p.status] || 'b-cyan';
    const tr = document.createElement('tr');
    const aprovarBtn = p.status === 'pendente'
      ? `<button class="btn btn-success" style="padding:4px 8px;font-size:10px" data-id="${p.id}" data-action="aprovar">✅ Aprovar</button>`
      : '';
    tr.innerHTML = `
      <td>#${escHtml(p.id)}</td>
      <td>${escHtml(p.email)}</td>
      <td><span class="b b-indigo">${escHtml(p.plano)}</span></td>
      <td>${escHtml(p.creditos)}</td>
      <td>R$ ${parseFloat(p.valor).toFixed(2)}</td>
      <td><span class="b ${sc}">${escHtml(p.status)}</span></td>
      <td style="font-size:11px;color:var(--muted)">${escHtml(p.gateway_id || '—')}</td>
      <td style="color:var(--muted)">${new Date(p.created_at).toLocaleString('pt-BR')}</td>
      <td><div style="display:flex;gap:4px">
        ${aprovarBtn}
        <button class="btn btn-danger" style="padding:4px 8px;font-size:10px" data-id="${p.id}" data-action="del">🗑</button>
      </div></td>`;
    tb.appendChild(tr);
  });

  tb.onclick = e => {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    const id  = parseInt(btn.dataset.id);
    const act = btn.dataset.action;
    if (act === 'aprovar') confirmar('Aprovar este pedido e liberar créditos?', () => aprovarPedido(id));
    if (act === 'del')     confirmar('⚠️ Excluir este pedido?', () => excluirPedido(id));
  };

  renderPagination('paginacaoPedidos', page, Math.ceil((j.total || 0) / 50), loadPedidos);
}

function resetPedidosFiltros() {
  ['p_busca','p_status','p_plano','p_de','p_ate'].forEach(id => { if ($(id)) $(id).value = ''; });
  loadPedidos(1);
}

async function aprovarPedido(id) {
  const j = await api('admin_aprovar_pedido', { pedido_id: id });
  if (j.status === 'sucesso') { toast(j.msg, 'success'); loadPedidos(currentPage); }
  else toast(j.msg || 'Erro.', 'error');
}

async function excluirPedido(id) {
  const j = await api('admin_delete_pedido_individual', { pedido_id: id });
  if (j.status === 'sucesso') { toast('Removido!', 'success'); loadPedidos(currentPage); loadDashboard(); }
  else toast(j.msg, 'error');
}

// ─────────────────────────────────────────────
// TRANSAÇÕES
// ─────────────────────────────────────────────
async function loadTransacoes(page = 1) {
  const payload = {
    busca: $('t_busca')?.value || '',
    tipo:  $('t_tipo')?.value  || '',
    de:    $('t_de')?.value    || '',
    ate:   $('t_ate')?.value   || '',
    page, limit: 50
  };
  const j = await api('admin_transacoes', payload);
  if (j.status !== 'sucesso') return;

  const tb = $('tbTransacoes');
  tb.innerHTML = '';
  if ($('t_total')) $('t_total').textContent = `Total: ${j.total || 0}`;

  (j.transacoes || []).forEach(t => {
    const tc = { compra:'b-green', consumo:'b-amber', estorno:'b-red', manual:'b-indigo' }[t.tipo] || 'b-cyan';
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>#${escHtml(t.id)}</td>
      <td>${escHtml(t.email)}</td>
      <td><span class="b ${tc}">${escHtml(t.tipo)}</span></td>
      <td style="font-weight:900;color:${t.quantidade < 0 ? '#f87171' : '#34d399'}">${escHtml(t.quantidade)}</td>
      <td>${escHtml(t.descricao)}</td>
      <td style="color:var(--muted)">${escHtml(t.referencia || 'null')}</td>
      <td style="color:var(--muted)">${escHtml(t.ip)}</td>
      <td style="color:var(--muted)">${new Date(t.created_at).toLocaleString('pt-BR')}</td>
      <td><button class="btn btn-danger" style="padding:4px 8px;font-size:10px" data-id="${t.id}" data-action="del">🗑</button></td>`;
    tb.appendChild(tr);
  });

  tb.onclick = e => {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    const id = parseInt(btn.dataset.id);
    confirmar('⚠️ Excluir esta transação?', () => excluirTransacao(id));
  };

  renderPagination('paginacaoTransacoes', page, Math.ceil((j.total || 0) / 50), loadTransacoes);
}

function resetTransacoesFiltros() {
  ['t_busca','t_tipo','t_de','t_ate'].forEach(id => { if ($(id)) $(id).value = ''; });
  loadTransacoes(1);
}

async function limparTransacoes() {
  const j = await api('admin_limpar_transacoes');
  if (j.status === 'sucesso') { toast(j.msg, 'success'); loadTransacoes(currentPage); }
  else toast('Erro.', 'error');
}

async function excluirTransacao(id) {
  const j = await api('admin_delete_transacao_individual', { transacao_id: id });
  if (j.status === 'sucesso') { toast('Removida!', 'success'); loadTransacoes(currentPage); }
  else toast(j.msg, 'error');
}

// ─────────────────────────────────────────────
// FINANCEIRO
// ─────────────────────────────────────────────
async function loadFinance() {
  const de  = $('f_de')?.value  || '';
  const ate = $('f_ate')?.value || '';
  const j   = await api('admin_financeiro', { de, ate });
  if (j.status !== 'sucesso') return;

  const fmt = n => 'R$ ' + parseFloat(n || 0).toFixed(2).replace('.', ',');
  $('f_receita').textContent  = fmt(j.receita);
  $('f_pedidos').textContent  = j.pedidos;
  $('f_ticket').textContent   = fmt(j.ticket);
  $('f_vendidos').textContent = j.vendidos;
  $('f_pdfs').textContent     = j.pdfs;
  $('f_saldo').textContent    = j.saldo;

  const serie  = j.serie || [];
  const labels = serie.map(x => x.dia || x.date || '');
  const values = serie.map(x => parseFloat(x.receita || 0));
  drawLineChart('chartReceita', labels, values);
}

function resetFinancePeriodo() {
  $('f_de').value = ''; $('f_ate').value = '';
  loadFinance();
}

// ─────────────────────────────────────────────
// CONFIGURAÇÕES
// ─────────────────────────────────────────────
async function loadConfigs() {
  const j = await api('admin_get_configs');
  if (j.status !== 'sucesso') return;
  const c = j.configs;

  // Constrói o HTML de forma segura (valores via .value após criação)
  $('configGrid').innerHTML = `
    <div class="config-card" style="grid-column:1/-1">
      <div class="config-card-title">🌐 Configurações do Site</div>
      <div class="form-row">
        <div class="form-group"><label>🌐 URL do Site</label><input id="cfg_site_url" placeholder="https://seu-dominio.com/pasta"></div>
        <div class="form-group"><label>🏷️ Nome do Site</label><input id="cfg_site_nome"></div>
        <div class="form-group"><label>📞 WhatsApp Suporte</label><input id="cfg_whatsapp_suporte" placeholder="5564999999999"></div>
      </div>
    </div>
    <div class="config-card" style="grid-column:1/-1;border-color:rgba(245,158,11,.4)">
      <div class="config-card-title" style="color:var(--warn)">💳 Gateway de Pagamento Ativo</div>
      <div style="margin-bottom:20px;display:flex;gap:14px;flex-wrap:wrap">
        <label style="flex:1;min-width:200px;cursor:pointer">
          <input type="radio" name="rGateway" value="mercadopago" onchange="trocarGateway('mercadopago')" style="accent-color:var(--primary);margin-right:6px">
          <span style="font-weight:700">💳 Mercado Pago</span>
        </label>
        <label style="flex:1;min-width:200px;cursor:pointer">
          <input type="radio" name="rGateway" value="asaas" onchange="trocarGateway('asaas')" style="accent-color:var(--warn);margin-right:6px">
          <span style="font-weight:700">🦋 ASAAS</span>
        </label>
        <input type="hidden" id="cfg_gateway_ativo">
      </div>
      <div id="wrapMercadoPago">
        <div style="background:rgba(99,102,241,.06);border:1px solid rgba(99,102,241,.2);border-radius:12px;padding:14px;margin-bottom:14px">
          <code style="font-size:11px;color:#a5b4fc" id="webhookMPUrl"></code>
        </div>
        <div class="form-group"><label>🔑 Access Token</label><input type="password" id="cfg_mp_access_token"></div>
      </div>
      <div id="wrapAsaas" style="display:none">
        <div style="background:rgba(245,158,11,.06);border:1px solid rgba(245,158,11,.2);border-radius:12px;padding:14px;margin-bottom:14px">
          <code style="font-size:11px;color:#fbbf24" id="webhookAsaasUrl"></code>
        </div>
        <div class="form-row">
          <div class="form-group"><label>🔑 API Key</label><input type="password" id="cfg_asaas_api_key"></div>
          <div class="form-group"><label>🌍 Ambiente</label>
            <select id="cfg_asaas_ambiente">
              <option value="sandbox">Sandbox</option>
              <option value="producao">Produção</option>
            </select>
          </div>
        </div>
      </div>
    </div>
    <div class="config-card">
      <div class="config-card-title">💰 Plano Básico</div>
      <div class="form-row">
        <div class="form-group"><label>Nome</label><input id="cfg_nome_plano_basico"></div>
        <div class="form-group"><label>Preço (R$)</label><input id="cfg_valor_basico" type="number" step="0.01"></div>
      </div>
      <div class="form-group"><label>Créditos</label><input id="cfg_creditos_basico" type="number"></div>
      <div class="form-group"><label>Descrição</label><input id="cfg_desc_plano_basico"></div>
    </div>
    <div class="config-card">
      <div class="config-card-title">🏆 Plano Profissional</div>
      <div class="form-row">
        <div class="form-group"><label>Nome</label><input id="cfg_nome_plano_profissional"></div>
        <div class="form-group"><label>Preço (R$)</label><input id="cfg_valor_profissional" type="number" step="0.01"></div>
      </div>
      <div class="form-group"><label>Créditos</label><input id="cfg_creditos_profissional" type="number"></div>
      <div class="form-group"><label>Descrição</label><input id="cfg_desc_plano_profissional"></div>
    </div>
    <div class="config-card">
      <div class="config-card-title">🏢 Plano Agência</div>
      <div class="form-row">
        <div class="form-group"><label>Nome</label><input id="cfg_nome_plano_agencia"></div>
        <div class="form-group"><label>Preço (R$)</label><input id="cfg_valor_agencia" type="number" step="0.01"></div>
      </div>
      <div class="form-group"><label>Créditos</label><input id="cfg_creditos_agencia" type="number"></div>
      <div class="form-group"><label>Descrição</label><input id="cfg_desc_plano_agencia"></div>
    </div>
    <div class="config-card" style="grid-column:1/-1">
      <div class="config-card-title">🧠 Inteligência Artificial (LLM)</div>
      <div class="form-row">
        <div class="form-group"><label>IA Ativada?</label>
          <select id="cfg_llm_enabled">
            <option value="0">❌ Desativada</option>
            <option value="1">✅ Ativada</option>
          </select>
        </div>
        <div class="form-group"><label>Modelo</label><input id="cfg_llm_model"></div>
      </div>
      <div class="form-group" style="margin-bottom:14px"><label>Endpoint da API</label><input id="cfg_llm_endpoint"></div>
      <div class="form-group"><label>API Key (chave secreta)</label><input type="password" id="cfg_llm_api_key"></div>
    </div>`;

  // Atribui valores via .value (nunca via innerHTML — evita XSS)
  const set = (id, val, fb = '') => { const el = $(id); if (el) el.value = val || fb; };
  set('cfg_site_url',              c.site_url,              'https://iubsites.com/konex/creative');
  set('cfg_site_nome',             c.site_nome,             'KONEX CREATIVE');
  set('cfg_whatsapp_suporte',      c.whatsapp_suporte);
  set('cfg_gateway_ativo',         c.gateway_ativo,         'mercadopago');
  set('cfg_mp_access_token',       c.mp_access_token);
  set('cfg_asaas_api_key',         c.asaas_api_key);
  set('cfg_asaas_ambiente',        c.asaas_ambiente,        'sandbox');
  set('cfg_valor_basico',          c.valor_basico,          '9.90');
  set('cfg_valor_profissional',    c.valor_profissional,    '19.90');
  set('cfg_valor_agencia',         c.valor_agencia,         '39.90');
  set('cfg_creditos_basico',       c.creditos_basico,       '1');
  set('cfg_creditos_profissional', c.creditos_profissional, '3');
  set('cfg_creditos_agencia',      c.creditos_agencia,      '10');
  set('cfg_nome_plano_basico',     c.nome_plano_basico,     'Básico');
  set('cfg_nome_plano_profissional',c.nome_plano_profissional,'Profissional');
  set('cfg_nome_plano_agencia',    c.nome_plano_agencia,    'Agência');
  set('cfg_desc_plano_basico',     c.desc_plano_basico);
  set('cfg_desc_plano_profissional',c.desc_plano_profissional);
  set('cfg_desc_plano_agencia',    c.desc_plano_agencia);
  set('cfg_llm_enabled',           c.llm_enabled,           '0');
  set('cfg_llm_model',             c.llm_model,             'gpt-4o-mini');
  set('cfg_llm_endpoint',          c.llm_endpoint,          'https://api.openai.com/v1/chat/completions');
  set('cfg_llm_api_key',           c.llm_api_key);

  // URLs de webhook — via textContent (seguro)
  const base = c.site_url || 'https://iubsites.com/konex/creative';
  const wmp  = $('webhookMPUrl');
  const was  = $('webhookAsaasUrl');
  if (wmp) wmp.textContent  = base + '/api.php?acao=webhook_mp';
  if (was) was.textContent  = base + '/api.php?acao=webhook_asaas';

  // Radio gateway
  const gw = c.gateway_ativo || 'mercadopago';
  document.querySelectorAll('input[name="rGateway"]').forEach(r => { r.checked = r.value === gw; });
  trocarGateway(gw);
}

async function salvarConfigs() {
  const ids = [
    'site_url','site_nome','gateway_ativo','mp_access_token','asaas_api_key','asaas_ambiente',
    'valor_basico','valor_profissional','valor_agencia',
    'creditos_basico','creditos_profissional','creditos_agencia',
    'nome_plano_basico','nome_plano_profissional','nome_plano_agencia',
    'desc_plano_basico','desc_plano_profissional','desc_plano_agencia',
    'whatsapp_suporte','llm_enabled','llm_model','llm_endpoint','llm_api_key'
  ];
  const configs = {};
  ids.forEach(k => { const el = $('cfg_' + k); if (el) configs[k] = el.value; });
  const j = await api('admin_save_configs', { configs });
  toast(j.msg, j.status === 'sucesso' ? 'success' : 'error');
}

function trocarGateway(gw) {
  const cgw = $('cfg_gateway_ativo');
  if (cgw) cgw.value = gw;
  const mp  = $('wrapMercadoPago');
  const as  = $('wrapAsaas');
  if (mp) mp.style.display = gw === 'mercadopago' ? 'block' : 'none';
  if (as) as.style.display = gw === 'asaas'       ? 'block' : 'none';
}

// ─────────────────────────────────────────────
// LOJA CONFIG
// ─────────────────────────────────────────────
function previewLojaVideo() {
  const url = $('loja_video_url').value.trim();
  if (!url) { $('lojaVideoPreview').style.display = 'none'; return; }
  const ytMatch    = url.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([a-zA-Z0-9_-]{11})/);
  const vimeoMatch = url.match(/vimeo\.com\/(\d+)/);
  let embedUrl = '';
  if (ytMatch)    embedUrl = `https://www.youtube.com/embed/${ytMatch[1]}?rel=0&modestbranding=1`;
  else if (vimeoMatch) embedUrl = `https://player.vimeo.com/video/${vimeoMatch[1]}`;
  if (embedUrl) { $('lojaVideoIframe').src = embedUrl; $('lojaVideoPreview').style.display = 'block'; }
}

function selectLayout(el) {
  document.querySelectorAll('.layout-opt').forEach(o => o.style.borderColor = 'var(--border)');
  el.style.borderColor = 'var(--primary)';
  const layout = el.dataset.layout;
  if ($('loja_layout_value')) $('loja_layout_value').value = layout;
  const names = { dark_modern:'Dark Modern', light_clean:'Light Clean', corporate_blue:'Corporate Blue', gradient_violet:'Gradient Violet', emerald_fresh:'Emerald Fresh' };
  if ($('selectedLayoutName')) $('selectedLayoutName').textContent = names[layout] || layout;
}

function aplicarLayout(layout) {
  document.querySelectorAll('.layout-opt').forEach(o => o.style.borderColor = 'var(--border)');
  const el = document.querySelector(`.layout-opt[data-layout="${layout}"]`);
  if (el) el.style.borderColor = 'var(--primary)';
  if ($('loja_layout_value')) $('loja_layout_value').value = layout;
  const names = { dark_modern:'Dark Modern', light_clean:'Light Clean', corporate_blue:'Corporate Blue', gradient_violet:'Gradient Violet', emerald_fresh:'Emerald Fresh' };
  if ($('selectedLayoutName')) $('selectedLayoutName').textContent = names[layout] || layout;
  api('admin_save_configs', { configs: { loja_layout: layout } })
    .then(j => toast(j.status === 'sucesso' ? '✅ Layout aplicado!' : j.msg, j.status === 'sucesso' ? 'success' : 'error'));
}

async function salvarLojaConfig() {
  const configs = {
    loja_video_url:        $('loja_video_url')?.value        || '',
    loja_video_titulo:     $('loja_video_titulo')?.value     || '',
    loja_layout:           $('loja_layout_value')?.value     || 'dark_modern',
    loja_curriculos_criados: $('loja_curriculos_criados')?.value || '4800',
    loja_contador_texto:   $('loja_contador_texto')?.value   || 'currículos criados este mês',
    loja_rating:           $('loja_rating')?.value           || '4.9'
  };
  const j = await api('admin_save_configs', { configs });
  toast(j.status === 'sucesso' ? '✅ Loja salva!' : j.msg, j.status === 'sucesso' ? 'success' : 'error');
}

async function loadLojaConfig() {
  const j = await api('admin_get_configs');
  if (j.status !== 'sucesso') return;
  const c = j.configs;
  const set = (id, val) => { const el = $(id); if (el) el.value = val || ''; };
  set('loja_video_url',        c.loja_video_url);
  set('loja_video_titulo',     c.loja_video_titulo);
  set('loja_curriculos_criados', c.loja_curriculos_criados || '4800');
  set('loja_contador_texto',   c.loja_contador_texto || 'currículos criados este mês');
  set('loja_rating',           c.loja_rating || '4.9');
  if (c.loja_video_url) previewLojaVideo();
  if (c.loja_layout) {
    const el = document.querySelector(`.layout-opt[data-layout="${c.loja_layout}"]`);
    if (el) selectLayout(el);
  }
}

// ─────────────────────────────────────────────
// MODELOS
// ─────────────────────────────────────────────
const MODELOS_DATA = [
  { id:1, nome:'ANA CAROLINA', cargo:'Gestora', empresa:'Google', periodo:'2022–Atual',
    empresa2:'Nubank', periodo2:'2020–2022', formacao:'Publicidade', cidade:'São Paulo, SP',
    skills:['SEO','Ads'], cor:'#6366f1', estilo:'Dark Moderno', emoji:'👩',
    foto:'https://i.pravatar.cc/60?img=47' },
  { id:2, nome:'CARLOS LIMA', cargo:'Desenvolvedor', empresa:'iFood', periodo:'2021–Atual',
    empresa2:'Totvs', periodo2:'2019–2021', formacao:'Computação', cidade:'São Paulo',
    skills:['React','AWS'], cor:'#1e40af', estilo:'Clássico', emoji:'👨',
    foto:'https://i.pravatar.cc/60?img=12' }
];

function renderModelosAdmin() {
  const grid = $('modelosGrid');
  if (!grid) return;
  grid.innerHTML = '';
  MODELOS_DATA.forEach(m => {
    const card = document.createElement('div');
    card.style.cssText = 'background:var(--panel);border:1px solid var(--border);border-radius:14px;overflow:hidden';
    card.innerHTML = `
      <div style="background:${escHtml(m.cor)};padding:14px;display:flex;align-items:center;gap:10px">
        <img src="${escHtml(m.foto)}" alt="${escHtml(m.nome)}"
          style="width:44px;height:44px;border-radius:50%;border:2px solid rgba(255,255,255,.5);object-fit:cover"
          onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
        <div style="width:44px;height:44px;border-radius:50%;background:rgba(255,255,255,.2);display:none;align-items:center;justify-content:center;font-size:20px;flex-shrink:0">${escHtml(m.emoji)}</div>
        <div style="color:#fff;flex:1;min-width:0">
          <div style="font-weight:900;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${escHtml(m.nome)}</div>
          <div style="font-size:10px;opacity:.85;margin-top:1px">${escHtml(m.cargo)}</div>
        </div>
      </div>
      <div style="padding:12px">
        <div style="font-size:10px;color:var(--muted);margin-bottom:6px;font-weight:700">EXPERIÊNCIA</div>
        <div style="font-size:11px;margin-bottom:2px"><strong>${escHtml(m.empresa)}</strong> — ${escHtml(m.periodo)}</div>
        <div style="font-size:11px;color:var(--muted);margin-bottom:8px">${escHtml(m.empresa2)} — ${escHtml(m.periodo2)}</div>
        <div style="font-size:10px;color:var(--muted);margin-bottom:4px;font-weight:700">FORMAÇÃO</div>
        <div style="font-size:11px;margin-bottom:8px">${escHtml(m.formacao)}</div>
        <div style="font-size:10px;color:var(--muted)">📍 ${escHtml(m.cidade)} &nbsp;|&nbsp; 🎨 ${escHtml(m.estilo)}</div>
      </div>`;
    grid.appendChild(card);
  });
}

// ─────────────────────────────────────────────
// SEGURANÇA
// ─────────────────────────────────────────────
async function salvarSeguranca() {
  const configs = {
    credito_gratis_ativo:     $('sec_credito_ativo').value,
    credito_gratis_qtd:       $('sec_credito_qtd').value,
    credito_gratis_limite_ip: $('sec_limite_ip').value
  };
  const j = await api('admin_save_configs', { configs });
  toast(j.status === 'sucesso' ? '✅ Salvo!' : j.msg, j.status === 'sucesso' ? 'success' : 'error');
}

async function loadFraudeStats() {
  const j = await api('admin_fraude_stats');
  if (j.status !== 'sucesso') return;

  $('fraudeStats').innerHTML = `
    <div style="display:flex;gap:12px;margin-bottom:12px">
      <div style="flex:1;background:rgba(99,102,241,.1);border-radius:10px;padding:12px;text-align:center">
        <div style="font-size:22px;font-weight:900;color:#a5b4fc">${escHtml(j.total_bonus || 0)}</div>
        <div style="font-size:10px;color:var(--muted);margin-top:2px">Créditos grátis dados</div>
      </div>
      <div style="flex:1;background:rgba(239,68,68,.1);border-radius:10px;padding:12px;text-align:center">
        <div style="font-size:22px;font-weight:900;color:#f87171">${escHtml((j.ips_suspeitos || []).length)}</div>
        <div style="font-size:10px;color:var(--muted);margin-top:2px">IPs suspeitos</div>
      </div>
    </div>`;

  const tbody = $('tbFraudeIPs');
  tbody.innerHTML = '';

  if (!j.ips_suspeitos || j.ips_suspeitos.length === 0) {
    tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;padding:20px;color:var(--muted)">✅ Nenhum IP suspeito detectado</td></tr>';
    return;
  }

  j.ips_suspeitos.forEach(ip => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td style="font-family:monospace">${escHtml(ip.ip_cadastro)}</td>
      <td style="font-weight:700;color:var(--warn)">${escHtml(ip.total)} contas</td>
      <td>
        <div style="display:flex;gap:5px">
          <button class="btn btn-ghost" style="font-size:10px;padding:4px 8px" data-ip="${escHtml(ip.ip_cadastro)}" data-action="copy">📋</button>
          <button class="btn btn-danger" style="font-size:10px;padding:4px 8px" data-ip="${escHtml(ip.ip_cadastro)}" data-action="del">🗑️</button>
        </div>
      </td>`;
    tbody.appendChild(tr);
  });

  tbody.onclick = e => {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    const ip  = btn.dataset.ip;
    const act = btn.dataset.action;
    if (act === 'copy') navigator.clipboard?.writeText(ip).then(() => toast('IP copiado!', 'info'));
    if (act === 'del')  confirmar(`⚠️ Limpar IP ${ip}?`, () => excluirIpSuspeito(ip));
  };
}

async function excluirIpSuspeito(ip) {
  const j = await api('admin_delete_ip_suspeito', { ip });
  if (j.status === 'sucesso') { toast('IP libertado!', 'success'); loadFraudeStats(); }
  else toast(j.msg, 'error');
}

async function loadSegurancaConfig() {
  const j = await api('admin_get_configs');
  if (j.status !== 'sucesso') return;
  const c = j.configs;
  const set = (id, val, fb = '') => { const el = $(id); if (el) el.value = val || fb; };
  set('sec_credito_ativo', c.credito_gratis_ativo,     '1');
  set('sec_credito_qtd',   c.credito_gratis_qtd,       '1');
  set('sec_limite_ip',     c.credito_gratis_limite_ip, '3');
  loadFraudeStats();
}

// ─────────────────────────────────────────────
// PAGINAÇÃO
// ─────────────────────────────────────────────
function renderPagination(containerId, page, totalPages, callback) {
  const el = $(containerId);
  if (!el || totalPages <= 1) { if (el) el.innerHTML = ''; return; }
  el.innerHTML = '';

  const add = (x, label, active = false) => {
    const b = document.createElement('button');
    b.className = 'page-btn' + (active ? ' active' : '');
    b.textContent = label;
    b.onclick = () => callback(x);
    el.appendChild(b);
  };

  if (page > 1) { add(1, '«'); add(page - 1, '◀'); }
  for (let i = Math.max(1, page - 2); i <= Math.min(totalPages, page + 2); i++)
    add(i, i, i === page);
  if (page < totalPages) { add(page + 1, '▶'); add(totalPages, '»'); }
}

// ─────────────────────────────────────────────
// GRÁFICO DE LINHA — corrigido (sem divisão por zero, com labels)
// ─────────────────────────────────────────────
function drawLineChart(cid, labels, values) {
  const canvas = $(cid);
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  const W = canvas.width, H = canvas.height;
  const PAD = { top: 30, right: 20, bottom: 50, left: 60 };
  const cW = W - PAD.left - PAD.right;
  const cH = H - PAD.top  - PAD.bottom;

  ctx.clearRect(0, 0, W, H);
  ctx.fillStyle = 'rgba(255,255,255,.02)';
  ctx.fillRect(0, 0, W, H);

  if (!values.length || values.length < 2) {
    ctx.fillStyle = 'rgba(255,255,255,.3)';
    ctx.font = '13px Inter, sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText('Sem dados suficientes para o período.', W / 2, H / 2);
    return;
  }

  const maxVal = Math.max(...values);
  // Evita divisão por zero quando todos os valores são 0
  const scaleY = maxVal > 0 ? cH / maxVal : 1;
  const scaleX = cW / (values.length - 1);

  // Grid lines horizontais
  const gridLines = 5;
  for (let i = 0; i <= gridLines; i++) {
    const y = PAD.top + cH - (i / gridLines) * cH;
    ctx.strokeStyle = 'rgba(255,255,255,.06)';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(PAD.left, y);
    ctx.lineTo(PAD.left + cW, y);
    ctx.stroke();
    // Labels eixo Y
    const yVal = (maxVal * i / gridLines);
    ctx.fillStyle = 'rgba(255,255,255,.35)';
    ctx.font = '10px Inter, sans-serif';
    ctx.textAlign = 'right';
    ctx.fillText('R$' + yVal.toFixed(0), PAD.left - 6, y + 3);
  }

  // Área preenchida sob a linha
  ctx.beginPath();
  values.forEach((v, i) => {
    const x = PAD.left + i * scaleX;
    const y = PAD.top  + cH - v * scaleY;
    i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
  });
  // Fecha o caminho pela base para preencher
  ctx.lineTo(PAD.left + (values.length - 1) * scaleX, PAD.top + cH);
  ctx.lineTo(PAD.left, PAD.top + cH);
  ctx.closePath();
  const grad = ctx.createLinearGradient(0, PAD.top, 0, PAD.top + cH);
  grad.addColorStop(0,   'rgba(99,102,241,.35)');
  grad.addColorStop(1,   'rgba(99,102,241,.02)');
  ctx.fillStyle = grad;
  ctx.fill();

  // Linha principal
  ctx.beginPath();
  ctx.strokeStyle = 'rgba(99,102,241,.95)';
  ctx.lineWidth   = 2.5;
  ctx.lineJoin    = 'round';
  values.forEach((v, i) => {
    const x = PAD.left + i * scaleX;
    const y = PAD.top  + cH - v * scaleY;
    i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
  });
  ctx.stroke();

  // Pontos
  values.forEach((v, i) => {
    const x = PAD.left + i * scaleX;
    const y = PAD.top  + cH - v * scaleY;
    ctx.beginPath();
    ctx.arc(x, y, 4, 0, Math.PI * 2);
    ctx.fillStyle   = '#6366f1';
    ctx.strokeStyle = '#fff';
    ctx.lineWidth   = 1.5;
    ctx.fill();
    ctx.stroke();
  });

  // Labels eixo X (datas) — mostra no máximo 10 para não sobrepor
  const step = Math.ceil(labels.length / 10);
  ctx.fillStyle  = 'rgba(255,255,255,.35)';
  ctx.font       = '10px Inter, sans-serif';
  ctx.textAlign  = 'center';
  labels.forEach((lbl, i) => {
    if (i % step !== 0 && i !== labels.length - 1) return;
    const x = PAD.left + i * scaleX;
    // Formata "2026-03-24" → "24/03"
    let txt = lbl;
    if (/^\d{4}-\d{2}-\d{2}/.test(lbl)) {
      const [, m, d] = lbl.split('-');
      txt = `${d}/${m}`;
    }
    ctx.fillText(txt, x, PAD.top + cH + 18);
  });
}

// ─────────────────────────────────────────────
// EXPORTAR CSV
// ─────────────────────────────────────────────
async function exportCSV(tipo) {
  const loadMap = {
    usuarios:   { acao: 'admin_usuarios',    payload: { page: 1, limit: 99999, busca: '' } },
    pedidos:    { acao: 'admin_pedidos',     payload: { page: 1, limit: 99999, busca: '', status: '', plano: '', de: '', ate: '' } },
    transacoes: { acao: 'admin_transacoes',  payload: { page: 1, limit: 99999, busca: '', tipo: '', de: '', ate: '' } },
  };
  const cfg = loadMap[tipo];
  if (!cfg) return;

  toast('Gerando CSV...', 'info');
  const j = await api(cfg.acao, cfg.payload);
  if (j.status !== 'sucesso') { toast('Erro ao exportar.', 'error'); return; }

  const rows = j[tipo] || [];
  if (!rows.length) { toast('Nenhum dado para exportar.', 'warn'); return; }

  const headers = Object.keys(rows[0]);
  const csvLines = [
    headers.join(';'),
    ...rows.map(row =>
      headers.map(h => {
        let val = row[h] ?? '';
        val = String(val).replace(/"/g, '""');
        return `"${val}"`;
      }).join(';')
    )
  ];

  const blob = new Blob(['\uFEFF' + csvLines.join('\n')], { type: 'text/csv;charset=utf-8;' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href     = url;
  a.download = `konex_${tipo}_${new Date().toISOString().slice(0,10)}.csv`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
  toast(`CSV de ${tipo} exportado!`, 'success');
}
</script>
</body>
</html>