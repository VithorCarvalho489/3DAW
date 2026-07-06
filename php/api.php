<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'salao_beleza');

function getDB(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
        if ($conn->connect_error) {
            die(json_encode(['error' => 'Erro de conexão: ' . $conn->connect_error]));
        }
        setupDatabase($conn);
    }
    return $conn;
}

function setupDatabase(mysqli $conn): void {
    $conn->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db(DB_NAME);

    $conn->query("
        CREATE TABLE IF NOT EXISTS usuarios (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            nome        VARCHAR(120)        NOT NULL,
            email       VARCHAR(180)        NOT NULL UNIQUE,
            senha_hash  VARCHAR(255)        NOT NULL,
            telefone    VARCHAR(20),
            tipo        ENUM('cliente','admin') DEFAULT 'cliente',
            criado_em   TIMESTAMP           DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS cabeleireiros (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            nome        VARCHAR(120)        NOT NULL,
            especialidade VARCHAR(200),
            foto_url    VARCHAR(300),
            ativo       TINYINT(1)          DEFAULT 1,
            criado_em   TIMESTAMP           DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS tipos_cabelo (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            nome        VARCHAR(80)         NOT NULL,
            descricao   TEXT
        ) ENGINE=InnoDB
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS servicos (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            nome            VARCHAR(120)    NOT NULL,
            descricao       TEXT,
            preco           DECIMAL(8,2)    NOT NULL,
            duracao_min     INT             NOT NULL,
            tipo_cabelo_id  INT,
            ativo           TINYINT(1)      DEFAULT 1,
            FOREIGN KEY (tipo_cabelo_id) REFERENCES tipos_cabelo(id) ON DELETE SET NULL
        ) ENGINE=InnoDB
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS favoritos (
            usuario_id      INT NOT NULL,
            cabeleireiro_id INT NOT NULL,
            PRIMARY KEY (usuario_id, cabeleireiro_id),
            FOREIGN KEY (usuario_id)      REFERENCES usuarios(id)      ON DELETE CASCADE,
            FOREIGN KEY (cabeleireiro_id) REFERENCES cabeleireiros(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS agendamentos (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id      INT             NOT NULL,
            cabeleireiro_id INT             NOT NULL,
            servico_id      INT             NOT NULL,
            data_hora       DATETIME        NOT NULL,
            status          ENUM('pendente','confirmado','cancelado','concluido') DEFAULT 'pendente',
            observacoes     TEXT,
            criado_em       TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id)      REFERENCES usuarios(id)      ON DELETE CASCADE,
            FOREIGN KEY (cabeleireiro_id) REFERENCES cabeleireiros(id) ON DELETE CASCADE,
            FOREIGN KEY (servico_id)      REFERENCES servicos(id)      ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS disponibilidade (
            id              INT AUTO_INCREMENT PRIMARY KEY,
            cabeleireiro_id INT NOT NULL,
            dia_semana      TINYINT NOT NULL,
            hora_inicio     TIME NOT NULL,
            hora_fim        TIME NOT NULL,
            FOREIGN KEY (cabeleireiro_id) REFERENCES cabeleireiros(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    $row = $conn->query("SELECT COUNT(*) as n FROM usuarios")->fetch_assoc();
    if ((int)$row['n'] === 0) {
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        $conn->query("INSERT INTO usuarios (nome, email, senha_hash, tipo) VALUES ('Administrador', 'admin@salao.com', '$hash', 'admin')");

        $conn->query("INSERT INTO tipos_cabelo (nome, descricao) VALUES
            ('Liso',    'Cabelo naturalmente liso ou quimicamente alisado'),
            ('Ondulado','Apresenta ondas suaves e volume médio'),
            ('Cacheado','Cachos definidos com bastante volume'),
            ('Crespo',  'Fios muito enrolados e com alto volume')
        ");

        $conn->query("INSERT INTO cabeleireiros (nome, especialidade) VALUES
            ('Amanda Lins',   'Cortes femininos e coloração'),
            ('Carlos Duarte', 'Cortes masculinos e barba'),
            ('Fernanda Melo', 'Tratamentos capilares e escova'),
            ('Rodrigo Silva', 'Coloração e mechas')
        ");

        $conn->query("INSERT INTO servicos (nome, descricao, preco, duracao_min, tipo_cabelo_id) VALUES
            ('Corte Feminino',    'Corte clássico ou moderno',             80.00,  60, NULL),
            ('Corte Masculino',   'Corte social ou degradê',               50.00,  40, NULL),
            ('Escova Progressiva','Alisamento de longa duração',          250.00, 180, 3),
            ('Coloração Completa','Tintura em toda a extensão do cabelo', 180.00, 120, NULL),
            ('Mechas e Luzes',    'Técnicas de iluminação dos fios',      200.00, 150, NULL),
            ('Hidratação',        'Máscara de reconstrução capilar',       90.00,  60, NULL),
            ('Manicure',          'Unhas das mãos',                        45.00,  50, NULL),
            ('Pedicure',          'Unhas dos pés',                         55.00,  60, NULL)
        ");

        for ($cab = 1; $cab <= 4; $cab++) {
            for ($dia = 1; $dia <= 5; $dia++) {
                $conn->query("INSERT INTO disponibilidade (cabeleireiro_id, dia_semana, hora_inicio, hora_fim)
                    VALUES ($cab, $dia, '08:00', '18:00')");
            }
            $conn->query("INSERT INTO disponibilidade (cabeleireiro_id, dia_semana, hora_inicio, hora_fim)
                VALUES ($cab, 6, '08:00', '14:00')");
        }
    }
}

function diaSemanaValido(string $data): bool {
    $d = DateTime::createFromFormat('Y-m-d', $data);
    return $d && $d->format('Y-m-d') === $data;
}

session_start();

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

$authActions = ['login', 'cadastro'];
$apiActions  = [
    'listar_tipos_cabelo', 'listar_servicos', 'listar_cabeleireiros', 'toggle_favorito',
    'listar_horarios', 'criar_agendamento', 'listar_meus_agendamentos', 'cancelar_agendamento'
];

if (isset($_POST['action']) && in_array($_POST['action'], $authActions, true)) {
    header('Content-Type: application/json');
    $db = getDB();

    if ($_POST['action'] === 'login') {
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if (!$email || !$senha) {
            echo json_encode(['success' => false, 'msg' => 'Preencha todos os campos.']);
            exit;
        }

        $stmt = $db->prepare("SELECT id, nome, senha_hash, tipo FROM usuarios WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($senha, $user['senha_hash'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_nome'] = $user['nome'];
            $_SESSION['user_tipo'] = $user['tipo'];
            echo json_encode(['success' => true, 'nome' => $user['nome'], 'tipo' => $user['tipo']]);
        } else {
            echo json_encode(['success' => false, 'msg' => 'E-mail ou senha inválidos.']);
        }
        exit;
    }

    if ($_POST['action'] === 'cadastro') {
        $nome     = trim($_POST['nome']     ?? '');
        $email    = trim($_POST['email']    ?? '');
        $senha    = $_POST['senha']         ?? '';
        $telefone = trim($_POST['telefone'] ?? '');

        if (!$nome || !$email || !$senha) {
            echo json_encode(['success' => false, 'msg' => 'Preencha nome, e-mail e senha.']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'msg' => 'E-mail inválido.']);
            exit;
        }
        if (strlen($senha) < 6) {
            echo json_encode(['success' => false, 'msg' => 'A senha deve ter ao menos 6 caracteres.']);
            exit;
        }

        $check = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
        $check->bind_param('s', $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'msg' => 'Este e-mail já está cadastrado.']);
            exit;
        }

        $hash = password_hash($senha, PASSWORD_BCRYPT);
        $ins  = $db->prepare("INSERT INTO usuarios (nome, email, senha_hash, telefone) VALUES (?, ?, ?, ?)");
        $ins->bind_param('ssss', $nome, $email, $hash, $telefone);
        $ins->execute();

        echo json_encode(['success' => true, 'msg' => 'Cadastro realizado! Faça login.']);
        exit;
    }
}

$acaoRecebida = $_POST['action'] ?? $_GET['action'] ?? '';
if ($acaoRecebida !== '' && in_array($acaoRecebida, $apiActions, true)) {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'msg' => 'Sessão expirada. Faça login novamente.']);
        exit;
    }

    $usuarioId = (int)$_SESSION['user_id'];
    $db = getDB();

    switch ($acaoRecebida) {

        case 'listar_tipos_cabelo': {
            $res = $db->query("SELECT id, nome, descricao FROM tipos_cabelo ORDER BY nome");
            echo json_encode(['success' => true, 'dados' => $res->fetch_all(MYSQLI_ASSOC)]);
            break;
        }

        case 'listar_servicos': {
            $tipoCabeloId = isset($_GET['tipo_cabelo_id']) ? (int)$_GET['tipo_cabelo_id'] : 0;
            if ($tipoCabeloId > 0) {
                $stmt = $db->prepare("SELECT id, nome, descricao, preco, duracao_min, tipo_cabelo_id FROM servicos WHERE ativo = 1 AND (tipo_cabelo_id = ? OR tipo_cabelo_id IS NULL) ORDER BY nome");
                $stmt->bind_param('i', $tipoCabeloId);
                $stmt->execute();
                $res = $stmt->get_result();
            } else {
                $res = $db->query("SELECT id, nome, descricao, preco, duracao_min, tipo_cabelo_id FROM servicos WHERE ativo = 1 ORDER BY nome");
            }
            echo json_encode(['success' => true, 'dados' => $res->fetch_all(MYSQLI_ASSOC)]);
            break;
        }

        case 'listar_cabeleireiros': {
            $stmt = $db->prepare("
                SELECT c.id, c.nome, c.especialidade, c.foto_url,
                       EXISTS(SELECT 1 FROM favoritos f WHERE f.usuario_id = ? AND f.cabeleireiro_id = c.id) AS favorito
                FROM cabeleireiros c
                WHERE c.ativo = 1
                ORDER BY favorito DESC, c.nome
            ");
            $stmt->bind_param('i', $usuarioId);
            $stmt->execute();
            $res = $stmt->get_result();
            echo json_encode(['success' => true, 'dados' => $res->fetch_all(MYSQLI_ASSOC)]);
            break;
        }

        case 'toggle_favorito': {
            $cabeleireiroId = (int)($_POST['cabeleireiro_id'] ?? 0);
            if (!$cabeleireiroId) {
                echo json_encode(['success' => false, 'msg' => 'Profissional inválido.']);
                break;
            }
            $stmt = $db->prepare("SELECT 1 FROM favoritos WHERE usuario_id = ? AND cabeleireiro_id = ?");
            $stmt->bind_param('ii', $usuarioId, $cabeleireiroId);
            $stmt->execute();
            $existe = $stmt->get_result()->num_rows > 0;

            if ($existe) {
                $del = $db->prepare("DELETE FROM favoritos WHERE usuario_id = ? AND cabeleireiro_id = ?");
                $del->bind_param('ii', $usuarioId, $cabeleireiroId);
                $del->execute();
                echo json_encode(['success' => true, 'favorito' => false]);
            } else {
                $ins = $db->prepare("INSERT INTO favoritos (usuario_id, cabeleireiro_id) VALUES (?, ?)");
                $ins->bind_param('ii', $usuarioId, $cabeleireiroId);
                $ins->execute();
                echo json_encode(['success' => true, 'favorito' => true]);
            }
            break;
        }

        case 'listar_horarios': {
            $cabeleireiroId = (int)($_GET['cabeleireiro_id'] ?? 0);
            $servicoId      = (int)($_GET['servico_id'] ?? 0);
            $data           = $_GET['data'] ?? '';

            if (!$cabeleireiroId || !$servicoId || !diaSemanaValido($data)) {
                echo json_encode(['success' => false, 'msg' => 'Dados inválidos para consulta de horários.']);
                break;
            }

            $hoje = new DateTime('today');
            $dataObj = DateTime::createFromFormat('Y-m-d', $data);
            if ($dataObj < $hoje) {
                echo json_encode(['success' => true, 'dados' => []]);
                break;
            }

            $stmtServico = $db->prepare("SELECT duracao_min FROM servicos WHERE id = ?");
            $stmtServico->bind_param('i', $servicoId);
            $stmtServico->execute();
            $servico = $stmtServico->get_result()->fetch_assoc();
            if (!$servico) {
                echo json_encode(['success' => false, 'msg' => 'Serviço não encontrado.']);
                break;
            }
            $duracao = (int)$servico['duracao_min'];

            $diaSemana = (int)$dataObj->format('w');
            $stmtDisp = $db->prepare("SELECT hora_inicio, hora_fim FROM disponibilidade WHERE cabeleireiro_id = ? AND dia_semana = ?");
            $stmtDisp->bind_param('ii', $cabeleireiroId, $diaSemana);
            $stmtDisp->execute();
            $blocos = $stmtDisp->get_result()->fetch_all(MYSQLI_ASSOC);

            $stmtOcup = $db->prepare("SELECT TIME(data_hora) AS hora FROM agendamentos WHERE cabeleireiro_id = ? AND DATE(data_hora) = ? AND status != 'cancelado'");
            $stmtOcup->bind_param('is', $cabeleireiroId, $data);
            $stmtOcup->execute();
            $ocupados = array_column($stmtOcup->get_result()->fetch_all(MYSQLI_ASSOC), 'hora');

            $agora = new DateTime();
            $ehHoje = $dataObj->format('Y-m-d') === $hoje->format('Y-m-d');

            $horarios = [];
            foreach ($blocos as $bloco) {
                $atual = DateTime::createFromFormat('H:i:s', $bloco['hora_inicio']);
                $fim   = DateTime::createFromFormat('H:i:s', $bloco['hora_fim']);
                while (true) {
                    $proximo = clone $atual;
                    $proximo->modify("+{$duracao} minutes");
                    if ($proximo > $fim) break;

                    $horaStr = $atual->format('H:i:s');
                    $disponivel = !in_array($horaStr, $ocupados);

                    if ($ehHoje) {
                        $candidato = DateTime::createFromFormat('Y-m-d H:i:s', $data . ' ' . $horaStr);
                        if ($candidato < $agora) $disponivel = false;
                    }

                    if ($disponivel) {
                        $horarios[] = $atual->format('H:i');
                    }
                    $atual = $proximo;
                }
            }

            sort($horarios);
            echo json_encode(['success' => true, 'dados' => $horarios]);
            break;
        }

        case 'criar_agendamento': {
            $cabeleireiroId = (int)($_POST['cabeleireiro_id'] ?? 0);
            $servicoId      = (int)($_POST['servico_id'] ?? 0);
            $data           = $_POST['data'] ?? '';
            $hora           = $_POST['hora'] ?? '';
            $observacoes    = trim($_POST['observacoes'] ?? '');

            if (!$cabeleireiroId || !$servicoId || !diaSemanaValido($data) || !preg_match('/^\d{2}:\d{2}$/', $hora)) {
                echo json_encode(['success' => false, 'msg' => 'Preencha todos os dados do agendamento.']);
                break;
            }

            $dataHora = $data . ' ' . $hora . ':00';

            $stmtCheck = $db->prepare("SELECT id FROM agendamentos WHERE cabeleireiro_id = ? AND data_hora = ? AND status != 'cancelado'");
            $stmtCheck->bind_param('is', $cabeleireiroId, $dataHora);
            $stmtCheck->execute();
            if ($stmtCheck->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'msg' => 'Este horário já foi reservado. Escolha outro.']);
                break;
            }

            $ins = $db->prepare("INSERT INTO agendamentos (usuario_id, cabeleireiro_id, servico_id, data_hora, observacoes) VALUES (?, ?, ?, ?, ?)");
            $ins->bind_param('iiiss', $usuarioId, $cabeleireiroId, $servicoId, $dataHora, $observacoes);

            if ($ins->execute()) {
                echo json_encode(['success' => true, 'msg' => 'Agendamento realizado com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'msg' => 'Não foi possível concluir o agendamento.']);
            }
            break;
        }

        case 'listar_meus_agendamentos': {
            $stmt = $db->prepare("
                SELECT a.id, a.data_hora, a.status, a.observacoes,
                       s.nome AS servico_nome, s.preco, s.duracao_min,
                       c.nome AS cabeleireiro_nome
                FROM agendamentos a
                JOIN servicos s      ON s.id = a.servico_id
                JOIN cabeleireiros c ON c.id = a.cabeleireiro_id
                WHERE a.usuario_id = ?
                ORDER BY a.data_hora DESC
            ");
            $stmt->bind_param('i', $usuarioId);
            $stmt->execute();
            echo json_encode(['success' => true, 'dados' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
            break;
        }

        case 'cancelar_agendamento': {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $db->prepare("UPDATE agendamentos SET status = 'cancelado' WHERE id = ? AND usuario_id = ? AND status != 'concluido'");
            $stmt->bind_param('ii', $id, $usuarioId);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'msg' => 'Agendamento cancelado.']);
            } else {
                echo json_encode(['success' => false, 'msg' => 'Não foi possível cancelar este agendamento.']);
            }
            break;
        }
    }
    exit;
}
