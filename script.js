
// ═══════════════════════════════════════════════════════════
// LOGIC: KONEX CREATIVE PREMIUM DEFINTIVA
// ═══════════════════════════════════════════════════════════

document.addEventListener('contextmenu', e => e.preventDefault()); 
document.addEventListener('keydown', e => {
    if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && e.key === 'I') || (e.ctrlKey && e.key === 'p') || (e.ctrlKey && e.key === 's') || (e.ctrlKey && e.key === 'c') || (e.ctrlKey && e.key === 'u')) {
        e.preventDefault(); toast('⚠️ Ação bloqueada por segurança do sistema.', 'error');
    }
});
window.addEventListener('beforeprint', e => { document.body.innerHTML = '<h1 style="color:red; text-align:center; margin-top:50px; font-family:sans-serif;">IMPRESSÃO BLOQUEADA</h1>'; });
document.addEventListener('dragstart', e => { if(!e.target.classList.contains('qr-drag')) e.preventDefault(); });

const $ = id => document.getElementById(id);
const API_URL = 'api.php';

// ── WATERMARK CONFIG (carregado do servidor) ──
let wmConfig = {
  text: 'PRÉVIA',
  color: '#cccccc',
  opacity: 0.08,
  size: 14,
  angle: 30
};

async function loadWatermarkConfig() {
  try {
    const r = await fetch(API_URL, { method:'POST', body: JSON.stringify({ acao:'get_precos' }) });
    const j = await r.json();
    if (j.status === 'sucesso') {
      wmConfig.text    = j.watermark_text    || 'PRÉVIA';
      wmConfig.color   = j.watermark_color   || '#cccccc';
      wmConfig.opacity = parseFloat(j.watermark_opacity || '0.08');
      wmConfig.size    = parseInt(j.watermark_size    || '14');
      wmConfig.angle   = parseFloat(j.watermark_angle  || '30');
    }
  } catch(e) { /* usa defaults */ }
}



let data = { exp: [], edu: [] };
let profilePic = '';
let photoPos = 'left';
let currentScale = 1;
let cropper = null;
let saveTimer = null;
let cutLines = []; 
let qrData = { active: false, phone: '55', size: 100, x: 650, y: 50 };

let totalDownloadsApi = 0;
let userEmail = '';
let userSenha = '';
let currentStep = 1;
let currentAiGender = 'H'; 

// ── RESIZER (ARRASTE LATERAL) ──
const resizer = document.getElementById('resizer');
const sidebar = document.getElementById('sidebar');
let isResizingSidebar = false;

resizer.addEventListener('mousedown', () => { isResizingSidebar = true; resizer.classList.add('active'); document.body.style.cursor = 'col-resize'; });
document.addEventListener('mousemove', (e) => {
    if (!isResizingSidebar) return;
    let newWidth = e.clientX;
    if (newWidth > 350 && newWidth < (window.innerWidth - 400) && newWidth < 800) {
        sidebar.style.width = newWidth + 'px'; sidebar.style.minWidth = newWidth + 'px'; autoZoom();
    }
});
document.addEventListener('mouseup', () => { if(isResizingSidebar) { isResizingSidebar = false; resizer.classList.remove('active'); document.body.style.cursor = 'default'; } });

function goToStep(step) {
    currentStep = step;
    document.querySelectorAll('.w-step').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.step-pane').forEach(el => el.classList.remove('active'));
    
    $(`navStep${step}`).classList.add('active');
    $(`paneStep${step}`).classList.add('active');
    
    $('btnPrev').style.visibility = step === 1 ? 'hidden' : 'visible';
    $('btnNext').innerHTML = step === 5 ? 'Finalizar e Ver Pronta' : 'Próximo Passo ▶';
    $('btnNext').onclick = step === 5 ? () => { toast('Currículo atualizado! Veja a prévia ao lado.', 'success'); } : () => { navigateWizard(1); };
}
function navigateWizard(dir) {
    let newStep = currentStep + dir;
    if(newStep >= 1 && newStep <= 5) goToStep(newStep);
}

function toast(msg, type='info') {
  const el = document.createElement('div'); el.className = `toast-item`;
  el.style.borderLeftColor = type === 'success' ? 'var(--success)' : 'var(--danger)';
  el.innerHTML = `<span>${msg}</span>`; $('toast').appendChild(el);
  setTimeout(() => { el.style.opacity = '0'; setTimeout(()=>el.remove(),300); }, 3500);
}
function closeModal(id) { 
    $(id).classList.remove('open'); 
    if(id==='imageModal'&&cropper){cropper.destroy();cropper=null;} 
    if(id==='videoModal'){ const v = $(id).querySelector('video'); if(v) v.pause(); }
}
function openVideoModal() { $('videoModal').classList.add('open'); }

function setAiGender(g) {
    currentAiGender = g;
    document.querySelectorAll('.gender-btn').forEach(btn => btn.classList.remove('active'));
    $(`btnGen${g}`).classList.add('active');
}

const aiTemplates = {
    'Administrativo': { 
        H: { obj: 'Atuar na área Administrativa visando a otimização de processos e organização corporativa.', res: 'Profissional com sólida experiência administrativa, focado na otimização de rotinas de escritório, controle de documentos e suporte à gestão. Hábil no atendimento ao público e elaboração de relatórios. Busco contribuir com eficiência para o crescimento da empresa.', skills: '• Gestão de Documentos\n• Atendimento ao Cliente\n• Pacote Office (Word, Excel Avançado)\n• Organização e Planejamento\n• Rotinas de Departamento Pessoal' },
        M: { obj: 'Atuar na área Administrativa visando a otimização de processos e organização corporativa.', res: 'Profissional com sólida experiência administrativa, focada na otimização de rotinas de escritório, controle de documentos e suporte à gestão. Hábil no atendimento ao público e elaboração de relatórios. Busco contribuir com eficiência para o crescimento da empresa.', skills: '• Gestão de Documentos\n• Atendimento ao Cliente\n• Pacote Office (Word, Excel Avançado)\n• Organização e Planejamento\n• Rotinas de Departamento Pessoal' },
        O: { obj: 'Atuar na área Administrativa visando a otimização de processos e organização corporativa.', res: 'Atuação profissional com sólida experiência administrativa, com foco na otimização de rotinas de escritório, controle de documentos e suporte à gestão. Habilidade no atendimento ao público e elaboração de relatórios. O objetivo é contribuir com eficiência para o crescimento da empresa.', skills: '• Gestão de Documentos\n• Atendimento ao Cliente\n• Pacote Office (Word, Excel Avançado)\n• Organização e Planejamento\n• Rotinas de Departamento Pessoal' }
    },
    'Vendas': { 
        H: { obj: 'Atuar como Consultor Comercial / Vendedor, superando metas e fidelizando a carteira de clientes.', res: 'Profissional da área comercial focado em resultados, prospecção e fechamento de negócios. Experiência no atendimento consultivo, identificação de necessidades e superação de metas de vendas. Possuo excelente comunicação e capacidade para fidelizar clientes.', skills: '• Técnicas de Vendas e Negociação\n• Prospecção Ativa (B2B e B2C)\n• Fechamento de Contratos\n• Comunicação Persuasiva\n• Foco em Metas e Resultados' },
        M: { obj: 'Atuar como Consultora Comercial / Vendedora, superando metas e fidelizando a carteira de clientes.', res: 'Profissional da área comercial focada em resultados, prospecção e fechamento de negócios. Experiência no atendimento consultivo, identificação de necessidades e superação de metas de vendas. Possuo excelente comunicação e capacidade para fidelizar clientes.', skills: '• Técnicas de Vendas e Negociação\n• Prospecção Ativa (B2B e B2C)\n• Fechamento de Contratos\n• Comunicação Persuasiva\n• Foco em Metas e Resultados' },
        O: { obj: 'Atuar na área Comercial / Vendas, com objetivo de superar metas e fidelizar a carteira de clientes.', res: 'Atuação na área comercial com foco em resultados, prospecção e fechamento de negócios. Experiência no atendimento consultivo, identificação de necessidades e superação de metas de vendas. Excelente comunicação e capacidade para fidelizar clientes.', skills: '• Técnicas de Vendas e Negociação\n• Prospecção Ativa (B2B e B2C)\n• Fechamento de Contratos\n• Comunicação Persuasiva\n• Foco em Metas e Resultados' }
    },
    'Tecnologia': { 
        H: { obj: 'Atuar no setor de Tecnologia da Informação, desenvolvendo soluções inovadoras e escaláveis.', res: 'Profissional de Tecnologia com foco em resolução de problemas lógicos e inovação contínua. Vivência no desenvolvimento e manutenção de sistemas, metodologias ágeis e suporte técnico. Sou apaixonado por tecnologia e focado em entregar soluções eficientes.', skills: '• Lógica de Programação\n• Desenvolvimento Web / Lógica\n• Metodologias Ágeis (Scrum)\n• Resolução de Problemas Complexos\n• Suporte e Infraestrutura de TI' },
        M: { obj: 'Atuar no setor de Tecnologia da Informação, desenvolvendo soluções inovadoras e escaláveis.', res: 'Profissional de Tecnologia com foco em resolução de problemas lógicos e inovação contínua. Vivência no desenvolvimento e manutenção de sistemas, metodologias ágeis e suporte técnico. Sou apaixonada por tecnologia e focada em entregar soluções eficientes.', skills: '• Lógica de Programação\n• Desenvolvimento Web / Lógica\n• Metodologias Ágeis (Scrum)\n• Resolução de Problemas Complexos\n• Suporte e Infraestrutura de TI' },
        O: { obj: 'Atuar no setor de Tecnologia da Informação, desenvolvendo soluções inovadoras e escaláveis.', res: 'Atuação em Tecnologia da Informação com foco em resolução de problemas lógicos e inovação contínua. Vivência no desenvolvimento e manutenção de sistemas, metodologias ágeis e suporte técnico. Grande interesse por tecnologia e foco na entrega de soluções eficientes.', skills: '• Lógica de Programação\n• Desenvolvimento Web / Lógica\n• Metodologias Ágeis (Scrum)\n• Resolução de Problemas Complexos\n• Suporte e Infraestrutura de TI' }
    },
    'Saúde': { 
        H: { obj: 'Contribuir com a equipe de Saúde / Enfermagem prestando atendimento seguro e humanizado.', res: 'Profissional da área da saúde dedicado ao cuidado humanizado e à segurança do paciente. Experiência no acompanhamento clínico, administração de medicamentos e suporte em procedimentos. Comprometido com a ética profissional e o bem-estar em ambientes hospitalares.', skills: '• Atendimento Humanizado\n• Suporte Clínico e Triagem\n• Administração de Medicações\n• Controle de Prontuários\n• Primeiros Socorros' },
        M: { obj: 'Contribuir com a equipe de Saúde / Enfermagem prestando atendimento seguro e humanizado.', res: 'Profissional da área da saúde dedicada ao cuidado humanizado e à segurança do paciente. Experiência no acompanhamento clínico, administração de medicamentos e suporte em procedimentos. Comprometida com a ética profissional e o bem-estar em ambientes hospitalares.', skills: '• Atendimento Humanizado\n• Suporte Clínico e Triagem\n• Administração de Medicações\n• Controle de Prontuários\n• Primeiros Socorros' },
        O: { obj: 'Contribuir com a equipe de Saúde prestando atendimento seguro e humanizado.', res: 'Atuação na área da saúde com dedicação ao cuidado humanizado e à segurança de pacientes. Experiência no acompanhamento clínico, administração de medicamentos e suporte em procedimentos. Forte compromisso com a ética profissional e o bem-estar em ambientes hospitalares.', skills: '• Atendimento Humanizado\n• Suporte Clínico e Triagem\n• Administração de Medicações\n• Controle de Prontuários\n• Primeiros Socorros' }
    },
    'Atendimento': { 
        H: { obj: 'Garantir a Excelência no Atendimento ao Cliente, focando na resolução de demandas com agilidade.', res: 'Profissional altamente comunicativo e empático, especializado em atendimento ao cliente. Foco na resolução rápida de demandas, esclarecimento de dúvidas e garantia da satisfação. Tenho facilidade em lidar com o público e operar sistemas de CRM de forma eficiente.', skills: '• Atendimento ao Cliente (SAC)\n• Resolução Rápida de Conflitos\n• Comunicação Clara e Assertiva\n• Paciência e Empatia\n• Operação de Sistemas CRM' },
        M: { obj: 'Garantir a Excelência no Atendimento ao Cliente, focando na resolução de demandas com agilidade.', res: 'Profissional altamente comunicativa e empática, especializada em atendimento ao cliente. Foco na resolução rápida de demandas, esclarecimento de dúvidas e garantia da satisfação. Tenho facilidade em lidar com o público e operar sistemas de CRM de forma eficiente.', skills: '• Atendimento ao Cliente (SAC)\n• Resolução Rápida de Conflitos\n• Comunicação Clara e Assertiva\n• Paciência e Empatia\n• Operação de Sistemas CRM' },
        O: { obj: 'Garantir a Excelência no Atendimento ao Cliente, focando na resolução de demandas com agilidade.', res: 'Atuação altamente comunicativa e com empatia, com especialização em atendimento ao cliente. Foco na resolução rápida de demandas, esclarecimento de dúvidas e garantia da satisfação. Facilidade em lidar com o público e operar sistemas de CRM de forma eficiente.', skills: '• Atendimento ao Cliente (SAC)\n• Resolução Rápida de Conflitos\n• Comunicação Clara e Assertiva\n• Paciência e Empatia\n• Operação de Sistemas CRM' }
    },
    'Jovem Aprendiz': { 
        H: { obj: 'Iniciar a carreira profissional como Jovem Aprendiz, somando dedicação e vontade de aprender.', res: 'Jovem em busca da primeira oportunidade profissional, com muita vontade de aprender e evoluir. Sou dedicado, proativo e possuo grande facilidade para absorver novos conhecimentos. Procuro um ambiente onde possa contribuir com minha energia e dedicação.', skills: '• Muita Vontade de Aprender\n• Proatividade e Dinamismo\n• Comunicação Eficaz\n• Facilidade com Informática\n• Organização e Pontualidade' },
        M: { obj: 'Iniciar a carreira profissional como Jovem Aprendiz, somando dedicação e vontade de aprender.', res: 'Jovem em busca da primeira oportunidade profissional, com muita vontade de aprender e evoluir. Sou dedicada, proativa e possuo grande facilidade para absorver novos conhecimentos. Procuro um ambiente onde possa contribuir com minha energia e dedicação.', skills: '• Muita Vontade de Aprender\n• Proatividade e Dinamismo\n• Comunicação Eficaz\n• Facilidade com Informática\n• Organização e Pontualidade' },
        O: { obj: 'Iniciar a carreira profissional como Jovem Aprendiz, somando dedicação e vontade de aprender.', res: 'Em busca da primeira oportunidade profissional, com muita vontade de aprender e evoluir na carreira. Perfil com dedicação, proatividade e grande facilidade para absorver novos conhecimentos. O objetivo é integrar um ambiente onde seja possível contribuir com energia e dedicação.', skills: '• Muita Vontade de Aprender\n• Proatividade e Dinamismo\n• Comunicação Eficaz\n• Facilidade com Informática\n• Organização e Pontualidade' }
    },
    'Operacional': { 
        H: { obj: 'Atuar na área de Logística / Operacional, garantindo agilidade e cumprimento rigoroso das metas.', res: 'Profissional dedicado e ágil, com experiência em rotinas operacionais, logística e controle de estoque. Habituado a trabalhar com metas de produtividade, organização de ambientes e processos de carga e descarga. Prezo pela segurança no trabalho e assiduidade.', skills: '• Controle de Estoque e Logística\n• Organização de Ambientes\n• Agilidade e Trabalho Físico\n• Rotinas Operacionais Integradas\n• Normas de Segurança (EPIs)' },
        M: { obj: 'Atuar na área de Logística / Operacional, garantindo agilidade e cumprimento rigoroso das metas.', res: 'Profissional dedicada e ágil, com experiência em rotinas operacionais, logística e controle de estoque. Habituada a trabalhar com metas de produtividade, organização de ambientes e processos de carga e descarga. Prezo pela segurança no trabalho e assiduidade.', skills: '• Controle de Estoque e Logística\n• Organização de Ambientes\n• Agilidade e Trabalho Físico\n• Rotinas Operacionais Integradas\n• Normas de Segurança (EPIs)' },
        O: { obj: 'Atuar na área de Logística / Operacional, garantindo agilidade e cumprimento rigoroso das metas.', res: 'Atuação dedicada e ágil, com experiência em rotinas operacionais, logística e controle de estoque. Hábito no trabalho com metas de produtividade, organização de ambientes e processos de carga e descarga. Foco na segurança no trabalho e assiduidade.', skills: '• Controle de Estoque e Logística\n• Organização de Ambientes\n• Agilidade e Trabalho Físico\n• Rotinas Operacionais Integradas\n• Normas de Segurança (EPIs)' }
    },
    'Engenharia': { 
        H: { obj: 'Aplicar conhecimentos de Engenharia no planejamento e execução de projetos técnicos e eficientes.', res: 'Engenheiro focado em planejamento estratégico, execução de obras e análise de viabilidade técnica. Possuo forte capacidade analítica, liderança de equipes em campo e domínio de softwares de modelagem. Comprometido com a entrega de projetos dentro do prazo e orçamento.', skills: '• Gestão de Projetos (PMBOK)\n• AutoCAD / Revit\n• Análise Estrutural e de Viabilidade\n• Liderança de Equipes em Campo\n• Controle de Orçamento' },
        M: { obj: 'Aplicar conhecimentos de Engenharia no planejamento e execução de projetos técnicos e eficientes.', res: 'Engenheira focada em planejamento estratégico, execução de obras e análise de viabilidade técnica. Possuo forte capacidade analítica, liderança de equipes em campo e domínio de softwares de modelagem. Comprometida com a entrega de projetos dentro do prazo e orçamento.', skills: '• Gestão de Projetos (PMBOK)\n• AutoCAD / Revit\n• Análise Estrutural e de Viabilidade\n• Liderança de Equipes em Campo\n• Controle de Orçamento' },
        O: { obj: 'Aplicar conhecimentos de Engenharia no planejamento e execução de projetos técnicos e eficientes.', res: 'Atuação em Engenharia com foco em planejamento estratégico, execução de obras e análise de viabilidade técnica. Forte capacidade analítica, liderança de equipes em campo e domínio de softwares de modelagem. Compromisso com a entrega de projetos dentro do prazo e orçamento estipulados.', skills: '• Gestão de Projetos (PMBOK)\n• AutoCAD / Revit\n• Análise Estrutural e de Viabilidade\n• Liderança de Equipes em Campo\n• Controle de Orçamento' }
    },
    'Marketing': { 
        H: { obj: 'Atuar no setor de Marketing / Publicidade, criando campanhas de alto impacto visual e engajamento.', res: 'Especialista em Marketing e Criação, focado em alavancar resultados através de estratégias digitais, inbound marketing e design criativo. Tenho experiência em análise de métricas (ROI, CAC), gestão de redes sociais e planejamento. Busco transformar ideias em resultados reais.', skills: '• Marketing Digital e Inbound\n• Gestão de Tráfego e SEO\n• Pacote Adobe (Photoshop, Illustrator)\n• Copywriting Estratégico\n• Análise de Métricas e KPIs' },
        M: { obj: 'Atuar no setor de Marketing / Publicidade, criando campanhas de alto impacto visual e engajamento.', res: 'Especialista em Marketing e Criação, focada em alavancar resultados através de estratégias digitais, inbound marketing e design criativo. Tenho experiência em análise de métricas (ROI, CAC), gestão de redes sociais e planejamento. Busco transformar ideias em resultados reais.', skills: '• Marketing Digital e Inbound\n• Gestão de Tráfego e SEO\n• Pacote Adobe (Photoshop, Illustrator)\n• Copywriting Estratégico\n• Análise de Métricas e KPIs' },
        O: { obj: 'Atuar no setor de Marketing / Publicidade, criando campanhas de alto impacto visual e engajamento.', res: 'Especialização em Marketing e Criação, com foco em alavancar resultados através de estratégias digitais, inbound marketing e design criativo. Experiência em análise de métricas (ROI, CAC), gestão de redes sociais e planejamento. O objetivo é transformar ideias em resultados reais e mensuráveis.', skills: '• Marketing Digital e Inbound\n• Gestão de Tráfego e SEO\n• Pacote Adobe (Photoshop, Illustrator)\n• Copywriting Estratégico\n• Análise de Métricas e KPIs' }
    },
    'Educação': { 
        H: { obj: 'Atuar na área da Educação, transmitindo conhecimento com metodologias ativas e didática exemplar.', res: 'Educador apaixonado pelo processo de aprendizagem e desenvolvimento humano. Experiência na elaboração de planos de aula criativos, acompanhamento pedagógico e uso de metodologias ativas. Foco em criar um ambiente acolhedor e estimulante para maximizar o potencial de cada aluno.', skills: '• Didática e Metodologias Ativas\n• Planejamento de Aulas\n• Comunicação Clara e Empática\n• Avaliação de Desempenho Escolar\n• Uso de Tecnologias Educacionais' },
        M: { obj: 'Atuar na área da Educação, transmitindo conhecimento com metodologias ativas e didática exemplar.', res: 'Educadora apaixonada pelo processo de aprendizagem e desenvolvimento humano. Experiência na elaboração de planos de aula criativos, acompanhamento pedagógico e uso de metodologias ativas. Foco em criar um ambiente acolhedor e estimulante para maximizar o potencial de cada aluno.', skills: '• Didática e Metodologias Ativas\n• Planejamento de Aulas\n• Comunicação Clara e Empática\n• Avaliação de Desempenho Escolar\n• Uso de Tecnologias Educacionais' },
        O: { obj: 'Atuar na área da Educação, transmitindo conhecimento com metodologias ativas e didática exemplar.', res: 'Atuação na área educacional com forte interesse pelo processo de aprendizagem e desenvolvimento humano. Experiência na elaboração de planos de aula criativos, acompanhamento pedagógico e uso de metodologias ativas. Foco na criação de um ambiente acolhedor e estimulante para maximizar o potencial dos alunos.', skills: '• Didática e Metodologias Ativas\n• Planejamento de Aulas\n• Comunicação Clara e Empática\n• Avaliação de Desempenho Escolar\n• Uso de Tecnologias Educacionais' }
    },
    'Diretoria': { 
        H: { obj: 'Atuar em nível C-Level (Diretoria), liderando operações corporativas para expansão de resultados.', res: 'Executivo Sênior com vasta experiência liderando operações corporativas complexas. Especialista em gestão de crise, reestruturação de processos e expansão de mercado. Experiência no gerenciamento de grandes equipes e no direcionamento estratégico para otimizar recursos operacionais.', skills: '• Planejamento Estratégico & Visão de Longo Prazo\n• Gestão Financeira Global (P&L, DRE)\n• Liderança de Equipes Multidisciplinares\n• Implementação de Metodologias Ágeis\n• Negociação e Resolução de Conflitos' },
        M: { obj: 'Atuar em nível C-Level (Diretoria), liderando operações corporativas para expansão de resultados.', res: 'Executiva Sênior com vasta experiência liderando operações corporativas complexas. Especialista em gestão de crise, reestruturação de processos e expansão de mercado. Experiência no gerenciamento de grandes equipes e no direcionamento estratégico para otimizar recursos operacionais.', skills: '• Planejamento Estratégico & Visão de Longo Prazo\n• Gestão Financeira Global (P&L, DRE)\n• Liderança de Equipes Multidisciplinares\n• Implementação de Metodologias Ágeis\n• Negociação e Resolução de Conflitos' },
        O: { obj: 'Atuar em nível de Liderança Executiva, guiando operações corporativas para expansão de resultados.', res: 'Atuação em nível executivo sênior com vasta experiência na liderança de operações corporativas complexas. Especialização em gestão de crise, reestruturação de processos e expansão de mercado. Experiência no gerenciamento de grandes equipes e no direcionamento estratégico para otimizar recursos operacionais.', skills: '• Planejamento Estratégico & Visão de Longo Prazo\n• Gestão Financeira Global (P&L, DRE)\n• Liderança de Equipes Multidisciplinares\n• Implementação de Metodologias Ágeis\n• Negociação e Resolução de Conflitos' }
    },
    'Financas': { 
        H: { obj: 'Atuar como Analista / Gerente Financeiro, garantindo o controle preciso do fluxo de caixa e relatórios.', res: 'Profissional analítico e com forte orientation para resultados na área de finanças. Experiência consolidada em tesouraria, contas a pagar e receber, conciliação bancária e elaboração de DREs. Focado na mitigação de riscos financeiros e aumento da lucratividade através da otimização de custos.', skills: '• Análise Financeira e Contábil\n• Fluxo de Caixa e Tesouraria\n• Elaboração de DRE e Balanços\n• Excel Avançado e VBA\n• ERPs Financeiros (SAP, Totvs)' },
        M: { obj: 'Atuar como Analista / Gerente Financeiro, garantindo o controle preciso do fluxo de caixa e relatórios.', res: 'Profissional analítica e com forte orientation para resultados na área de finanças. Experiência consolidada em tesouraria, contas a pagar e receber, conciliação bancária e elaboração de DREs. Focada na mitigação de riscos financeiros e aumento da lucratividade através da otimização de custos.', skills: '• Análise Financeira e Contábil\n• Fluxo de Caixa e Tesouraria\n• Elaboração de DRE e Balanços\n• Excel Avançado e VBA\n• ERPs Financeiros (SAP, Totvs)' },
        O: { obj: 'Atuar na Gestão Financeira, garantindo o controle preciso do fluxo de caixa e relatórios estratégicos.', res: 'Atuação com perfil analítico e com forte orientation para resultados na área de finanças. Experiência consolidada em tesouraria, contas a pagar e receber, conciliação bancária e elaboração de DREs. Foco na mitigação de riscos financeiros e no aumento da lucratividade através da otimização de custos.', skills: '• Análise Financeira e Contábil\n• Fluxo de Caixa e Tesouraria\n• Elaboração de DRE e Balanços\n• Excel Avançado e VBA\n• ERPs Financeiros (SAP, Totvs)' }
    },
    'RH': { 
        H: { obj: 'Atuar na gestão de Recursos Humanos e Departamento Pessoal, promovendo um clima organizacional saudável.', res: 'Especialista em Gestão de Pessoas com foco em recrutamento e seleção, treinamento e desenvolvimento de lideranças. Experiência robusta em rotinas de departamento pessoal (folha de pagamento, benefícios, rescisões) e implementação de políticas de retenção de talentos e endomarketing.', skills: '• Recrutamento e Seleção por Competências\n• Treinamento e Desenvolvimento (T&D)\n• Rotinas de Departamento Pessoal\n• Gestão de Clima e Endomarketing\n• Avaliação de Desempenho' },
        M: { obj: 'Atuar na gestão de Recursos Humanos e Departamento Pessoal, promovendo um clima organizacional saudável.', res: 'Especialista em Gestão de Pessoas com foco em recrutamento e seleção, treinamento e desenvolvimento de lideranças. Experiência robusta em rotinas de departamento pessoal (folha de pagamento, benefícios, rescisões) e implementação de políticas de retenção de talentos e endomarketing.', skills: '• Recrutamento e Seleção por Competências\n• Treinamento e Desenvolvimento (T&D)\n• Rotinas de Departamento Pessoal\n• Gestão de Clima e Endomarketing\n• Avaliação de Desempenho' },
        O: { obj: 'Atuar na gestão de Recursos Humanos e Departamento Pessoal, promovendo um clima organizacional saudável.', res: 'Especialização em Gestão de Pessoas com foco em recrutamento e seleção, além de treinamento e desenvolvimento de lideranças. Experiência robusta em rotinas de departamento pessoal (folha de pagamento, benefícios, rescisões) e implementação de políticas de retenção de talentos e endomarketing.', skills: '• Recrutamento e Seleção por Competências\n• Treinamento e Desenvolvimento (T&D)\n• Rotinas de Departamento Pessoal\n• Gestão de Clima e Endomarketing\n• Avaliação de Desempenho' }
    }
};

function fillAI(type) {
    const area = $('aiArea').value;
    const g = currentAiGender;
    const dt = aiTemplates[area] ? aiTemplates[area][g] : null;
    if(!dt) return;

    const level = ($('aiLevel') ? $('aiLevel').value : 'pleno');
    const kwRaw = ($('aiKeywords') ? ($('aiKeywords').value || '').trim() : '');
    const kws = kwRaw ? kwRaw.split(',').map(s=>s.trim()).filter(Boolean).slice(0, 12) : [];

    const lvl = ({
      junior: {tag:'Júnior', plus:'com forte vontade de aprender e rápida adaptação'},
      pleno:  {tag:'Pleno',  plus:'com foco em performance, processos e melhoria contínua'},
      senior: {tag:'Sênior', plus:'com liderança e visão estratégica para tomada de decisão'}
    })[level] || {tag:'Pleno', plus:'com foco em performance e melhoria contínua'};

    const addKeywordsSentence = (txt) => {
      if(!kws.length) return txt;
      const k = kws.slice(0,6).join(', ');
      const extra = `\n\nPalavras‑chave: ${k}.`;
      return (txt || '').trim() + extra;
    };

    const injectSkills = (base) => {
      if(!kws.length) return base;
      const lines = (base||'').split(/\n/).map(s=>s.trim()).filter(Boolean);
      const norm = (s) => s.replace(/^•\s*/,'').toLowerCase();
      const have = new Set(lines.map(norm));
      const extra = [];
      for(const k of kws){
        const nk = k.toLowerCase();
        if(!have.has(nk)) extra.push('• ' + k);
        if(extra.length >= 8) break;
      }
      return (lines.concat(extra)).join('\n');
    };

    const obj = (dt.obj || '').replace(/\.$/, '') + ` (${lvl.tag}) — ${lvl.plus}.`;
    const res = (dt.res || '').replace(/\.$/, '') + `. Perfil ${lvl.tag} ${lvl.plus}.`;

    if(type === 'resumo' || type === 'tudo') $('inRes').value = addKeywordsSentence(res);
    if(type === 'skills' || type === 'tudo') $('inSkills').value = injectSkills(dt.skills || '');
    if(type === 'objetivo' || type === 'tudo') $('inObj').value = kws.length ? (obj + ` Principais focos: ${kws.slice(0,4).join(', ')}.`) : obj;

    render(); saveData(); toast('✨ Textos gerados por Inteligência Artificial com sucesso!', 'success');
}

function fillMockData() {
    $('inNome').value = 'João Carlos Pereira';
    $('inEstadoCivil').value = 'Casado(a)'; $('inNasc').value = '15/08/1990';
    $('inCel').value = '(11) 98765-4321'; $('inRecado').value = '(11) 91234-5678';
    $('inEmail').value = 'joaocarlos.p@email.com'; $('inRua').value = 'Avenida Paulista, 1000 - São Paulo/SP';
    $('inLink').value = 'linkedin.com/in/joaocarlos'; $('selCNHCat').value = 'B'; $('inCNH').value = '';
    
    $('inObj').value = 'Atuar como Coordenador Estratégico visando a otimização de resultados e expansão corporativa.';
    $('inRes').value = 'Profissional com mais de 10 anos de experiência em gestão de projetos, liderança de equipes multidisciplinares e planejamento estratégico. Especialista em implementação de metodologias ágeis que resultaram em 30% de aumento na produtividade do último ano. Focado em resultados, análise de métricas e desenvolvimento humano. Habitado a tomar decisões em ambientes de alta pressão e a conduzir negociações complexas com fornecedores internacionais. Sempre em busca de inovação e melhoria contínua dos processos operacionais.';
    $('inSkills').value = '• Liderança e Gestão de Conflitos\n• Metodologias Ágeis (Scrum, Kanban)\n• Planejamento Estratégico e Orçamentário\n• Análise de Dados e KPIs (Power BI)\n• Comunicação Assertiva e Negociação\n• Gestão de Tempo e Priorização';
    $('inIdiomas').value = 'Inglês — Fluente (Certificação TOEFL)\nEspanhol — Nível Intermediário\nFrancês — Básico';
    $('inInfos').value = 'Disponibilidade imediata para início.\nDisponibilidade para viagens nacionais e internacionais.\nPalestrante voluntário em eventos de tecnologia educacional.';
    
    data.exp = [
        { role: 'Coordenador de Projetos Sênior', comp: 'Tech Solutions Global', period: 'Jan/2020 - Atual', desc: 'Liderança de uma equipe de 25 profissionais na execução de projetos de transformação digital. Redução de custos operacionais em 15% através da otimização de processos e renegociação de contratos. Implantação de sistema ERP que unificou dados de 5 filiais.' },
        { role: 'Analista Estratégico Pleno', comp: 'Inova Business Corp', period: 'Mar/2016 - Dez/2019', desc: 'Análise de viabilidade de novos negócios e expansão de mercado. Criação de dashboards em Power BI para diretoria. Treinamento de mais de 100 colaboradores nas novas ferramentas de gestão corporativa.' },
        { role: 'Assistente Administrativo', comp: 'Grupo Alpha', period: 'Fev/2013 - Fev/2016', desc: 'Suporte direto à gerência comercial. Elaboração de relatórios semanais de vendas, controle de fluxo de caixa e gestão de arquivos digitais e físicos. Atendimento a clientes VIP.' }
    ];
    data.edu = [
        { course: 'MBA em Gestão Empresarial e Liderança', inst: 'Fundação Getulio Vargas (FGV)', period: '2018 - 2020', desc: 'Trabalho de conclusão aprovado com nota máxima. Foco em estratégias de mercado em tempos de crise.' },
        { course: 'Bacharelado em Administração de Empresas', inst: 'Universidade de São Paulo (USP)', period: '2010 - 2014', desc: 'Participação ativa na Empresa Júnior, atuando como Diretor de Projetos no último ano.' }
    ];
    
    $('chkQR').checked = true;
    qrData.active = true;
    qrData.phone = '5511987654321';
    $('inQRPhone').value = qrData.phone;
    
    renderLists(); render(); saveData(); goToStep(5);
    toast('✨ Currículo de teste preenchido! Role para baixo na prévia.', 'success');
}

const themeVariantIndex = {}; 
const themes = [
  { name: 'Executivo', layout: 'layout-classic lay-8', style: 'var-14', font: "'Times New Roman', serif", color: '#1e3a8a', sec: '#f1f5f9', fontCol: '#0f172a', sideCol: '#ffffff' },
  { name: 'Moderno', layout: 'layout-side-left lay-12', style: 'var-1', font: "'Inter', sans-serif", color: '#0ea5e9', sec: '#0f172a', fontCol: '#334155', sideCol: '#f8fafc' },
  { name: 'Criativo', layout: 'layout-artistic', style: 'var-6', font: "'Poppins', sans-serif", color: '#ec4899', sec: '#fdf2f8', fontCol: '#4c1d95', sideCol: '#ffffff' },
  { name: 'Elegante', layout: 'layout-minimal lay-26', style: 'var-3', font: "'Playfair Display', serif", color: '#b45309', sec: '#fffbeb', fontCol: '#1c1917', sideCol: '#ffffff' },
  { name: 'Tech', layout: 'layout-side-right lay-14', style: 'var-9', font: "'Roboto Mono', monospace", color: '#10b981', sec: '#1f2937', fontCol: '#f3f4f6', sideCol: '#10b981' },
  { name: 'Minimal', layout: 'layout-minimal', style: 'var-0', font: "'Lato', sans-serif", color: '#000000', sec: '#ffffff', fontCol: '#000000', sideCol: '#000000' }
];

const themeExtraVariants = [
  [{color:'#1e3a8a',sec:'#f1f5f9',fontCol:'#0f172a',sideCol:'#ffffff'},{color:'#1e3a5f',sec:'#dbeafe',fontCol:'#0f172a',sideCol:'#ffffff'},{color:'#0f172a',sec:'#e0e7ff',fontCol:'#0f172a',sideCol:'#ffffff'},{color:'#312e81',sec:'#eef2ff',fontCol:'#0f172a',sideCol:'#ffffff'}],
  [{color:'#0ea5e9',sec:'#0f172a',fontCol:'#334155',sideCol:'#f8fafc'},{color:'#6366f1',sec:'#1e1b4b',fontCol:'#334155',sideCol:'#e0e7ff'},{color:'#0284c7',sec:'#082f49',fontCol:'#334155',sideCol:'#f0f9ff'},{color:'#7c3aed',sec:'#2e1065',fontCol:'#ede9fe',sideCol:'#ddd6fe'}],
  [{color:'#ec4899',sec:'#fdf2f8',fontCol:'#4c1d95',sideCol:'#ffffff'},{color:'#f43f5e',sec:'#fff1f2',fontCol:'#4c1d95',sideCol:'#ffffff'},{color:'#a855f7',sec:'#faf5ff',fontCol:'#1e1b4b',sideCol:'#ffffff'},{color:'#fb7185',sec:'#fff0f6',fontCol:'#831843',sideCol:'#ffffff'}],
  [{color:'#b45309',sec:'#fffbeb',fontCol:'#1c1917',sideCol:'#ffffff'},{color:'#78350f',sec:'#fef3c7',fontCol:'#1c1917',sideCol:'#ffffff'},{color:'#d97706',sec:'#fffbeb',fontCol:'#1c1917',sideCol:'#ffffff'},{color:'#92400e',sec:'#fef9c3',fontCol:'#1c1917',sideCol:'#ffffff'}],
  [{color:'#10b981',sec:'#1f2937',fontCol:'#f3f4f6',sideCol:'#10b981'},{color:'#06b6d4',sec:'#164e63',fontCol:'#ecfeff',sideCol:'#06b6d4'},{color:'#22c55e',sec:'#052e16',fontCol:'#f0fdf4',sideCol:'#bbf7d0'},{color:'#a3e635',sec:'#1a2e05',fontCol:'#365314',sideCol:'#ecfccb'}],
  [{color:'#000000',sec:'#ffffff',fontCol:'#000000',sideCol:'#000000'},{color:'#374151',sec:'#f9fafb',fontCol:'#111827',sideCol:'#111827'},{color:'#1f2937',sec:'#f3f4f6',fontCol:'#111827',sideCol:'#111827'},{color:'#475569',sec:'#f8fafc',fontCol:'#0f172a',sideCol:'#0f172a'}]
];

/* ─────────────────────────────────────────────────────────
   KONEX CREATIVE — +50 TEMAS INTELIGENTES (ADD-ON)
   (Não substitui temas existentes: apenas adiciona)
────────────────────────────────────────────────────────── */
(function add50SmartThemes(){
  const layouts = [
    { layout: 'layout-classic lay-8',     style: 'var-14' },
    { layout: 'layout-side-left lay-12',  style: 'var-1'  },
    { layout: 'layout-side-right lay-14', style: 'var-9'  },
    { layout: 'layout-minimal lay-26',    style: 'var-3'  },
    { layout: 'layout-geo lay-13',        style: 'var-6'  },
    { layout: 'layout-artistic lay-22',   style: 'var-6'  },
    { layout: 'layout-boxed lay-11',      style: 'var-14' }
  ];

  const fonts = [
    "'Inter', sans-serif",
    "'Poppins', sans-serif",
    "'Montserrat', sans-serif",
    "'Merriweather', serif",
    "'Playfair Display', serif",
    "'Lato', sans-serif",
    "'Raleway', sans-serif",
    "'Nunito', sans-serif",
    "'Oswald', sans-serif",
    "'Roboto Mono', monospace"
  ];

  const paletteBanks = [
    { label:'Navy Paper',   prims:['#1e3a8a','#1d4ed8','#0f766e','#7c3aed'], sec:'#f1f5f9', fontCol:'#0f172a' },
    { label:'Sky Night',    prims:['#0ea5e9','#38bdf8','#0284c7','#6366f1'], sec:'#0f172a', fontCol:'#f8fafc' },
    { label:'Emerald Dark', prims:['#10b981','#34d399','#14b8a6','#22c55e'], sec:'#052e2b', fontCol:'#ecfeff' },
    { label:'Amber Clean',  prims:['#b45309','#f59e0b','#d97706','#a16207'], sec:'#fffbeb', fontCol:'#1c1917' },
    { label:'Rose Quartz',  prims:['#e11d48','#ef4444','#db2777','#be123c'], sec:'#fff1f2', fontCol:'#111827' },
    { label:'Violet Mist',  prims:['#7c3aed','#6d28d9','#8b5cf6','#a855f7'], sec:'#f5f3ff', fontCol:'#111827' },
    { label:'Graphite',     prims:['#111827','#334155','#0f172a','#1f2937'], sec:'#111827', fontCol:'#f9fafb' },
    { label:'Teal Cloud',   prims:['#14b8a6','#0f766e','#06b6d4','#22c55e'], sec:'#ecfeff', fontCol:'#0f172a' },
    { label:'Coffee',       prims:['#92400e','#78350f','#b45309','#7c2d12'], sec:'#fef3c7', fontCol:'#1c1917' },
    { label:'Mono Pro',     prims:['#0f172a','#111827','#1f2937','#334155'], sec:'#ffffff', fontCol:'#0f172a' }
  ];

  function hexToRgb(hex){
    const h = String(hex||'').replace('#','').trim();
    if (h.length !== 6) return {r:255,g:255,b:255};
    return { r:parseInt(h.slice(0,2),16), g:parseInt(h.slice(2,4),16), b:parseInt(h.slice(4,6),16) };
  }
  function isLight(hex){
    const {r,g,b}=hexToRgb(hex);
    const lum=(0.2126*r+0.7152*g+0.0722*b)/255;
    return lum>0.62;
  }
  function makeVariants(p){
    const sideCol = isLight(p.sec) ? '#0f172a' : '#ffffff';
    return p.prims.map(c => ({ color:c, sec:p.sec, fontCol:p.fontCol, sideCol }));
  }

  const newThemes=[];
  const newVars=[];

  for(let i=0;i<50;i++){
    const L=layouts[i%layouts.length];
    const F=fonts[i%fonts.length];
    const P=paletteBanks[i%paletteBanks.length];
    const variants=makeVariants(P);
    const v0=variants[0];

    newThemes.push({
      name: `Smart ${String(i+1).padStart(2,'0')} • ${P.label}`,
      layout: L.layout,
      style: L.style,
      font: F,
      color: v0.color,
      sec: v0.sec,
      fontCol: v0.fontCol,
      sideCol: v0.sideCol
    });

    newVars.push(variants);
  }

  themes.push(...newThemes);
  themeExtraVariants.push(...newVars);

  if (typeof renderThemeGrid === 'function') renderThemeGrid();
})();


let themeFilter = '';
let sidebarTextAuto = true;

function clamp(n,min,max){ return Math.max(min, Math.min(max,n)); }
function hexToRgb(hex){
  const h=(hex||'').replace('#','').trim();
  if(h.length===3){
    const r=parseInt(h[0]+h[0],16), g=parseInt(h[1]+h[1],16), b=parseInt(h[2]+h[2],16);
    return {r,g,b};
  }
  if(h.length!==6) return {r:0,g:0,b:0};
  return {r:parseInt(h.slice(0,2),16), g:parseInt(h.slice(2,4),16), b:parseInt(h.slice(4,6),16)};
}
function rgbToHex(r,g,b){
  const to=v=>v.toString(16).padStart(2,'0');
  return '#'+to(clamp(r,0,255))+to(clamp(g,0,255))+to(clamp(b,0,255));
}
function rgbToHsl(r,g,b){
  r/=255; g/=255; b/=255;
  const max=Math.max(r,g,b), min=Math.min(r,g,b);
  let h=0,s=0,l=(max+min)/2;
  if(max!==min){
    const d=max-min;
    s = l>0.5 ? d/(2-max-min) : d/(max+min);
    switch(max){
      case r: h=(g-b)/d + (g<b?6:0); break;
      case g: h=(b-r)/d + 2; break;
      case b: h=(r-g)/d + 4; break;
    }
    h/=6;
  }
  return {h,s,l};
}
function hslToRgb(h,s,l){
  let r,g,b;
  if(s===0){ r=g=b=l; }
  else{
    const hue2rgb=(p,q,t)=>{
      if(t<0) t+=1; if(t>1) t-=1;
      if(t<1/6) return p+(q-p)*6*t;
      if(t<1/2) return q;
      if(t<2/3) return p+(q-p)*(2/3-t)*6;
      return p;
    };
    const q = l<0.5 ? l*(1+s) : l+s-l*s;
    const p = 2*l-q;
    r = hue2rgb(p,q,h+1/3);
    g = hue2rgb(p,q,h);
    b = hue2rgb(p,q,h-1/3);
  }
  return {r:Math.round(r*255), g:Math.round(g*255), b:Math.round(b*255)};
}
function luminance(hex){
  const {r,g,b}=hexToRgb(hex);
  const srgb=[r,g,b].map(v=>{
    v/=255;
    return v<=0.03928 ? v/12.92 : ((v+0.055)/1.055)**2.4;
  });
  return 0.2126*srgb[0]+0.7152*srgb[1]+0.0722*srgb[2];
}
function contrastRatio(a,b){
  const L1=luminance(a), L2=luminance(b);
  const hi=Math.max(L1,L2), lo=Math.min(L1,L2);
  return (hi+0.05)/(lo+0.05);
}
function bestTextOn(bg){
  const dark='#0f172a', light='#ffffff';
  const cd=contrastRatio(bg,dark), cl=contrastRatio(bg,light);
  return cl>=cd ? light : dark;
}
function enforceReadableColors(){
  const paperBg = '#ffffff';
  const desiredMain = bestTextOn(paperBg);
  if(desiredMain !== '#ffffff') {
    $('inpFontColor').value = '#0f172a';
  }
  const layoutVal = ($('selLayout')?.value || '');
  const prim = $('inpColor')?.value || '#2563eb';
  const sec  = $('inpSecColor')?.value || '#f1f5f9';
  let sbBg = prim;
  if(layoutVal.includes('layout-side-right')) sbBg = sec;
  if(layoutVal.includes('layout-geo')) sbBg = prim;

  const autoSideText = bestTextOn(sbBg);
  if(sidebarTextAuto){
    $('inpSidebarColor').value = autoSideText;
  }
  document.querySelector(':root').style.setProperty('--sidebar-text-color', $('inpSidebarColor').value);
  document.querySelector(':root').style.setProperty('--user-font-color', $('inpFontColor').value);
}

function filterThemes(){
  themeFilter = ($('themeSearch')?.value || '').trim().toLowerCase();
  renderThemeGrid();
}

function applySmartTheme(){
  const optLayout = [...document.querySelectorAll('#selLayout option')];
  const optStyle  = [...document.querySelectorAll('#selStyle option')];
  const optFont   = [...document.querySelectorAll('#selFontFamily option')];
  if(optLayout.length) $('selLayout').value = optLayout[Math.floor(Math.random()*optLayout.length)].value;
  if(optStyle.length) $('selStyle').value = optStyle[Math.floor(Math.random()*optStyle.length)].value;
  if(optFont.length) $('selFontFamily').value = optFont[Math.floor(Math.random()*optFont.length)].value;

  const hue=Math.random();
  const primRgb=hslToRgb(hue, 0.78, 0.42);
  const prim=rgbToHex(primRgb.r, primRgb.g, primRgb.b);
  const secRgb=hslToRgb((hue+0.02)%1, 0.25, 0.95);
  const sec=rgbToHex(secRgb.r, secRgb.g, secRgb.b);

  $('inpColor').value = prim;
  $('inpSecColor').value = sec;
  $('inpFontColor').value = '#0f172a';

  sidebarTextAuto = true;
  enforceReadableColors();
  updateStyles();

  toast('✨ Tema inteligente gerado (legível + profissional).', 'success');
}

function renderThemeGrid() {
  const grid = $('themeGridContainer'); let html = '';
  themes.forEach((t, i) => {
    if(themeFilter && !String(t.name||'').toLowerCase().includes(themeFilter)) return;
    const ev = themeExtraVariants[i] || [];
    const vidx = themeVariantIndex[i] || 0;
    const dots = ev.slice(0, 4).map((v,vi) => {
      const active = vi === vidx ? 'border:2px solid #fff; box-shadow:0 0 0 2px var(--brand);' : '';
      return `<div class="theme-dot" style="background:${v.color}; ${active}" title="Variação ${vi+1}"></div>`;
    }).join('');
    html += `<div class="theme-btn" id="tbtn-${i}" onclick="applyThemeVariant(${i})" style="position:relative;" title="Clique para alternar variações de cor">
      <div class="theme-name">${t.name}</div>
      <div class="theme-dots" id="tdots-${i}">${dots || `<div class="theme-dot" style="background:${t.color}"></div><div class="theme-dot" style="background:${t.sec}"></div>`}</div>
      <div style="font-size:9px;color:var(--ui-muted);margin-top:3px;">▶ ${ev.length > 1 ? ev.length+' cores' : '1 cor'}</div>
    </div>`;
  });
  grid.innerHTML = html;
}

function applyThemeVariant(index) {
  const t = themes[index]; if(!t) return;
  sidebarTextAuto = true;
  const ev = themeExtraVariants[index] || [];
  
  if(ev.length > 1) {
    themeVariantIndex[index] = ((themeVariantIndex[index] || 0) + 1) % ev.length;
  }
  const vidx = themeVariantIndex[index] || 0;
  const v = ev[vidx] || {color: t.color, sec: t.sec, fontCol: t.fontCol, sideCol: t.sideCol};

  $('selLayout').value = t.layout;
  $('selStyle').value = t.style;
  $('selFontFamily').value = t.font;
  $('inpColor').value = v.color;
  $('inpSecColor').value = v.sec;
  $('inpFontColor').value = v.fontCol || t.fontCol;
  $('inpSidebarColor').value = v.sideCol || t.sideCol;
  enforceReadableColors();
  updateStyles();
  
  const dotsEl = $(`tdots-${index}`);
  if(dotsEl) {
    dotsEl.innerHTML = ev.slice(0,4).map((vv,vi) => {
      const active = vi === vidx ? 'border:2px solid #fff; box-shadow:0 0 0 2px var(--brand);' : '';
      return `<div class="theme-dot" style="background:${vv.color}; ${active}"></div>`;
    }).join('') || `<div class="theme-dot" style="background:${v.color}"></div><div class="theme-dot" style="background:${v.sec}"></div>`;
  }
  
  toast(`Tema ${t.name.toUpperCase()} — Variação ${vidx+1}/${ev.length} aplicada!`, 'success');
}

// ── LOJA E AUTENTICAÇÃO ──
function updateSidebarCredit() {
  const badge = $('sidebarCreditBadge');
  if (badge) badge.textContent = `Créditos: ${totalDownloadsApi}`;
  updateDownloadButtonState();
}
function openStore() {
  $('storeModal').classList.add('open');
  if (userEmail) { $('authForms').style.display = 'none'; $('loggedInView').style.display = 'block'; $('storeCreditNum').textContent = totalDownloadsApi; } 
  else { $('authForms').style.display = 'block'; $('loggedInView').style.display = 'none'; }
}
function switchStoreTab(tab) {
  $('secAuth').style.display = tab === 'auth' ? 'block' : 'none'; $('secPlans').style.display = tab === 'plans' ? 'block' : 'none';
  document.querySelectorAll('.s-tab').forEach(el => el.classList.remove('active'));
  if(tab === 'auth') $('tabAuth').classList.add('active'); if(tab === 'plans') $('tabPlans').classList.add('active');
}
function toggleLogReg(view) {
  $('formLogin').style.display = view === 'login' ? 'block' : 'none'; $('formReg').style.display = view === 'reg' ? 'block' : 'none'; $('formForgot').style.display = view === 'forgot' ? 'block' : 'none';
  if(view === 'login' || view === 'reg') {
      $('btnLog').style.background = view === 'login' ? 'var(--brand-grd)' : 'transparent'; $('btnLog').style.color = view === 'login' ? '#fff' : 'var(--ui-txt)';
      $('btnReg').style.background = view === 'reg' ? 'var(--brand-grd)' : 'transparent'; $('btnReg').style.color = view === 'reg' ? '#fff' : 'var(--ui-txt)';
  }
}

async function doAuth() {
  const email = $('lEmail').value.trim(); const senha = $('lSenha').value.trim();
  if (!email || !senha) { toast('Preencha os dados de acesso.', 'error'); return;}
  try {
    const r = await fetch(API_URL, { method:'POST', body: JSON.stringify({ acao:'validar', email, senha }) });
    const j = await r.json();
    if (j.status === 'sucesso') { totalDownloadsApi = parseInt(j.creditos); userEmail = email; userSenha = senha; toast(`Bem-vindo(a)!`, 'success'); updateSidebarCredit(); saveData(); openStore(); } else { toast(j.msg, 'error'); }
  } catch(e) { toast('Erro de conexão.', 'error'); }
}
async function doRegister() {
  const nome = $('rNome').value.trim(), email = $('rEmail').value.trim(), senha = $('rSenha').value.trim();
  if (!email || !senha || !nome) { toast('Preencha Nome, E-mail e Senha.', 'error'); return;}
  try {
    const r = await fetch(API_URL, { method:'POST', body: JSON.stringify({ acao:'registrar', email, senha, nome }) });
    const j = await r.json();
    if (j.status === 'sucesso') { toast('Conta criada! Fazendo login...', 'success'); $('lEmail').value = email; $('lSenha').value = senha; doAuth(); } else { toast(j.msg, 'error'); }
  } catch(e) { toast('Erro de conexão.', 'error'); }
}
async function doResetPassword() {
    const email = $('fEmail').value.trim(); const nova_senha = $('fNovaSenha').value.trim();
    if(!email || nova_senha.length < 6){ toast('Preencha e crie senha de mín. 6 caracteres.', 'error'); return;}
    try {
        const r = await fetch(API_URL, { method:'POST', body: JSON.stringify({ acao:'resetar_senha', email: email, nova_senha: nova_senha }) });
        const j = await r.json();
        if (j.status === 'sucesso') { toast(j.msg, 'success'); $('lEmail').value = email; $('lSenha').value = nova_senha; toggleLogReg('login'); } else { toast(j.msg, 'error'); }
    } catch(e) { toast('Erro ao conectar com o servidor.', 'error'); }
}
function logout() { userEmail = ''; userSenha = ''; totalDownloadsApi = 0; updateSidebarCredit(); saveData(); openStore(); toast('Você saiu da conta.', 'info'); }

async function buyPlan(plano, btnEl) {
  if (!userEmail) { toast('Faça login ou crie uma conta para comprar.', 'error'); switchStoreTab('auth'); return; }
  btnEl.style.opacity = '0.5';
  try {
    const r = await fetch(API_URL, { method: 'POST', body: JSON.stringify({ acao: 'gerar_pagamento', email: userEmail, plano: plano }) });
    const j = await r.json();
    if (j.status === 'sucesso' && j.link) { toast('Redirecionando para Mercado Pago...', 'success'); setTimeout(() => { window.location.href = j.link; }, 1500); } else { toast(j.msg || 'Erro ao gerar pagamento.', 'error'); btnEl.style.opacity = '1'; }
  } catch (e) { toast('Erro de servidor.', 'error'); btnEl.style.opacity = '1'; }
}

async function syncLicenseFromAPI(silent = true) {
  if (!userEmail || !userSenha) return false;
  try {
    const r = await fetch(API_URL, { method:'POST', body: JSON.stringify({ acao:'saldo', email:userEmail, senha:userSenha }) });
    const j = await r.json();
    if (j.status === 'sucesso') { totalDownloadsApi = parseInt(j.creditos); if($('storeCreditNum')) $('storeCreditNum').textContent = totalDownloadsApi; updateSidebarCredit(); saveData(); if(!silent) toast('Saldo atualizado.', 'success'); return true; }
  } catch(e) { return false; }
}

// ── PHOTO CROP ──
function setPhotoPos(side) { photoPos = side; $('btnPosLeft').className = side === 'left' ? 'btn active' : 'btn'; $('btnPosRight').className = side === 'right' ? 'btn active' : 'btn'; render(); saveData(); }
function initCropper(e) { const file = e.target.files[0]; if (!file) return; const reader = new FileReader(); reader.onload = evt => { $('imageToCrop').src = evt.target.result; $('imageModal').classList.add('open'); if (cropper) cropper.destroy(); cropper = new Cropper($('imageToCrop'), { aspectRatio: 1, viewMode: 1 }); }; reader.readAsDataURL(file); }
function saveCrop() { if (cropper) { profilePic = cropper.getCroppedCanvas({ width: 300, height: 300 }).toDataURL(); closeModal('imageModal'); render(); saveData(); } }
function removePhoto() { profilePic = ''; $('inFoto').value=''; render(); saveData(); }

// ── DADOS & RENDERIZAÇÃO ──
function addExp() { data.exp.push({ role:'', comp:'', period:'', desc:''}); renderLists(); saveData();}
function addEdu() { data.edu.push({ course:'', inst:'', period:'', desc:''}); renderLists(); saveData();}

function renderLists() {
  $('listExp').innerHTML = data.exp.map((e, i) => `<div class="exp-item"><input value="${(e.role||'').replace(/"/g,'&quot;')}" oninput="data.exp[${i}].role=this.value;render();saveData()" placeholder="Cargo / Função"><input value="${(e.comp||'').replace(/"/g,'&quot;')}" oninput="data.exp[${i}].comp=this.value;render();saveData()" placeholder="Empresa"><input value="${(e.period||'').replace(/"/g,'&quot;')}" oninput="data.exp[${i}].period=this.value;render();saveData()" placeholder="Período (Ex: Mar/2020 - Atual)"><textarea oninput="data.exp[${i}].desc=this.value;render();saveData()" placeholder="Resumo das atividades...">${e.desc||''}</textarea><button class="btn-red" onclick="data.exp.splice(${i},1);renderLists();render();saveData()">Excluir</button></div>`).join('');
  $('listEdu').innerHTML = data.edu.map((e, i) => `<div class="edu-item"><input value="${(e.course||'').replace(/"/g,'&quot;')}" oninput="data.edu[${i}].course=this.value;render();saveData()" placeholder="Curso"><input value="${(e.inst||'').replace(/"/g,'&quot;')}" oninput="data.edu[${i}].inst=this.value;render();saveData()" placeholder="Instituição"><input value="${(e.period||'').replace(/"/g,'&quot;')}" oninput="data.edu[${i}].period=this.value;render();saveData()" placeholder="Ano de Conclusão"><textarea oninput="data.edu[${i}].desc=this.value;render();saveData()" placeholder="Detalhes (Opcional)">${e.desc||''}</textarea><button class="btn-red" onclick="data.edu.splice(${i},1);renderLists();render();saveData()">Excluir</button></div>`).join('');
  render();
}

function updateStyles() {
  try { enforceReadableColors(); } catch(e) {}
  const r = document.querySelector(':root');
  r.style.setProperty('--user-font', $('selFontFamily').value);
  r.style.setProperty('--user-color', $('inpColor').value);
  r.style.setProperty('--secondary', $('inpSecColor').value);
  r.style.setProperty('--user-font-color', $('inpFontColor').value);
  r.style.setProperty('--sidebar-text-color', $('inpSidebarColor').value);
  r.style.setProperty('--cv-margin', $('rngMargin').value + 'px');
  r.style.setProperty('--cv-spacing', $('rngSpacing').value + 'px');
  r.style.setProperty('--title-scale', $('rngTitle').value);
  r.style.setProperty('--text-scale', $('rngText').value);

  render(); saveData();
}

function render() {
  const container = $('cvWrapper'); let layoutClass = $('selLayout').value; const varClass = $('selStyle').value;
  if (photoPos === 'right') layoutClass += ' pos-right';
  container.className = `cv-content ${layoutClass} palette-custom ${varClass}`;

  const nome = ($('inNome').value || 'SEU NOME COMPLETO').toUpperCase();
  const obj = $('inObj').value.trim(); const objHTML = obj ? `<div class="cv-role" style="text-transform:none;">${obj}</div>` : ``;

  const contatoArr = [$('inEstadoCivil').value, $('inNasc').value ? 'Nasc: '+$('inNasc').value : '', $('inCel').value, $('inRecado').value ? 'Recado: '+$('inRecado').value : '', $('inEmail').value, $('inRua').value, $('inLink').value, $('selCNHCat').value ? 'CNH: '+$('selCNHCat').value + ($('inCNH').value ? ' - '+$('inCNH').value : '') : ''].filter(Boolean);
  const contato = contatoArr.join(' • ');

  const imgTag = profilePic ? `<img src="${profilePic}" class="cv-photo" alt="Foto" style="max-width:140px; border-radius:8px; object-fit:cover;">` : '';

  const resVal = $('inRes').value.trim(); const resHTML = resVal ? `<div class="section-title">Resumo Profissional</div><div class="item-desc" style="white-space:pre-wrap;">${resVal}</div>` : '';
  const skillsVal = $('inSkills').value.trim(); const skillsHTML = skillsVal ? `<div class="section-title">Habilidades</div><div class="item-desc" style="white-space:pre-wrap;">${skillsVal}</div>` : '';
  const idiomasVal = $('inIdiomas').value.trim(); const idiomasHTML = idiomasVal ? `<div class="section-title">Idiomas</div><div class="item-desc" style="white-space:pre-wrap;">${idiomasVal}</div>` : '';
  const infosVal = $('inInfos').value.trim(); const infosHTML = infosVal ? `<div class="section-title">Informações Adicionais</div><div class="item-desc" style="white-space:pre-wrap;">${infosVal}</div>` : '';

  const expHTML = data.exp.length > 0 ? `<div class="section-title">Experiência Profissional</div>` + data.exp.map(e => `<div class="item-box"><div class="item-title">${e.role||''}</div><div class="item-sub">${e.comp||''}</div>${e.period?`<div class="item-meta"><b>Período:</b> ${e.period}</div>`:''}${e.desc?`<div class="item-desc" style="white-space:pre-wrap;">${e.desc}</div>`:''}</div>`).join('') : '';
  const eduHTML = data.edu.length > 0 ? `<div class="section-title">Formação Acadêmica</div>` + data.edu.map(e => `<div class="item-box"><div class="item-title">${e.course||''}</div><div class="item-sub">${e.inst||''}</div>${e.period?`<div class="item-meta"><b>Período:</b> ${e.period}</div>`:''}${e.desc?`<div class="item-desc" style="white-space:pre-wrap;">${e.desc}</div>`:''}</div>`).join('') : '';

  let html = '';
  if (layoutClass.includes('layout-artistic')) { html = `<div class="side-strip"></div><div class="main-body"><div class="header-art"><div class="big-letter">${(nome.charAt(0)||"")}</div><div style="flex:1"><div class="name-art">${nome}</div>${objHTML}<div style="font-size:10px;margin-top:5px;color:var(--txt-main);opacity:.8">${contato}</div></div>${imgTag?`<div style="margin-left:20px">${imgTag}</div>`:''}</div><div style="display:flex;gap:30px"><div style="flex:1;min-width:0">${resHTML}${expHTML}</div><div style="flex:1;min-width:0">${eduHTML}${skillsHTML}${idiomasHTML}${infosHTML}</div></div></div>`; } 
  else if (layoutClass.includes('side')) { const sb = `<div class="sidebar-area">${imgTag}<div style="margin-top:20px"><div class="section-title" style="margin-top:0;">Contato</div><div class="cv-contact" style="color:var(--sidebar-text-color);">${contato.replace(/ • /g,'<br><br>')}</div><div style="color:var(--sidebar-text-color);">${skillsHTML}${idiomasHTML}${infosHTML}</div></div></div>`; const ma = `<div class="main-area"><div class="cv-name">${nome}</div>${objHTML}${resHTML}${expHTML}${eduHTML}</div>`; html = (layoutClass.includes('left') && photoPos==='right') || (layoutClass.includes('right') && photoPos==='left') ? ma + sb : sb + ma; } 
  else if (layoutClass.includes('layout-geo')) { html = `<div class="header-bg">${imgTag}<div><div class="cv-name">${nome}</div><div class="cv-contact">${contato}</div></div></div><div class="main-content">${resHTML}<div style="display:flex;gap:20px;margin-top:20px"><div style="flex:1;min-width:0">${expHTML}</div><div style="flex:1;min-width:0">${eduHTML}${skillsHTML}${idiomasHTML}${infosHTML}</div></div></div>`; } 
  else if (layoutClass.includes('layout-boxed')) { html = `<div class="inner-border"><div class="header">${imgTag}<div class="cv-name">${nome}</div><div class="cv-contact">${contato}</div></div>${resHTML}${expHTML}${eduHTML}${skillsHTML}${idiomasHTML}${infosHTML}</div>`; } 
  else { html = `<div class="header" style="display:flex; gap:20px; align-items:center; padding-bottom:20px; margin-bottom:20px; border-bottom:2px solid var(--prim);">${imgTag}<div style="flex:1;"><div class="cv-name">${nome}</div>${objHTML}<div class="cv-contact">${contato}</div></div></div>${resHTML}${expHTML}${eduHTML}${skillsHTML}${idiomasHTML}${infosHTML}`; }
  container.innerHTML = html;
  
  const paper = $('pdfPage');
  const oldQR = $('drgQRCode'); if(oldQR) oldQR.remove();
  qrData.active = $('chkQR').checked;
  qrData.phone = $('inQRPhone').value;
  qrData.size = $('rngQRSize').value;
  $('qrConfig').style.display = qrData.active ? 'block' : 'none';

  if(qrData.active && qrData.phone.length >= 10) {
      const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=${qrData.size}x${qrData.size}&data=https://wa.me/${qrData.phone}`;
      const img = document.createElement('img');
      img.id = 'drgQRCode'; img.className = 'qr-drag'; img.src = qrUrl; img.draggable = false;
      img.style.left = qrData.x + 'px'; img.style.top = qrData.y + 'px'; 
      img.style.width = qrData.size + 'px'; img.style.height = qrData.size + 'px';
      paper.appendChild(img);
  }

  // Atualiza prévia contínua (divisões + sobreposição)
  try { ensureCuts(true); } catch(e) {}

}

// ── DRAG & DROP: QR CODE ──
let isDraggingQR = false; let qrStartX, qrStartY, qrInitialMouseX, qrInitialMouseY;
document.addEventListener('mousedown', e => { if(e.target.id === 'drgQRCode') { isDraggingQR = true; qrStartX = qrData.x; qrStartY = qrData.y; qrInitialMouseX = e.clientX; qrInitialMouseY = e.clientY; } });
document.addEventListener('touchstart', e => { if(e.target.id === 'drgQRCode') { isDraggingQR = true; qrStartX = qrData.x; qrStartY = qrData.y; qrInitialMouseX = e.touches[0].clientX; qrInitialMouseY = e.touches[0].clientY; e.preventDefault();} }, {passive:false});
document.addEventListener('mousemove', e => { if(isDraggingQR) { let dx = (e.clientX - qrInitialMouseX) / currentScale; let dy = (e.clientY - qrInitialMouseY) / currentScale; qrData.x = qrStartX + dx; qrData.y = qrStartY + dy; $('drgQRCode').style.left = qrData.x + 'px'; $('drgQRCode').style.top = qrData.y + 'px'; } });
document.addEventListener('touchmove', e => { if(isDraggingQR) { let dx = (e.touches[0].clientX - qrInitialMouseX) / currentScale; let dy = (e.touches[0].clientY - qrInitialMouseY) / currentScale; qrData.x = qrStartX + dx; qrData.y = qrStartY + dy; $('drgQRCode').style.left = qrData.x + 'px'; $('drgQRCode').style.top = qrData.y + 'px'; e.preventDefault();} }, {passive:false});
document.addEventListener('mouseup', () => { if(isDraggingQR) { isDraggingQR = false; saveData(); } });
document.addEventListener('touchend', () => { if(isDraggingQR) { isDraggingQR = false; saveData(); } });

// ── DIVISÕES (PRÉVIA CONTÍNUA) + RECORTE MANUAL (MULTI-PÁGINA CHEIA) ──
let divisionsVisible = true;          // mostra guias A4 + sobreposição
let cutEditEnabled = true;           // permite arrastar as divisões

let activeCutDrag = { on:false, el:null };

function getPaper(){ return $('pdfPage'); }
function getCutContainer(){ return $('cutLinesContainer'); }

function getDocHeightPx(){
  const paper = getPaper();
  // altura real do conteúdo (contínuo)
  return Math.max(paper.scrollHeight || 0, 1123);
}

function getPageCount(totalH, pageH=1123){
  return Math.max(1, intDivCeil(totalH, pageH));
}

function intDivCeil(a,b){
  return Math.ceil(a / b);
}

function getAutoStarts(totalH, pageH=1123){
  const pages = getPageCount(totalH, pageH);
  const starts = [];
  for (let i=1; i<pages; i++) starts.push(i*pageH);
  return starts; // início das páginas 2..N
}

function readCutStarts(){
  // Cada .cut-line representa INÍCIO da página (2..N)
  const out=[];
  document.querySelectorAll('#cutLinesContainer .cut-line').forEach(line => {
    const y=parseInt(line.style.top);
    if(!isNaN(y)) out.push(y);
  });
  return out.sort((a,b)=>a-b);
}

function clearCuts(){
  const c=getCutContainer();
  if(c) c.innerHTML='';
}

function setContinuousPreview(on){
  const paper=getPaper();
  if(!paper) return;
  if(on){
    paper.classList.add('continuous-preview');
    const totalH=getDocHeightPx();
    paper.style.height = totalH + 'px';
  } else {
    paper.classList.remove('continuous-preview');
    paper.style.height = '';
  }
}

function makeCutLine(y, idx, bounds){
  const line=document.createElement('div');
  line.className='cut-line';
  line.dataset.idx=String(idx);
  line.dataset.minY=String(bounds.minY);
  line.dataset.maxY=String(bounds.maxY);
  line.style.top = Math.round(y) + 'px';
  line.innerHTML = `<div class="cut-handle" title="Arraste para ajustar">✂️ Divisão ${idx} — Arraste</div>`;
  if(!cutEditEnabled) line.classList.add('locked');

  const handle=line.querySelector('.cut-handle');
  handle.addEventListener('mousedown', (e)=>{
    if(!cutEditEnabled) return;
    activeCutDrag.on=true;
    activeCutDrag.el=line;
    e.preventDefault();
  });
  handle.addEventListener('touchstart', (e)=>{
    if(!cutEditEnabled) return;
    activeCutDrag.on=true;
    activeCutDrag.el=line;
    e.preventDefault();
  }, {passive:false});

  return line;
}

function renderGuides(autoStarts){
  const g=$('pageGuidesContainer');
  if(!g) return;
  g.innerHTML='';
  if(!divisionsVisible) return;
  autoStarts.forEach((y, i)=>{
    const ln=document.createElement('div');
    ln.className='page-guide-line';
    ln.style.top = y + 'px';
    ln.innerHTML = `<div class="pg-label">PÁGINA ${i+2}</div>`;
    g.appendChild(ln);
  });
}

function renderOverlapZones(pageStarts, pageH=1123){
  const z=$('overlapZonesContainer');
  if(!z) return;
  z.innerHTML='';
  if(!divisionsVisible) return;

  // pageStarts inclui página 1 start=0 e demais
  for(let i=1;i<pageStarts.length;i++){
    const prevEnd = pageStarts[i-1] + pageH;
    const curStart = pageStarts[i];
    const overlap = prevEnd - curStart;
    if(overlap > 18){
      const band=document.createElement('div');
      band.className='overlap-zone';
      band.style.top = curStart + 'px';
      band.style.height = overlap + 'px';
      band.innerHTML = `<div class="oz-label">SOBREPOSIÇÃO</div>`;
      z.appendChild(band);
    }
  }
}

function computeNormalizedStarts(totalH, existingCuts){
  // Retorna pageStarts (inclui 0) e cutStarts (p/ DOM) respeitando:
  // - páginas cheias (altura fixa 1123)
  // - sem buracos: start[i] <= start[i-1] + 1123
  // - ordem: start[i] >= start[i-1] + 120
  const pageH=1123;
  const pages=getPageCount(totalH, pageH);

  const autoStarts=[];
  for(let i=1;i<pages;i++) autoStarts.push(i*pageH);

  const starts=[0];
  const cutStarts=[]; // página 2..N

  const cuts=(existingCuts||[]).slice().sort((a,b)=>a-b);

  for(let i=1;i<pages;i++){
    const auto = i*pageH;
    const prevStart = starts[i-1];
    const prevEnd = prevStart + pageH;

    const minY = prevStart + 120;
    const maxY = Math.min(auto, prevEnd);

    // escolhe um corte existente dentro do range, preferindo o mais próximo do auto (maior)
    const cands = cuts.filter(y => y >= minY && y <= maxY);
    const chosen = cands.length ? cands[cands.length-1] : maxY;

    const y = clamp(chosen, minY, maxY);
    starts.push(y);
    cutStarts.push(y);
  }

  return { pages, autoStarts, pageStarts: starts, cutStarts };
}

function ensureCuts(preserve=true){
  // 1) ativa preview contínua
  setContinuousPreview(true);

  const totalH=getDocHeightPx();
  const existing = preserve ? readCutStarts() : [];

  const norm = computeNormalizedStarts(totalH, existing);

  // 2) recria linhas de corte (uma por página extra)
  clearCuts();
  const cont=getCutContainer();

  for(let i=0;i<norm.cutStarts.length;i++){
    const idx=i+1;
    const auto=norm.autoStarts[i];

    const prevStart = norm.pageStarts[i];
    const prevEnd = prevStart + 1123;
    const minY = prevStart + 120;
    const maxY = Math.min(auto, prevEnd);

    const y = clamp(norm.cutStarts[i], minY, maxY);
    const line = makeCutLine(y, idx, {minY, maxY});
    cont.appendChild(line);
  }

  // 3) guias A4 e zona de sobreposição
  renderGuides(norm.autoStarts);
  renderOverlapZones(norm.pageStarts, 1123);

  // 4) persistência
  saveData();

  // 5) zoom ajusta altura
  autoZoom();
}

function toggleDivisions(){
  divisionsVisible=!divisionsVisible;
  const btn=$('btnToggleDivisions');
  if(btn) btn.textContent = divisionsVisible ? '✂️ Divisões ✓' : '✂️ Divisões';
  ensureCuts(true);
}

function toggleCutEdit(){
  cutEditEnabled=!cutEditEnabled;
  const btn=$('btnToggleCutEdit');
  if(btn) btn.textContent = cutEditEnabled ? '🖱️ Editar Cortes ✓' : '🖱️ Editar Cortes';
  document.querySelectorAll('#cutLinesContainer .cut-line').forEach(l=>{
    l.classList.toggle('locked', !cutEditEnabled);
  });
}

function resetCuts(){
  // volta para o automático (sem sobreposição)
  const btn=$('btnResetCuts');
  if(btn) btn.textContent='↺ Reset';
  clearCuts();
  ensureCuts(false);
}

function getClientY(e){
  return e.touches ? e.touches[0].clientY : e.clientY;
}

function moveActiveCut(clientY){
  if(!activeCutDrag.on || !activeCutDrag.el) return;
  const pRect = getPaper().getBoundingClientRect();
  let y = (clientY - pRect.top) / currentScale;

  const minY = parseInt(activeCutDrag.el.dataset.minY || '0');
  const maxY = parseInt(activeCutDrag.el.dataset.maxY || '1123');

  y = clamp(y, minY, maxY);
  activeCutDrag.el.style.top = Math.round(y) + 'px';
}

document.addEventListener('mousemove', (e)=>{ if(activeCutDrag.on) moveActiveCut(getClientY(e)); });
document.addEventListener('touchmove', (e)=>{ if(activeCutDrag.on){ moveActiveCut(getClientY(e)); e.preventDefault(); } }, {passive:false});

document.addEventListener('mouseup', ()=>{
  if(activeCutDrag.on){ activeCutDrag.on=false; activeCutDrag.el=null; ensureCuts(true); }
});
document.addEventListener('touchend', ()=>{
  if(activeCutDrag.on){ activeCutDrag.on=false; activeCutDrag.el=null; ensureCuts(true); }
});
// ── SAVE & LOAD (Auto-Gravação) ──
function saveData() {
  clearTimeout(saveTimer);
  saveTimer = setTimeout(() => {
    cutLines = []; document.querySelectorAll('.cut-line').forEach(line => { 
        let yPos = parseInt(line.style.top);
        if(!isNaN(yPos)) cutLines.push(yPos); 
    });
    const payload = {
      nome: $('inNome').value, cel: $('inCel').value, recado: $('inRecado').value, nasc: $('inNasc').value, email: $('inEmail').value, rua: $('inRua').value, link: $('inLink').value, cnh: $('inCNH').value, cnhCat: $('selCNHCat').value, estadoCivil: $('inEstadoCivil').value, obj: $('inObj').value, res: $('inRes').value, skills: $('inSkills').value, idiomas: $('inIdiomas').value, infos: $('inInfos').value,
      layout: $('selLayout').value, style: $('selStyle').value, margin: $('rngMargin').value, spacing: $('rngSpacing').value, titleScale: $('rngTitle').value, textScale: $('rngText').value,
      exp: data.exp, edu: data.edu, pic: profilePic, photoPos: photoPos, fontFamily: $('selFontFamily').value, userColor: $('inpColor').value, secColor: $('inpSecColor').value, userFontColor: $('inpFontColor').value, sidebarColor: $('inpSidebarColor').value, userEmail: userEmail, userSenha: userSenha, cutLines: cutLines,
      qrData: qrData, currentStep: currentStep, currentAiGender: currentAiGender, aiLevel: ($('aiLevel') ? $('aiLevel').value : 'pleno'), aiKeywords: ($('aiKeywords') ? $('aiKeywords').value : '')
    };
    localStorage.setItem('konexData_FINAL3', JSON.stringify(payload));
  }, 500);
}

function loadData() {
  const saved = JSON.parse(localStorage.getItem('konexData_FINAL3')) || JSON.parse(localStorage.getItem('konexData_FINAL2'));
  if (!saved) { 
      goToStep(1); 
      setTimeout(updateDownloadButtonState, 300); 
      setTimeout(() => ensureCuts(false), 300); // Cria divisões automáticas
      return; 
  }

  $('inNome').value = saved.nome||''; $('inCel').value = saved.cel||''; $('inRecado').value = saved.recado||''; $('inNasc').value = saved.nasc||''; $('inEstadoCivil').value = saved.estadoCivil||''; $('inEmail').value = saved.email||''; $('inRua').value = saved.rua||''; $('inLink').value = saved.link||''; $('inCNH').value = saved.cnh||''; $('selCNHCat').value = saved.cnhCat||''; $('inObj').value = saved.obj||''; $('inRes').value = saved.res||''; $('inSkills').value = saved.skills||''; $('inIdiomas').value = saved.idiomas||''; $('inInfos').value = saved.infos||'';
  
  if (saved.layout) $('selLayout').value = saved.layout; if (saved.style) $('selStyle').value = saved.style; if (saved.margin) $('rngMargin').value = saved.margin; if (saved.spacing) $('rngSpacing').value = saved.spacing; if (saved.titleScale) $('rngTitle').value = saved.titleScale; if (saved.textScale) $('rngText').value = saved.textScale;
  data.exp = saved.exp || []; data.edu = saved.edu || []; profilePic = saved.pic || '';
  if (saved.fontFamily) $('selFontFamily').value = saved.fontFamily; if (saved.userColor) $('inpColor').value = saved.userColor; if (saved.secColor) $('inpSecColor').value = saved.secColor; if (saved.userFontColor) $('inpFontColor').value = saved.userFontColor; if (saved.sidebarColor) $('inpSidebarColor').value = saved.sidebarColor;

  if (saved.photoPos) setPhotoPos(saved.photoPos);
  
  if (saved.qrData) { qrData = saved.qrData; $('chkQR').checked = qrData.active; $('inQRPhone').value = qrData.phone; $('rngQRSize').value = qrData.size; }
  if (saved.currentAiGender) { setAiGender(saved.currentAiGender); }
  if ($('aiLevel') && saved.aiLevel) $('aiLevel').value = saved.aiLevel;
  if ($('aiKeywords') && typeof saved.aiKeywords !== 'undefined') $('aiKeywords').value = saved.aiKeywords;

  userEmail = saved.userEmail || ''; userSenha = saved.userSenha || '';
  if (userEmail && userSenha) syncLicenseFromAPI(true);
  
  // Restaura divisões (cortes)
  if (saved.cutLines && saved.cutLines.length > 0) {
      // linhas serão normalizadas por ensureCuts(true)
      const c = $('cutLinesContainer'); if (c) c.innerHTML = '';
      saved.cutLines.forEach(y => {
        const line = document.createElement('div');
        line.className = 'cut-line';
        line.style.top = (parseInt(y)||0) + 'px';
        line.innerHTML = `<div class="cut-handle" title="Arraste para ajustar">✂️ Divisão — Arraste</div>`;
        c.appendChild(line);
      });
  } else {
      // sem cortes salvos: cria automático
      setTimeout(() => ensureCuts(false), 300);
  }

  goToStep(saved.currentStep || 1);
  renderLists(); updateStyles();
  setTimeout(updateDownloadButtonState, 300);
}

function clearData(silent=false) { 
  if (silent || confirm('⚠️ TEM CERTEZA? Isso apagará TODO o currículo da tela e começará em branco!')) { 
    localStorage.removeItem('konexData_FINAL3'); localStorage.removeItem('konexData_FINAL2'); 
    data = { exp: [], edu: [] }; profilePic = ''; qrData.active = false; $('chkQR').checked = false;
    document.querySelectorAll('input:not([type="color"]):not([type="range"]), textarea').forEach(el => el.value = ''); 
    $('cutLinesContainer').innerHTML = ''; goToStep(1); renderLists(); render(); saveData(); 
    setTimeout(() => ensureCuts(false), 300); // Recria divisões automáticas
    if(!silent) toast('Dados limpos com sucesso.', 'success'); 
  } 
}

// ── GERAÇÃO PDF ──
async function generateVisualPDF(filename, isDraft = false) {
  const el = $('pdfPage'); const overlay = $('exportOverlay'); if (!el) return;
  overlay.style.display = 'flex'; 
  $('exportTitleText').textContent = isDraft ? 'Gerando Prévia Grátis...' : 'Renderizando PDF Oficial...';
  document.documentElement.classList.add('exporting-pdf'); 

  const printContainer = document.createElement('div');
  printContainer.style.position = 'absolute'; printContainer.style.top = '0'; printContainer.style.left = '-9999px'; printContainer.style.width = '794px'; printContainer.style.height = 'auto'; printContainer.style.background = '#fff'; printContainer.style.zIndex = '-1';
  
  const clone = el.cloneNode(true); clone.style.transform = 'none'; clone.style.margin = '0';

  printContainer.appendChild(clone); document.body.appendChild(printContainer);
  
  if (document.fonts && document.fonts.ready) await document.fonts.ready; await new Promise(r => setTimeout(r, 600)); 

  try {
    const canvas = await html2canvas(clone, { scale: 2, useCORS: true, width: 794, windowWidth: 794, scrollY: 0, logging: false, allowTaint: true });
    const pdf = new jspdf.jsPDF('p', 'mm', 'a4'); const pdfWidth = pdf.internal.pageSize.getWidth(); const pdfHeight = pdf.internal.pageSize.getHeight();

    // ── CORTES FULL PAGE (SEM BRANCO) + SOBREPOSIÇÃO ──
    // Cada .cut-line é interpretada como o INÍCIO da página seguinte.
    // Regras:
    // 1) cada página sempre tem altura fixa (A4) => não sobra branco
    // 2) sem buracos => o início da pág. i+1 nunca passa do fim da pág. i
    // 3) pode haver sobreposição (conteúdo repetido) quando o corte sobe

    let cutPoints = [];
    clone.querySelectorAll('.cut-line').forEach(line => {
        let yPos = parseInt(line.style.top);
        if (!isNaN(yPos)) cutPoints.push(yPos * 2);
    });
    cutPoints.sort((a,b) => a - b);

    const pageHeightPx = 1123 * 2;
    const pages = Math.max(1, Math.ceil(canvas.height / pageHeightPx));

    const starts = [0];
    const minGap = 120 * 2; // distância mínima entre inícios de páginas

    for (let i = 1; i < pages; i++) {
        const autoStart = i * pageHeightPx;
        const prevStart = starts[i - 1];
        const prevEnd = prevStart + pageHeightPx;

        const minY = prevStart + minGap;
        const maxY = Math.min(autoStart, prevEnd);

        const cands = cutPoints.filter(y => y >= minY && y <= maxY);
        const chosen = cands.length ? cands[cands.length - 1] : maxY;
        starts.push(Math.max(minY, Math.min(maxY, chosen)));
    }

    for (let i = 0; i < starts.length; i++) {
        let startY = starts[i];

        // Ajuste da última página para garantir preenchimento total
        if (canvas.height > pageHeightPx && startY + pageHeightPx > canvas.height) {
            startY = canvas.height - pageHeightPx;
        }
        if (canvas.height <= pageHeightPx) {
            startY = 0;
        }

        if (i > 0) pdf.addPage();

        const sliceCanvas = document.createElement('canvas');
        sliceCanvas.width = canvas.width;
        sliceCanvas.height = Math.min(pageHeightPx, canvas.height);
        const ctx = sliceCanvas.getContext('2d');

        const sHeight = sliceCanvas.height;
        ctx.drawImage(canvas, 0, startY, canvas.width, sHeight, 0, 0, canvas.width, sHeight);

        const imgData = sliceCanvas.toDataURL('image/jpeg', 1.0);
        pdf.addImage(imgData, 'JPEG', 0, 0, pdfWidth, pdfHeight);
    }

if (isDraft) {
      const allCtx = canvas.getContext('2d');
      // Usa configurações dinâmicas do admin
      const wmText    = wmConfig.text    || 'PRÉVIA';
      const wmColor   = wmConfig.color   || '#cccccc';
      const wmOpacity = wmConfig.opacity || 0.08;
      const wmSize    = wmConfig.size    || 14;
      const wmAngle   = (wmConfig.angle  || 30) * Math.PI / 180;
      
      // Grid de watermarks cobrindo toda a página
      const stepX = canvas.width  / 3;
      const stepY = canvas.height / 5;
      for (let row = 0; row < 6; row++) {
        for (let col = 0; col < 4; col++) {
          allCtx.save();
          allCtx.globalAlpha = wmOpacity;
          allCtx.font = `bold ${wmSize * 2.8}px Arial`;
          allCtx.fillStyle = wmColor;
          allCtx.translate((col * stepX) - stepX * 0.3, (row * stepY) - stepY * 0.3);
          allCtx.rotate(-wmAngle);
          allCtx.fillText(wmText, 0, 0);
          allCtx.restore();
        }
      }
      // Linha diagonal extra semi-transparente
      allCtx.save();
      allCtx.globalAlpha = wmOpacity * 2.5;
      allCtx.font = `bold ${wmSize * 4}px Arial`;
      allCtx.fillStyle = wmColor;
      allCtx.translate(canvas.width * 0.5, canvas.height * 0.5);
      allCtx.rotate(-wmAngle);
      allCtx.textAlign = 'center';
      allCtx.fillText(wmText, 0, 0);
      allCtx.textAlign = 'start';
      allCtx.restore();
      // Borda vermelha tracejada
      allCtx.save();
      allCtx.globalAlpha = 0.4;
      allCtx.strokeStyle = '#dc2626';
      allCtx.lineWidth = 5;
      allCtx.setLineDash([25, 15]);
      allCtx.strokeRect(8, 8, canvas.width - 16, canvas.height - 16);
      allCtx.restore();
    }
    pdf.save(filename); toast(isDraft ? 'Prévia com marca d\'água gerada!' : 'PDF Oficial Gerado Com Sucesso!', 'success');
  } catch(e) { console.error(e); toast('Erro crítico ao gerar PDF.', 'error'); } 
  finally { document.body.removeChild(printContainer); document.documentElement.classList.remove('exporting-pdf'); overlay.style.display = 'none'; autoZoom(); }
}

async function exportPDFDraft() {
  await generateVisualPDF(`Curriculo_DEMO_${Date.now()}.pdf`, true);
}

async function exportPDFOfficial() {
  if (totalDownloadsApi <= 0) { toast('Sem créditos. Adquira na loja para liberar.', 'error'); openStore(); return; }
  if (!confirm(`⚠️ Baixar o PDF OFICIAL consumirá 1 crédito.\nSeu saldo atual é: ${totalDownloadsApi}\n\nDeseja continuar?`)) return;
  try {
    const r = await fetch(API_URL, { method:'POST', body: JSON.stringify({acao:'consumir', email:userEmail, senha:userSenha}) }); const j = await r.json();
    if (j.status === 'sucesso') { totalDownloadsApi = parseInt(j.creditos); updateSidebarCredit(); if($('storeCreditNum')) $('storeCreditNum').textContent = totalDownloadsApi; await generateVisualPDF(`Curriculo_${$('inNome').value||'Konex'}.pdf`, false); } else { toast('Falha ao validar o seu crédito.', 'error'); }
  } catch(e) { toast('Erro de servidor.', 'error'); }
}

async function exportPDFOfficialProtected() {
  if (!userEmail) {
    toast('🔒 Faça login e adquira créditos para baixar o PDF Oficial.', 'error');
    openStore();
    return;
  }
  if (totalDownloadsApi <= 0) {
    const btn = $('btnOfficialDownload');
    if (btn) btn.classList.add('btn-download-locked');
    toast('🔒 Você precisa de créditos para baixar o PDF Oficial. Redirecionando para a Loja...', 'error');
    setTimeout(() => {
      openStore();
      if (btn) btn.classList.remove('btn-download-locked');
    }, 1500);
    return;
  }
  await exportPDFOfficial();
}

function updateDownloadButtonState() {
  const btn = $('btnOfficialDownload');
  if (!btn) return;
  if (totalDownloadsApi > 0 && userEmail) {
    btn.innerHTML = `✅ BAIXAR PDF OFICIAL (${totalDownloadsApi} crédito${totalDownloadsApi !== 1 ? 's' : ''})`;
    btn.style.background = '';
    btn.classList.remove('btn-download-locked');
    const pdfPage = $('pdfPage');
    if (pdfPage) pdfPage.classList.remove('has-watermark');
  } else {
    btn.innerHTML = '🔒 PDF OFICIAL — COMPRAR CRÉDITO';
    btn.classList.add('btn-download-locked');
    const pdfPage = $('pdfPage');
    if (pdfPage && userEmail) pdfPage.classList.add('has-watermark');
  }
}

let marginGuideVisible = false;
function toggleMarginGuide() {
  marginGuideVisible = !marginGuideVisible;
  const paper = $('pdfPage');
  const btn = $('btnToggleMargins');
  if (!paper) return;
  paper.classList.toggle('guide-margins-on', marginGuideVisible);
  if (btn) {
    btn.style.background = marginGuideVisible ? 'rgba(59,130,246,.15)' : '';
    btn.style.borderColor = marginGuideVisible ? '#3b82f6' : '';
    btn.style.color = marginGuideVisible ? '#2563eb' : '';
    btn.textContent = marginGuideVisible ? '📐 Margens ✓' : '📐 Margens';
  }
  const overlay = $('marginGuideOverlay');
  if (overlay) {
    const marginVal = document.documentElement.style.getPropertyValue('--cv-margin') || '40px';
    overlay.setAttribute('data-margin', marginVal);
    overlay.style.top = marginVal;
    overlay.style.left = marginVal;
    overlay.style.right = marginVal;
    overlay.style.bottom = marginVal;
  }
  toast(marginGuideVisible ? '📐 Guia de margens ativado — mostra a área de conteúdo do PDF' : 'Guia de margens desativado', 'info');
}

// Bloqueios Extras
document.addEventListener('keyup', function(e) {
  if (e.key === 'PrintScreen' || e.key === 'Snapshot') {
    const flash = document.createElement('div');
    flash.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.97);z-index:9999999;pointer-events:none;';
    document.body.appendChild(flash);
    navigator.clipboard && navigator.clipboard.writeText('').catch(()=>{});
    setTimeout(() => flash.remove(), 400);
    toast('⚠️ Captura de tela bloqueada.', 'error');
  }
});
document.addEventListener('keydown', function(e) {
  if (e.key === 'PrintScreen' || e.key === 'Snapshot') {
    e.preventDefault();
    toast('⚠️ Captura de tela bloqueada por proteção.', 'error');
  }
  if (e.metaKey && e.shiftKey && (e.key === 's' || e.key === 'S' || e.key === '3' || e.key === '4' || e.key === '5')) {
    e.preventDefault();
    toast('⚠️ Screenshot bloqueado.', 'error');
  }
});
document.addEventListener('visibilitychange', function() {
  if (document.hidden) return;
  const pdfPage = $('pdfPage');
  if (pdfPage) {
    pdfPage.style.webkitUserSelect = 'none';
    pdfPage.style.userSelect = 'none';
  }
});

function initSelects() {
  const fonts = [ {v:"'Inter', sans-serif",n:"Inter"},{v:"'Montserrat', sans-serif",n:"Montserrat"},{v:"'Lato', sans-serif",n:"Lato"}, {v:"'Roboto', sans-serif",n:"Roboto"},{v:"'Open Sans', sans-serif",n:"Open Sans"},{v:"'Nunito', sans-serif",n:"Nunito"}, {v:"'Poppins', sans-serif",n:"Poppins"},{v:"'Raleway', sans-serif",n:"Raleway"},{v:"'Ubuntu', sans-serif",n:"Ubuntu"}, {v:"Arial, sans-serif",n:"Arial"},{v:"'Times New Roman', serif",n:"Times New Roman"} ];
  $('selFontFamily').innerHTML = fonts.map(f => `<option value="${f.v}">${f.n}</option>`).join('');
  let layHTML = `<option value="layout-classic">1. Clássico Padrão</option><option value="layout-side-left">2. Barra Lateral Esq.</option><option value="layout-side-right">3. Barra Lateral Dir.</option><option value="layout-artistic">4. Artístico</option>`;
  const nomesLay = ["8.Clássico Borda Dupla","9.Clássico Centralizado","10.Clássico Degradê","11.Box Arredondado","12.Lateral Escura","13.Geom. Reto","14.Foto Quadrada","15.Min. Fundo Cor","16.Blocos Sólidos","17.Cabeçalho Cor","18.Lateral Tracejada","19.Espaçado","20.Sombra Nome","21.Geom. Curva","22.Artístico Fino","23.Títulos Coloridos","24.Linha Topo","25.Foto Extragrande","26.Super Clean","27.Borda Pontilhada","28.Info Afastada","29.Gradiente Premium","30.Cargo Bloco","31.Alinhado Esq","32.Artístico Traços","33.Conteúdo Sobreposto","34.Contato Bold","35.Moldura Dupla","36.Topo Afastado","37.Borda Interna","38.Padding Grande","39.Foto Hexagonal","40.Pattern BG","41.Arredondado Alt","42.Lateral Solta","43.Nome Sublinhado","44.Bolinha Lateral","45.Topo Minimalista","46.Lateral Branca","47.Coluna Dupla","48.Foto Borda Dupla","49.Sombra Flutuante","50.Header Cheio","51.Borda Larga","52.Contato Dividido","53.Diagonal","54.Maiúsculo","55.Tracejado Claro","56.Lettering","57.Lateral Opaca","58.Linha Topo","59.Recuo Cor","60.Tarja Cor","61.Bloco Título","62.Foto P&B","63.Título Enxuto","64.Gradiente Inv.","65.Header Curto","66.Borda Interna Box","67.Cargo Itálico","68.Faixa Ribbon","69.Sidebar Gradiente","70.Header Band","71.Monochrome Bold","72.Side Accent","73.Magazine 2col","74.Arc Header","75.Ultra Compact","76.Dark Sidebar","77.Timeline Classic","78.Split Equal","79.Luxury Center","80.Stripe Top","81.Name Giant","82.Boxed Color","83.Sidebar Estreita","84.Sidebar Larga","85.Foto Círculo","86.Foto Grande","87.Art Vertical","88.Header Accent","89.Foto Direita","90.Corner Accent","91.Título Full Width","92.BG Gradiente","93.Dupla Coluna","94.Serif Luxury","95.Modern Grid","96.Tall Sidebar","97.Diagonal Stripe","98.Bold Initials","99.Sidebar Compact","100.Elevated Card","101.Tech Dark","102.Publisher","103.Neon Accent","104.Stamp Style","105.Wide Header","106.Sidebar Right Dark","107.Thin Line","108.Corporate+","109.Creative Overlap","110.Academic Formal","111.Round Badge","112.Gradient Side","113.Shadow Card","114.Condensed Pro","115.Artistic Bordered","116.Geo Bold","117.Full Banner"];
  const layClasses = ["layout-classic lay-8","layout-classic lay-9","layout-classic lay-10","layout-boxed lay-11","layout-side-left lay-12","layout-geo lay-13","layout-side-right lay-14","layout-minimal lay-15","layout-classic lay-16","layout-classic lay-17","layout-side-left lay-18","layout-minimal lay-19","layout-classic lay-20","layout-geo lay-21","layout-artistic lay-22","layout-boxed lay-23","layout-classic lay-24","layout-side-left lay-25","layout-minimal lay-26","layout-boxed lay-27","layout-classic lay-28","layout-side-left lay-29","layout-classic lay-30","layout-classic lay-31","layout-artistic lay-32","layout-geo lay-33","layout-side-right lay-34","layout-classic lay-35","layout-side-left lay-36","layout-minimal lay-37","layout-classic lay-38","layout-side-left lay-39","layout-geo lay-40","layout-boxed lay-41","layout-side-left lay-42","layout-classic lay-43","layout-minimal lay-44","layout-classic lay-45","layout-side-left lay-46","layout-artistic lay-47","layout-geo lay-48","layout-boxed lay-49","layout-classic lay-50","layout-side-left lay-51","layout-minimal lay-52","layout-geo lay-53","layout-classic lay-54","layout-boxed lay-55","layout-artistic lay-56","layout-side-left lay-57","layout-side-right lay-58","layout-minimal lay-59","layout-classic lay-60","layout-artistic lay-61","layout-side-right lay-62","layout-minimal lay-63","layout-side-left lay-64","layout-geo lay-65","layout-boxed lay-66","layout-classic lay-67","layout-classic lay-68","layout-side-left lay-69","layout-classic lay-70","layout-minimal lay-71","layout-side-right lay-72","layout-geo lay-73","layout-classic lay-74","layout-classic lay-75","layout-side-left lay-76","layout-classic lay-77","layout-side-left lay-78","layout-minimal lay-79","layout-classic lay-80","layout-classic lay-81","layout-boxed lay-82","layout-side-left lay-83","layout-side-left lay-84","layout-classic lay-85","layout-classic lay-86","layout-artistic lay-87","layout-classic lay-88","layout-classic lay-89","layout-boxed lay-90","layout-classic lay-91","layout-minimal lay-92","layout-classic lay-93","layout-classic lay-94","layout-side-right lay-95","layout-side-left lay-96","layout-classic lay-97","layout-classic lay-98","layout-side-left lay-99","layout-boxed lay-100","layout-classic lay-101","layout-classic lay-102","layout-side-left lay-103","layout-boxed lay-104","layout-classic lay-105","layout-side-right lay-106","layout-classic lay-107","layout-classic lay-108","layout-classic lay-109","layout-minimal lay-110","layout-classic lay-111","layout-side-right lay-112","layout-classic lay-113","layout-classic lay-114","layout-artistic lay-115","layout-geo lay-116","layout-classic lay-117"];
  for (let i=0; i<nomesLay.length; i++) layHTML += `<option value="${layClasses[i]}">${nomesLay[i]}</option>`; $('selLayout').innerHTML = layHTML;
  let stHTML = `<option value="var-0">0. Padrão</option><option value="var-1">1. Bloco Suave</option><option value="var-2">2. Borda Lateral</option><option value="var-3">3. Centrado Duplo</option><option value="var-4">4. Sublinhado</option>`;
  const nomesStyle = ["5.2 Linhas Center","6.Bloco Arredondado","7.Tracejado","8.Pontilhado","9.Bordas Duplas","10.Small-Caps","11.Bloco Sec","12.Sombra Linha","13.Contorno","14.Sombra Sólida","15.Borda Grossa","16.Gradiente","17.Alinhado Dir","18.Linha Acima","19.Linha Dir","20.2 Linhas Lat","21.Dupla Abaixo","22.Borda Traç","23.Lettering","24.Sub Grosso","25.Invertido","26.Itálico Suave","27.1ªMaiúsc","28.Tag Lateral","29.Acima e Abaixo","30.Topo Grosso","31.Recuado","32.Contorno Vazado","33.Marca-texto","34.Esq+Inf","35.Esq Arredond","36.Fundo Escuro","37.Inf Grossa","38.Pílula Center","39.Traç Duplo","40.Gradiente Sólido","41.Sombra Dupla","42.Sub Deslocado","43.Marcador Quad","44.Barras Dir","45.Cartão Sombra","46.Ultra Fina Center","47.Pont Duplo","48.Fita Cortada","49.Sec+Cor Top","50.Bolinha","51.Linha Fina","52.Título Cortante","53.Enquadrado","54.Gigante Traç","55.Aba Pasta","56.Linha Suspensa","57.Sombra Esq","58.Seta Moderna","59.Tarja Trans","60.Elegante","61.Caixa Fina","62.Citação Itálico","63.Dupla Dir","64.Plaqueta Geom","65.Gradiente Texto","66.Barra Esq Premium","67.Itálico Bold","68.Seta Direita","69.Fino Espaçado","70.Pílula Borda","71.Bold Grossa","72.Traço Esq","73.Traço Dir","74.Hachurado","75.Bolinha Ring","76.Dark Block","77.Topo+Base","78.Underline Deco","79.Conic Gradient","80.Borda Gradiente","81.Sub Parcial","82.Aba Acima","83.Ultra Light","84.Banner Esq","85.Dupla Grossa","86.Box Shadow","87.Card Rounded","88.Stroke Fino","89.Linha Fade","90.Fita Reta","91.Playfair Itálico","92.Linha Topo2","93.Zebra","94.Block Icon","95.Espaçado XL","96.Diamond","97.Fundo Fade","98.Pixel Retro","99.Borda Inferior Grad","100.Mega Bold","101.Split Center","102.Invertido Dir","103.Dupla Esq","104.Oswald Bold","105.Pílula Esq","106.Ultra Thin","107.Bicolor","108.Sub+Accent","109.Caixa Letra","110.Diagonal Lines","111.3D Shadow","112.Gradient Box","113.Frame L","114.Separadores"];
  for (let i=0; i<nomesStyle.length; i++) stHTML += `<option value="var-${i+5}">${nomesStyle[i]}</option>`; $('selStyle').innerHTML = stHTML;
  renderThemeGrid();
}

function autoZoom() {
  const paper = $('pdfPage'); const wrap = $('paperZoomWrap'); const preview = $('mainPreview');
  const isMobile = (window.visualViewport ? window.visualViewport.width : window.innerWidth) <= 1023;

  const previewRect = preview.getBoundingClientRect();
  const availWidth = Math.max(260, (previewRect.width || preview.clientWidth) - (isMobile ? 12 : 40));

  currentScale = Math.min(1, availWidth / 794);

  paper.style.transform = 'none';
  wrap.style.transform = `scale(${currentScale})`;
  wrap.style.transformOrigin = 'top left';
  wrap.style.width = '794px'; 

  const baseH = Math.max(paper.scrollHeight || 0, 1123);
  const scaledHeight = Math.ceil(baseH * currentScale);
  const scaledWidth = Math.ceil(794 * currentScale);

  wrap.parentElement.style.minWidth = scaledWidth + 'px';
  wrap.style.marginBottom = `${scaledHeight - baseH}px`;
  
  if (marginGuideVisible) {
    const overlay = $('marginGuideOverlay');
    if (overlay) {
      const mv = document.documentElement.style.getPropertyValue('--cv-margin') || '40px';
      overlay.style.top = mv; overlay.style.left = mv;
      overlay.style.right = mv; overlay.style.bottom = mv;
      overlay.setAttribute('data-margin', mv);
    }
  }
  updateDownloadButtonState();
}

window.addEventListener('resize', autoZoom);

initSelects(); loadData();
