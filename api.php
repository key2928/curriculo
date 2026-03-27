<?php
// =================================================================
//  KONEX CREATIVE — API BACKEND (BLINDADA E 100% SEGURA)
// =================================================================
header('Content-Type: application/json; charset=utf-8');
$allowed_origins = ['https://iubsites.com', 'https://www.iubsites.com'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 1. CONFIGURAÇÕES DE CONEXÃO
define('ADMIN_PASS', 'konex2026'); 

$db_host = 'localhost';
$db_name = 'iubsit15_konex'; 
$db_user = 'iubsit15_konexuser'; 
$db_pass = '@Vanvan123';

function jsonResponse($data) { 
    echo json_encode($data); 
    exit; 
}

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    jsonResponse(['status' => 'erro', 'msg' => 'Erro de conexão no Banco de Dados.']);
}

if (isset($_GET['acao']) && strpos($_GET['acao'], 'webhook') !== false) {
    $acao = $_GET['acao'];
    $request = $_GET;
} else {
    $inputJSON = file_get_contents('php://input');
    $request = json_decode($inputJSON, true) ?: $_POST;
    $acao = $request['acao'] ?? '';
}

if (!$acao) {
    jsonResponse(['status' => 'erro', 'msg' => 'Ação não especificada na requisição.']);
}

function getConfig($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT valor FROM configuracoes WHERE chave = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    } catch(Exception $e) {
        return $default;
    }
}

// 2. ROTEAMENTO DE AÇÕES COM BLINDAGEM TOTAL (TRY/CATCH)
try {
    switch ($acao) {
        
        // ==========================================
        //  AÇÕES DO CLIENTE (LOJA E LOGIN)
        // ==========================================
        case 'login':
        case 'validar':
        case 'verificar':
            $email = $request['email'] ?? '';
            $senha = $request['senha'] ?? '';
            
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && ($acao === 'verificar' || password_verify($senha, $user['senha_hash']))) {
                if (isset($user['ativo']) && $user['ativo'] == 0) {
                    jsonResponse(['status' => 'erro', 'msg' => 'Esta conta foi desativada pelo administrador.']);
                }
                
                jsonResponse([
                    'status' => 'sucesso', 
                    'id' => (int)$user['id'],
                    'nome' => $user['nome'] ?? '',
                    'email' => $user['email'],
                    'creditos' => (int)($user['creditos'] ?? 0),
                    'cpf' => $user['cpf'] ?? '',
                    'plano' => $user['plano'] ?? 'avulso'
                ]);
            }
            jsonResponse(['status' => 'erro', 'msg' => 'Acesso negado. Verifique seu e-mail ou senha.']);
            break;

        case 'registrar':
            $email = trim($request['email'] ?? '');
            $senha = $request['senha'] ?? '';
            $nome = trim($request['nome'] ?? '');
            $cpf = preg_replace('/[^0-9]/', '', $request['cpf'] ?? '');

            if (empty($nome) || empty($email) || empty($senha)) {
                jsonResponse(['status' => 'erro', 'msg' => 'Todos os campos (Nome, E-mail e Senha) são obrigatórios para criar a conta.']);
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                jsonResponse(['status' => 'erro', 'msg' => 'Email inválido.']);
            }

            if (!empty($cpf)) {
                $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? OR cpf = ?");
                $check->execute([$email, $cpf]);
            } else {
                $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
                $check->execute([$email]);
            }
            
            if ($check->fetch()) {
                jsonResponse(['status' => 'erro', 'msg' => 'Este E-mail já possui uma conta no sistema. Faça login.']);
            }

            $creditosIniciais = 0;
            if (getConfig('credito_gratis_ativo', '1') === '1') {
                $creditosIniciais = (int)getConfig('credito_gratis_qtd', 1);
            }

            $stmt = $pdo->prepare("INSERT INTO usuarios (email, senha_hash, nome, cpf, creditos, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$email, password_hash($senha, PASSWORD_DEFAULT), $nome, $cpf, $creditosIniciais]);
            
            jsonResponse(['status' => 'sucesso', 'msg' => 'Sua conta foi criada com sucesso!']);
            break;

        case 'atualizar_cpf':
            $uid = $request['user_id'] ?? 0;
            $cpf = preg_replace('/[^0-9]/', '', $request['cpf'] ?? '');
            if (!$uid) jsonResponse(['status' => 'erro', 'msg' => 'Sua sessão expirou. Faça login novamente.']);
            $pdo->prepare("UPDATE usuarios SET cpf = ? WHERE id = ?")->execute([$cpf, $uid]);
            jsonResponse(['status' => 'sucesso', 'msg' => 'Seu CPF foi atualizado com sucesso!']);
            break;

        case 'consumir':
            $email = $request['email'] ?? '';
            $stmt = $pdo->prepare("SELECT id, creditos FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $u = $stmt->fetch();
            
            if(!$u || $u['creditos'] <= 0) jsonResponse(['status' => 'erro', 'msg' => 'Você não possui créditos. Compre um plano na Loja.']);
            
            $pdo->prepare("UPDATE usuarios SET creditos = creditos - 1 WHERE id = ?")->execute([$u['id']]);
            try {
                $pdo->prepare("INSERT INTO transacoes (usuario_id, tipo, quantidade, descricao, created_at) VALUES (?, 'consumo', -1, 'Geração de PDF Oficial Konex', NOW())")->execute([$u['id']]);
            } catch (Exception $e) {}
            
            jsonResponse(['status' => 'sucesso', 'creditos' => $u['creditos'] - 1]);
            break;

        // ==========================================
        //  INTELIGÊNCIA ARTIFICIAL (IA GEMINI)
        // ==========================================
        case 'ai_parse_resume':
        case 'ai_generate_texts':
        case 'ai_generate_theme':
            if (getConfig('llm_enabled', '1') === '0') {
                jsonResponse(['status' => 'erro', 'msg' => 'A Inteligência Artificial está temporariamente desativada no sistema.']);
            }
            
            $key = getConfig('llm_api_key');
            if (empty($key)) {
                jsonResponse(['status' => 'erro', 'msg' => 'A API Key da IA não foi configurada pelo Administrador.']);
            }

            $prompt = "";
            if ($acao === 'ai_parse_resume') {
                $prompt = "Extraia os dados deste currículo e retorne APENAS um JSON válido sem marcações markdown. Chaves exigidas: nome, email, telefone, linkedin, cidade, estado_civil, nascimento, objetivo, resumo, habilidades (array), idiomas (array), experiencias (array de objetos contendo cargo, empresa, periodo, descricao), formacao (array de objetos contendo curso, instituicao, periodo, descricao). Texto base para ler: " . $request['texto'];
            } elseif ($acao === 'ai_generate_texts') {
                $prompt = "Aja como um Recrutador Sênior (ATS Ready). O candidato busca a vaga de: {$request['vaga']}. Sua experiência real é: {$request['experiencia']}. Retorne APENAS um JSON válido sem marcações markdown contendo: objetivo (string curta focada na vaga), resumo (parágrafo altamente persuasivo evidenciando impacto), skills (array com 6 a 8 palavras-chave e hard skills da área), bullets_experiencia (array de strings descrevendo os resultados dele como tópicos fortes focados em métricas).";
            } elseif ($acao === 'ai_generate_theme') {
                $prompt = "Aja como um Especialista em UI/UX de Currículos. O candidato trabalha na área: {$request['area']} e deseja transmitir uma imagem: {$request['vibe']}. Retorne APENAS um JSON válido sem marcações markdown com as chaves de estilo: layout (uma entre: layout-classic lay-8, layout-side-left lay-12, layout-side-right lay-14, layout-geo lay-13, layout-artistic lay-22, layout-minimal lay-26, layout-boxed lay-11), style (uma entre: var-1 até var-9), font (exemplo: 'Inter', sans-serif), color (cor hexadecimal principal), sec (cor hexadecimal secundária de fundo), fontCol (cor hexadecimal do texto escuro), sideCol (cor hexadecimal para texto na lateral, dependendo se o fundo pede fonte branca ou escura para contraste alto).";
            }

            $model = getConfig('llm_model', 'gemini-2.0-flash');
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";
            
            $data = [
                "contents" => [["parts" => [["text" => $prompt]]]],
                "generationConfig" => ["responseMimeType" => "application/json"]
            ];
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $json = json_decode($res, true);
            if ($httpCode !== 200 || isset($json['error'])) {
                $msg = $json['error']['message'] ?? 'Erro desconhecido na comunicação com a API do Google Gemini.';
                if (strpos(strtolower($msg), 'quota') !== false) $msg = 'Sua chave do Gemini atingiu o limite gratuito de consultas.';
                if (strpos(strtolower($msg), 'api key not valid') !== false) $msg = 'A Chave da API informada no painel é inválida.';
                jsonResponse(['status' => 'erro', 'msg' => $msg]);
            }
            
            $respText = $json['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            $respData = json_decode($respText, true);
            
            if (!$respData) {
                jsonResponse(['status' => 'erro', 'msg' => 'A Inteligência Artificial retornou um formato inesperado. Tente novamente.']);
            }
            
            jsonResponse(['status' => 'sucesso', 'result' => $respData]);
            break;

        // ==========================================
        //  PAGAMENTOS E LOJA (GERAÇÃO E VERIFICAÇÃO ATIVA)
        // ==========================================
        case 'get_precos':
            jsonResponse([
                'status'                  => 'sucesso',
                'valor_basico'            => getConfig('valor_basico', '9.90'),
                'valor_profissional'      => getConfig('valor_profissional', '19.90'),
                'valor_agencia'           => getConfig('valor_agencia', '39.90'),
                'creditos_basico'         => getConfig('creditos_basico', '1'),
                'creditos_profissional'   => getConfig('creditos_profissional', '3'),
                'creditos_agencia'        => getConfig('creditos_agencia', '10'),
                'nome_plano_basico'       => getConfig('nome_plano_basico', 'Básico'),
                'nome_plano_profissional' => getConfig('nome_plano_profissional', 'Profissional'),
                'nome_plano_agencia'      => getConfig('nome_plano_agencia', 'Agência'),
                'desc_plano_basico'       => getConfig('desc_plano_basico', ''),
                'desc_plano_profissional' => getConfig('desc_plano_profissional', ''),
                'desc_plano_agencia'      => getConfig('desc_plano_agencia', '')
            ]);
            break;

        case 'gerar_pagamento':
            $email = $request['email'] ?? '';
            $plano = $request['plano'] ?? 'basico'; 
            
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if(!$user) jsonResponse(['status' => 'erro', 'msg' => 'Usuário não encontrado.']);
            
            $valor = (float)getConfig("valor_{$plano}", 9.90);
            $creditos = (int)getConfig("creditos_{$plano}", 1);
            $gateway = getConfig('gateway_ativo', 'mercadopago');
            
            $stmt = $pdo->prepare("INSERT INTO pedidos (email, plano, creditos, valor, status, created_at) VALUES (?, ?, ?, ?, 'pendente', NOW())");
            $stmt->execute([$email, $plano, $creditos, $valor]);
            $pedido_id = $pdo->lastInsertId();
            
            if($gateway === 'mercadopago') {
                $token = getConfig('mp_access_token');
                if(!$token) jsonResponse(['status' => 'erro', 'msg' => 'Access Token do Mercado Pago não configurado.']);
                
                $data = [
                    'items' => [[ 'title' => "Plano $plano - Konex", 'quantity' => 1, 'unit_price' => $valor ]],
                    'payer' => [ 'email' => $email ],
                    'external_reference' => "KNX_" . $pedido_id, // TOTALMENTE PROTEGIDO E BLINDADO
                    'back_urls' => [ 'success' => getConfig('site_url') ]
                ];
                
                $ch = curl_init('https://api.mercadopago.com/checkout/preferences');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Content-Type: application/json']);
                $res = json_decode(curl_exec($ch), true);
                curl_close($ch);
                
                if(isset($res['init_point'])) {
                    $pdo->prepare("UPDATE pedidos SET gateway_id = ? WHERE id = ?")->execute([$res['id'], $pedido_id]);
                    jsonResponse(['status' => 'sucesso', 'link' => $res['init_point'], 'pedido_id' => $pedido_id]);
                } else {
                    jsonResponse(['status' => 'erro', 'msg' => 'O Mercado Pago recusou a geração do pagamento.']);
                }
            } 
            elseif ($gateway === 'asaas') {
                $asaas_key = getConfig('asaas_api_key');
                $ambiente = getConfig('asaas_ambiente', 'sandbox');
                $base_url = $ambiente === 'producao' ? 'https://api.asaas.com/v3' : 'https://api-sandbox.asaas.com/v3';
                
                if(!$asaas_key) jsonResponse(['status' => 'erro', 'msg' => 'API Key do Asaas não configurada.']);
                
                $cpf_envio = preg_replace('/[^0-9]/', '', $user['cpf'] ?? '');
                
                if (!empty($cpf_envio)) {
                    $ch = curl_init("$base_url/customers?cpfCnpj={$cpf_envio}");
                } else {
                    $ch = curl_init("$base_url/customers?email={$email}");
                }
                curl_setopt($ch, CURLOPT_HTTPHEADER, ["access_token: $asaas_key"]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $cust_res = json_decode(curl_exec($ch), true);
                curl_close($ch);
                $customer_id = $cust_res['data'][0]['id'] ?? null;
                
                if (!$customer_id) {
                    $ch = curl_init("$base_url/customers");
                    curl_setopt($ch, CURLOPT_POST, true);
                    $nomeCliente = !empty($user['nome']) ? $user['nome'] : 'Cliente Konex';
                    
                    $dadosCliente = [
                        'name' => $nomeCliente, 
                        'email' => $email
                    ];
                    if (!empty($cpf_envio)) {
                        $dadosCliente['cpfCnpj'] = $cpf_envio;
                    }

                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dadosCliente));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ["access_token: $asaas_key", "Content-Type: application/json"]);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $cust_res = json_decode(curl_exec($ch), true);
                    curl_close($ch);
                    $customer_id = $cust_res['id'] ?? null;
                }
                
                if (!$customer_id) {
                    $erroAsaas = $cust_res['errors'][0]['description'] ?? 'Erro ao criar cliente no Asaas.';
                    jsonResponse(['status' => 'erro', 'msg' => 'Asaas recusou: ' . $erroAsaas]);
                }
                
                $pay_data = [
                    'customer' => $customer_id,
                    'billingType' => 'PIX',
                    'value' => $valor,
                    'dueDate' => date('Y-m-d', strtotime('+1 day')),
                    'description' => "Créditos Currículo ($plano) - Konex",
                    'externalReference' => (string)$pedido_id
                ];
                $ch = curl_init("$base_url/payments");
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($pay_data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ["access_token: $asaas_key", "Content-Type: application/json"]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $pay_res = json_decode(curl_exec($ch), true);
                curl_close($ch);
                $payment_id = $pay_res['id'] ?? null;
                
                if (!$payment_id) {
                    $erroAsaasPay = $pay_res['errors'][0]['description'] ?? 'Desconhecido';
                    jsonResponse(['status' => 'erro', 'msg' => 'Asaas falhou ao gerar PIX: ' . $erroAsaasPay]);
                }
                
                $pdo->prepare("UPDATE pedidos SET gateway_id = ? WHERE id = ?")->execute([$payment_id, $pedido_id]);
                
                $ch = curl_init("$base_url/payments/$payment_id/pixQrCode");
                curl_setopt($ch, CURLOPT_HTTPHEADER, ["access_token: $asaas_key"]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $qr_res = json_decode(curl_exec($ch), true);
                curl_close($ch);
                
                if(isset($qr_res['payload'])) {
                    jsonResponse([
                        'status' => 'sucesso', 
                        'gateway' => 'asaas',
                        'tipo' => 'pix',
                        'pix_copia_cola' => $qr_res['payload'], 
                        'pix_qr_base64' => $qr_res['encodedImage'],
                        'pedido_id' => $pedido_id
                    ]);
                } else {
                    jsonResponse(['status' => 'erro', 'msg' => 'Asaas: Falha ao obter o QR Code do PIX.']);
                }
            } else {
                jsonResponse(['status' => 'erro', 'msg' => 'Nenhum Gateway de pagamento foi configurado no painel Admin.']);
            }
            break;

        case 'verificar_pagamento':
            $pid = (int)($request['pedido_id'] ?? 0);
            
            // 1. Verifica localmente no banco
            $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
            $stmt->execute([$pid]);
            $pedido = $stmt->fetch();
            
            if (!$pedido) jsonResponse(['status' => 'nao_encontrado']);
            if ($pedido['status'] === 'aprovado') jsonResponse(['status' => 'aprovado', 'creditos' => $pedido['creditos']]);
            
            // 2. Se está pendente localmente, faz VERIFICAÇÃO ATIVA na API do Gateway
            $gateway = getConfig('gateway_ativo', 'mercadopago');
            $pago = false;
            
            try {
                if ($gateway === 'asaas' && !empty($pedido['gateway_id'])) {
                    $asaas_key = getConfig('asaas_api_key');
                    $ambiente = getConfig('asaas_ambiente', 'sandbox');
                    $base_url = $ambiente === 'producao' ? 'https://api.asaas.com/v3' : 'https://api-sandbox.asaas.com/v3';

                    $ch = curl_init("$base_url/payments/{$pedido['gateway_id']}");
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ["access_token: $asaas_key"]);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $pay_res = json_decode(curl_exec($ch), true);
                    curl_close($ch);

                    if (isset($pay_res['status']) && ($pay_res['status'] === 'RECEIVED' || $pay_res['status'] === 'CONFIRMED')) {
                        $pago = true;
                    }
                } 
                elseif ($gateway === 'mercadopago') {
                    $token = getConfig('mp_access_token');
                    
                    // SEM EXCEÇÃO: Pesquisa pelo prefixo de segurança e NÃO LIBERA 'pending'
                    $ch = curl_init("https://api.mercadopago.com/v1/payments/search?external_reference=KNX_{$pid}&sort=date_created&criteria=desc");
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $pay_res = json_decode(curl_exec($ch), true);
                    curl_close($ch);

                    // Fallback para pedidos do passado caso existam
                    if (empty($pay_res['results'])) {
                        $ch = curl_init("https://api.mercadopago.com/v1/payments/search?external_reference={$pid}&sort=date_created&criteria=desc");
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        $pay_res = json_decode(curl_exec($ch), true);
                        curl_close($ch);
                    }

                    if (isset($pay_res['results']) && count($pay_res['results']) > 0) {
                        foreach ($pay_res['results'] as $payment) {
                            if ($payment['status'] === 'approved') {
                                $pago = true;
                                $pedido['gateway_id'] = $payment['id']; 
                                break;
                            }
                        }
                    }
                }
            } catch (Exception $e) {}

            // 3. Se a API confirmou, aprova, dá os créditos e avisa o front
            if ($pago) {
                // Previne dupla entrega
                $stmt = $pdo->prepare("SELECT status FROM pedidos WHERE id = ?");
                $stmt->execute([$pid]);
                if ($stmt->fetchColumn() !== 'aprovado') {
                    $pdo->prepare("UPDATE pedidos SET status = 'aprovado', gateway_id = ? WHERE id = ?")->execute([$pedido['gateway_id'], $pid]);
                    $pdo->prepare("UPDATE usuarios SET creditos = creditos + ? WHERE email = ?")->execute([$pedido['creditos'], $pedido['email']]);

                    $stmtU = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
                    $stmtU->execute([$pedido['email']]);
                    $uid = $stmtU->fetchColumn();

                    if ($uid) {
                        try {
                            $pdo->prepare("INSERT INTO transacoes (usuario_id, tipo, quantidade, descricao, referencia, created_at) VALUES (?, 'compra', ?, ?, ?, NOW())")
                                ->execute([$uid, $pedido['creditos'], "Compra Automática via Polling ($gateway)", $pedido['gateway_id']]);
                        } catch (Exception $e) {}
                    }
                }
                jsonResponse(['status' => 'aprovado', 'creditos' => $pedido['creditos']]);
            }

            jsonResponse(['status' => 'pendente']);
            break;

        // ==========================================
        //  WEBHOOKS (MANTIDOS COMO BACKUP SEGURO)
        // ==========================================
        case 'webhook_mp':
            $inputJSON = file_get_contents('php://input');
            $data = json_decode($inputJSON, true) ?: [];
            
            $topic = $_GET['topic'] ?? $_GET['type'] ?? $data['action'] ?? $data['type'] ?? '';
            $id = $_GET['id'] ?? $_GET['data']['id'] ?? $data['data']['id'] ?? '';
            
            if(strpos($topic, 'payment') !== false && $id) {
                $token = getConfig('mp_access_token');
                $ch = curl_init("https://api.mercadopago.com/v1/payments/$id");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
                $payment = json_decode(curl_exec($ch), true);
                curl_close($ch);
                
                // SOMENTE APPROVED AQUI TAMBÉM - Função de imediato removida
                if(isset($payment['status']) && $payment['status'] === 'approved') {
                        
                    $pedido_id_raw = $payment['external_reference'];
                    $pedido_id = (int) str_replace("KNX_", "", $pedido_id_raw); 
                    
                    $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ? AND status != 'aprovado'");
                    $stmt->execute([$pedido_id]);
                    $pedido = $stmt->fetch();
                    
                    if($pedido) {
                        $pdo->prepare("UPDATE pedidos SET status = 'aprovado', gateway_id = ? WHERE id = ?")->execute([$id, $pedido_id]);
                        $pdo->prepare("UPDATE usuarios SET creditos = creditos + ? WHERE email = ?")->execute([$pedido['creditos'], $pedido['email']]);
                        
                        $stmtU = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
                        $stmtU->execute([$pedido['email']]);
                        $uid = $stmtU->fetchColumn();
                        
                        try {
                            $pdo->prepare("INSERT INTO transacoes (usuario_id, tipo, quantidade, descricao, referencia, created_at) VALUES (?, 'compra', ?, ?, ?, NOW())")
                                ->execute([$uid, $pedido['creditos'], "Compra Webhook MP (Plano {$pedido['plano']})", $id]);
                        } catch (Exception $e) {}
                    }
                }
            }
            http_response_code(200);
            echo "OK";
            exit;

        case 'webhook_asaas':
            $inputJSON = file_get_contents('php://input');
            $data = json_decode($inputJSON, true);
            
            if(isset($data['event']) && $data['event'] === 'PAYMENT_RECEIVED') {
                $payment_id = $data['payment']['id'];
                $pedido_id = $data['payment']['externalReference'];
                
                $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ? AND status != 'aprovado'");
                $stmt->execute([$pedido_id]);
                $pedido = $stmt->fetch();
                
                if($pedido) {
                    $pdo->prepare("UPDATE pedidos SET status = 'aprovado' WHERE id = ?")->execute([$pedido_id]);
                    $pdo->prepare("UPDATE usuarios SET creditos = creditos + ? WHERE email = ?")->execute([$pedido['creditos'], $pedido['email']]);
                    
                    $stmtU = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
                    $stmtU->execute([$pedido['email']]);
                    $uid = $stmtU->fetchColumn();
                    
                    try {
                        $pdo->prepare("INSERT INTO transacoes (usuario_id, tipo, quantidade, descricao, referencia, created_at) VALUES (?, 'compra', ?, ?, ?, NOW())")
                            ->execute([$uid, $pedido['creditos'], "Compra Webhook Asaas (Plano {$pedido['plano']})", $payment_id]);
                    } catch (Exception $e) {}
                }
            }
            http_response_code(200);
            echo "OK";
            exit;

        // ==========================================
        //  AÇÕES DO ADMINISTRADOR (PAINEL COMPLETO)
        // ==========================================
        case 'admin_login':
            if (($request['senha'] ?? '') === ADMIN_PASS) {
                jsonResponse(['status' => 'sucesso', 'token' => bin2hex(random_bytes(32))]);
            }
            jsonResponse(['status' => 'erro', 'msg' => 'Senha incorreta.']);
            break;

        case 'admin_check':
            jsonResponse(['status' => 'sucesso']);
            break;

        case 'admin_stats':
            $total_u = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
            $pedidos = $pdo->query("SELECT SUM(valor) as receita, COUNT(*) as qtd FROM pedidos WHERE status = 'aprovado'")->fetch();
            $downloads = 0;
            try { $downloads = $pdo->query("SELECT COUNT(*) FROM transacoes WHERE tipo = 'consumo'")->fetchColumn(); } catch(Exception $e) {}
            
            $ultimos = $pdo->query("SELECT id, email, plano, valor, status, creditos, created_at FROM pedidos ORDER BY id DESC LIMIT 5")->fetchAll();
            jsonResponse([
                'status' => 'sucesso',
                'total_usuarios' => (int)$total_u,
                'total_downloads' => (int)$downloads,
                'receita_total' => (float)($pedidos['receita'] ?? 0),
                'total_pedidos' => (int)($pedidos['qtd'] ?? 0),
                'ultimos_pedidos' => $ultimos
            ]);
            break;

        case 'admin_usuarios':
            $busca = $request['busca'] ?? '';
            $sql = "SELECT * FROM usuarios";
            if (!empty($busca)) {
                $sql .= " WHERE email LIKE :b OR nome LIKE :b";
            }
            $sql .= " ORDER BY id DESC LIMIT 100";
            
            $stmt = $pdo->prepare($sql);
            if (!empty($busca)) {
                $stmt->bindValue(':b', "%$busca%");
            }
            $stmt->execute();
            $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $usuariosFormatados = [];
            foreach($res as $u) {
                $usuariosFormatados[] = [
                    'id' => $u['id'] ?? 0,
                    'email' => $u['email'] ?? '',
                    'nome' => $u['nome'] ?? '',
                    'cpf' => $u['cpf'] ?? '',
                    'creditos' => $u['creditos'] ?? 0,
                    'plano' => $u['plano'] ?? 'avulso',
                    'ativo' => $u['ativo'] ?? 1,
                    'created_at' => $u['created_at'] ?? ''
                ];
            }
            jsonResponse(['status' => 'sucesso', 'total' => count($usuariosFormatados), 'usuarios' => $usuariosFormatados]);
            break;
            
        case 'admin_toggle_usuario':
            $uid = (int)$request['usuario_id'];
            try { $pdo->prepare("UPDATE usuarios SET ativo = NOT ativo WHERE id = ?")->execute([$uid]); } catch(Exception $e) {}
            jsonResponse(['status' => 'sucesso']);
            break;
            
        case 'admin_delete_usuario':
            $uid = (int)$request['usuario_id'];
            try { $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$uid]); } catch(Exception $e) {}
            jsonResponse(['status' => 'sucesso']);
            break;

        case 'admin_add_usuario':
            $cpf = preg_replace('/[^0-9]/', '', $request['cpf'] ?? '');
            try {
                $pdo->prepare("INSERT INTO usuarios (email, senha_hash, nome, cpf, plano, creditos, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())")->execute([
                    $request['email'], password_hash($request['senha'], PASSWORD_DEFAULT), $request['nome'], $cpf,
                    $request['plano'] ?? 'avulso', (int)($request['creditos'] ?? 0)
                ]);
            } catch(Exception $e) {}
            jsonResponse(['status' => 'sucesso']);
            break;

        case 'admin_edit_usuario':
            $cpf = preg_replace('/[^0-9]/', '', $request['cpf'] ?? '');
            try {
                $pdo->prepare("UPDATE usuarios SET nome=?, email=?, cpf=?, plano=? WHERE id=?")->execute([
                    $request['nome'], $request['email'], $cpf, $request['plano'], $request['usuario_id']
                ]);
                if (!empty($request['senha'])) {
                    $pdo->prepare("UPDATE usuarios SET senha_hash=? WHERE id=?")->execute([password_hash($request['senha'], PASSWORD_DEFAULT), $request['usuario_id']]);
                }
            } catch(Exception $e) {}
            jsonResponse(['status' => 'sucesso']);
            break;

        case 'admin_force_password':
            try { $pdo->prepare("UPDATE usuarios SET senha_hash=? WHERE id=?")->execute([password_hash($request['senha'], PASSWORD_DEFAULT), $request['usuario_id']]); } catch(Exception $e) {}
            jsonResponse(['status' => 'sucesso', 'msg' => 'Senha alterada com sucesso.']);
            break;
            
        case 'admin_add_creditos':
            $uid = $request['usuario_id'];
            $qtd = (int)$request['quantidade'];
            try {
                $pdo->prepare("UPDATE usuarios SET creditos = creditos + ? WHERE id = ?")->execute([$qtd, $uid]);
                $pdo->prepare("INSERT INTO transacoes (usuario_id, tipo, quantidade, descricao, created_at) VALUES (?, 'manual', ?, 'Ajuste Admin', NOW())")->execute([$uid, $qtd]);
            } catch (Exception $e) {}
            jsonResponse(['status' => 'sucesso']);
            break;

        case 'admin_pedidos':
            $res = $pdo->query("SELECT * FROM pedidos ORDER BY id DESC LIMIT 100")->fetchAll();
            jsonResponse(['status' => 'sucesso', 'total' => count($res), 'pedidos' => $res]);
            break;
            
        case 'admin_limpar_pedidos':
            try { $pdo->query("TRUNCATE TABLE pedidos"); } catch(Exception $e) {}
            jsonResponse(['status' => 'sucesso', 'msg' => 'Todos os pedidos foram limpos.']);
            break;
            
        case 'admin_aprovar_pedido':
            $pid = (int)$request['pedido_id'];
            $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
            $stmt->execute([$pid]);
            $pedido = $stmt->fetch();
            if($pedido && $pedido['status'] !== 'aprovado') {
                $pdo->prepare("UPDATE pedidos SET status = 'aprovado' WHERE id = ?")->execute([$pid]);
                $pdo->prepare("UPDATE usuarios SET creditos = creditos + ? WHERE email = ?")->execute([$pedido['creditos'], $pedido['email']]);
                $uid = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
                $uid->execute([$pedido['email']]);
                $userId = $uid->fetchColumn();
                try {
                    $pdo->prepare("INSERT INTO transacoes (usuario_id, tipo, quantidade, descricao, created_at) VALUES (?, 'compra', ?, 'Aprovação manual do Pedido #$pid', NOW())")->execute([$userId, $pedido['creditos']]);
                } catch(Exception $e) {}
                jsonResponse(['status' => 'sucesso', 'msg' => 'Pedido aprovado e créditos liberados!']);
            }
            jsonResponse(['status' => 'erro', 'msg' => 'Pedido já aprovado ou não encontrado.']);
            break;
            
        case 'admin_delete_pedido_individual':
            $pid = (int)$request['pedido_id'];
            try { $pdo->prepare("DELETE FROM pedidos WHERE id = ?")->execute([$pid]); } catch(Exception $e) {}
            jsonResponse(['status' => 'sucesso']);
            break;

        case 'admin_transacoes':
            $res = [];
            try { $res = $pdo->query("SELECT t.*, u.email FROM transacoes t LEFT JOIN usuarios u ON t.usuario_id = u.id ORDER BY t.id DESC LIMIT 100")->fetchAll(); } catch (Exception $e) {}
            jsonResponse(['status' => 'sucesso', 'total' => count($res), 'transacoes' => $res]);
            break;
            
        case 'admin_limpar_transacoes':
            try { $pdo->query("TRUNCATE TABLE transacoes"); } catch(Exception $e) {}
            jsonResponse(['status' => 'sucesso', 'msg' => 'Histórico de transações limpo.']);
            break;
            
        case 'admin_delete_transacao_individual':
            $tid = (int)$request['transacao_id'];
            try { $pdo->prepare("DELETE FROM transacoes WHERE id = ?")->execute([$tid]); } catch(Exception $e) {}
            jsonResponse(['status' => 'sucesso']);
            break;
            
        case 'admin_financeiro':
            $de = $request['de'] ?? ''; $ate = $request['ate'] ?? '';
            $where = "WHERE status = 'aprovado'";
            $params = [];
            if ($de && $ate) { $where .= " AND DATE(created_at) BETWEEN ? AND ?"; $params[] = $de; $params[] = $ate; }
            
            $stmt = $pdo->prepare("SELECT SUM(valor) as receita, COUNT(*) as pedidos, SUM(creditos) as vendidos FROM pedidos $where");
            $stmt->execute($params);
            $fin = $stmt->fetch();
            
            $pdfs = 0;
            try {
                $whereT = "WHERE tipo = 'consumo'";
                if ($de && $ate) { $whereT .= " AND DATE(created_at) BETWEEN ? AND ?"; }
                $stmtT = $pdo->prepare("SELECT COUNT(*) FROM transacoes $whereT");
                $stmtT->execute($params);
                $pdfs = $stmtT->fetchColumn();
            } catch(Exception $e) {}
            
            $saldo = $pdo->query("SELECT SUM(creditos) FROM usuarios")->fetchColumn();
            
            $stmtS = $pdo->prepare("SELECT DATE(created_at) as dia, SUM(valor) as receita FROM pedidos $where GROUP BY DATE(created_at) ORDER BY dia ASC LIMIT 30");
            $stmtS->execute($params);
            
            jsonResponse([
                'status' => 'sucesso',
                'receita' => $fin['receita'] ?? 0, 'pedidos' => $fin['pedidos'] ?? 0, 'vendidos' => $fin['vendidos'] ?? 0,
                'ticket' => ($fin['pedidos'] > 0) ? ($fin['receita'] / $fin['pedidos']) : 0, 'pdfs' => $pdfs ?? 0, 'saldo' => $saldo ?? 0,
                'serie' => $stmtS->fetchAll()
            ]);
            break;
            
        case 'admin_fraude_stats':
            $ips = [];
            $bonus = 0;
            try {
                $limit = (int)getConfig('credito_gratis_limite_ip', 3);
                $ips = $pdo->query("SELECT ip_cadastro, COUNT(*) as total FROM usuarios WHERE ip_cadastro IS NOT NULL AND ip_cadastro != '' GROUP BY ip_cadastro HAVING total > $limit ORDER BY total DESC")->fetchAll();
            } catch(Exception $e) {}
            
            try {
                $bonus = $pdo->query("SELECT SUM(quantidade) FROM transacoes WHERE tipo = 'bonus'")->fetchColumn();
            } catch (Exception $e) {}
            
            jsonResponse(['status' => 'sucesso', 'ips_suspeitos' => $ips, 'total_bonus' => (int)$bonus]);
            break;
            
        case 'admin_delete_ip_suspeito':
            $ip = $request['ip'] ?? '';
            if ($ip) { try { $pdo->prepare("DELETE FROM usuarios WHERE ip_cadastro = ?")->execute([$ip]); } catch(Exception $e) {} }
            jsonResponse(['status' => 'sucesso']);
            break;

        case 'admin_get_configs':
            jsonResponse(['status' => 'sucesso', 'configs' => $pdo->query("SELECT chave, valor FROM configuracoes")->fetchAll(PDO::FETCH_KEY_PAIR)]);
            break;

        case 'admin_save_configs':
            $stmt = $pdo->prepare("INSERT INTO configuracoes (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
            foreach ($request['configs'] as $k => $v) { $stmt->execute([$k, $v]); }
            jsonResponse(['status' => 'sucesso']);
            break;

        default:
            jsonResponse(['status' => 'erro', 'msg' => "Ação '{$acao}' não implementada no servidor."]);
            break;
    }
} catch (\Throwable $e) {
    // PROTEÇÃO FINAL CONTRA CRASHES
    jsonResponse([
        'status' => 'erro', 
        'msg' => 'Houve uma falha de conexão interna. O servidor bloqueou a requisição para não expor a base de dados.'
    ]);
}
?>