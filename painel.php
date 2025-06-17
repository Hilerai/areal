<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
$usuario = $_SESSION['usuario'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel - MTTORTATO</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container painel-container fade-in">
        <h1>🏗️ MTTORTATO</h1>
        <div class="welcome-message">
            Bem-vindo, <strong><?= htmlspecialchars($usuario['nome']) ?></strong>!
            <br><small>Gestão completa de materiais de construção</small>
        </div>
        
        <div class="menu-links">
            <?php if ($usuario['tipo'] === 'gestor'): ?>
                <a href="produtos.php">📦 Gerenciar Produtos</a>
                <a href="veiculos.php">🚛 Gerenciar Veículos</a>
            <?php elseif ($usuario['tipo'] === 'cliente'): ?>
                <a href="pedidos.php">🛒 Fazer Pedido</a>
            <?php elseif ($usuario['tipo'] === 'patio'): ?>
                <a href="saidas.php">🚛 Registrar Saída</a>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="logout.php" class="logout-link">🚪 Sair do Sistema</a>
        </div>
        
        <div style="text-align: center; margin-top: 20px; color: #8D6E63; font-size: 0.9rem;">
            Tipo de usuário: <strong><?= ucfirst($usuario['tipo']) ?></strong>
        </div>
    </div>
</body>
</html>

