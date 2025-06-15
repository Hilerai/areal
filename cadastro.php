<?php
require 'conexao.php';

$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $tipo = $_POST['tipo'];
    
    if (empty($nome) || empty($email) || empty($senha)) {
        $mensagem = 'Todos os campos são obrigatórios!';
        $tipo_mensagem = 'error';
    } else {
        $senha_hash = password_hash($senha, PASSWORD_BCRYPT);
        $sql = "INSERT INTO usuarios (nome, email, senha, tipo) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $nome, $email, $senha_hash, $tipo);
        
        if ($stmt->execute()) {
            $mensagem = 'Usuário cadastrado com sucesso! <a href="login.php">Ir para login</a>';
            $tipo_mensagem = 'success';
        } else {
            $mensagem = 'Erro ao cadastrar usuário. Tente novamente.';
            $tipo_mensagem = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - MTTORTATO</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container fade-in">
        <h2>👤 Cadastrar Usuário</h2>
        
        <?php if (!empty($mensagem)): ?>
            <div class="<?= $tipo_mensagem ?>">
                <?= $mensagem ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="nome">Nome Completo:</label>
                <input type="text" id="nome" name="nome" required placeholder="Digite seu nome completo">
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required placeholder="Digite seu email">
            </div>
            
            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" required placeholder="Digite sua senha">
            </div>
            
            <div class="form-group">
                <label for="tipo">Tipo de Usuário:</label>
                <select id="tipo" name="tipo" required>
                    <option value="">Selecione o tipo</option>
                    <option value="cliente">🏗️ Cliente</option>
                    <option value="gestora">👩‍💼 Gestora</option>
                    <option value="patio">🚛 Operador de Pátio</option>
                </select>
            </div>
            
            <button type="submit">📝 Cadastrar</button>
        </form>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="login.php">Já tem uma conta? Faça login</a>
        </div>
    </div>
</body>
</html>

