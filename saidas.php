<?php
session_start();
if (!isset($_SESSION["usuario"]) || $_SESSION["usuario"]["tipo"] !== "patio") {
    header("Location: login.php");
    exit();
}

require "conexao.php";

$mensagem = "";
$tipo_mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $pedido_id = $_POST["pedido_id"];
    $operador_id = $_SESSION["usuario"]["id"];
    $quantidade_real = $_POST["quantidade_real"];
    
    if (empty($pedido_id) || empty($quantidade_real) || $quantidade_real <= 0) {
        $mensagem = "Selecione um pedido e informe uma quantidade válida!";
        $tipo_mensagem = "error";
    } else {
        $sql = "INSERT INTO saidas_material (pedido_id, operador_id, quantidade_real, data_saida) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iid", $pedido_id, $operador_id, $quantidade_real);
        
        if ($stmt->execute()) {
            $conn->query("UPDATE pedidos SET status='carregado' WHERE id=$pedido_id");
            $mensagem = "Saída registrada com sucesso!";
            $tipo_mensagem = "success";
        } else {
            $mensagem = "Erro ao registrar saída. Tente novamente.";
            $tipo_mensagem = "error";
        }
    }
}

// Buscar pedidos pendentes com informações do cliente e produto
$pedidos = $conn->query("SELECT p.id, p.quantidade, pr.nome as produto_nome, u.nome as cliente_nome, p.data_pedido
                        FROM pedidos p 
                        JOIN produtos pr ON p.produto_id = pr.id 
                        JOIN usuarios u ON p.cliente_id = u.id 
                        WHERE p.status = 'pendente' OR p.status IS NULL
                        ORDER BY p.data_pedido");

// Buscar saídas registradas
$saidas = $conn->query("SELECT s.*, p.quantidade as quantidade_pedido, pr.nome as produto_nome, u.nome as cliente_nome, op.nome as operador_nome
                       FROM saidas_material s
                       JOIN pedidos p ON s.pedido_id = p.id
                       JOIN produtos pr ON p.produto_id = pr.id
                       JOIN usuarios u ON p.cliente_id = u.id
                       JOIN usuarios op ON s.operador_id = op.id
                       ORDER BY s.data_saida DESC");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Saída - MTTORTATO</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container fade-in" style="max-width: 900px;">
        <h2>🚛 Registrar Saída de Material</h2>
        
        <?php if (!empty($mensagem)): ?>
            <div class="<?= $tipo_mensagem ?>">
                <?= $mensagem ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <h3 style="margin-bottom: 20px; color: #2c3e50;">Nova Saída</h3>
            
            <div class="form-group">
                <label for="pedido_id">Pedido:</label>
                <select id="pedido_id" name="pedido_id" required>
                    <option value="">Selecione um pedido</option>
                    <?php if ($pedidos && $pedidos->num_rows > 0): ?>
                        <?php while($pedido = $pedidos->fetch_assoc()): ?>
                            <option value="<?= $pedido["id"] ?>">
                                Pedido #<?= $pedido["id"] ?> - 
                                <?= htmlspecialchars($pedido["cliente_nome"]) ?> - 
                                <?= htmlspecialchars($pedido["produto_nome"]) ?> - 
                                <?= number_format($pedido["quantidade"], 2, ",", ".") ?>m³
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="quantidade_real">Quantidade Real Carregada (m³):</label>
                <input type="number" id="quantidade_real" name="quantidade_real" step="0.01" min="0.01" required placeholder="0,00">
            </div>
            
            <button type="submit">📋 Registrar Saída</button>
        </form>
        
        <?php if ($saidas && $saidas->num_rows > 0): ?>
            <h3 style="margin: 30px 0 20px 0; color: #2c3e50;">Saídas Registradas</h3>
            <table>
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Produto</th>
                        <th>Qtd. Pedida</th>
                        <th>Qtd. Real</th>
                        <th>Operador</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($saida = $saidas->fetch_assoc()): ?>
                        <tr>
                            <td><?= date("d/m/Y H:i", strtotime($saida["data_saida"])) ?></td>
                            <td>#<?= $saida["pedido_id"] ?></td>
                            <td><?= htmlspecialchars($saida["cliente_nome"]) ?></td>
                            <td><?= htmlspecialchars($saida["produto_nome"]) ?></td>
                            <td><?= number_format($saida["quantidade_pedido"], 2, ",", ".") ?> m³</td>
                            <td><?= number_format($saida["quantidade_real"], 2, ",", ".") ?> m³</td>
                            <td><?= htmlspecialchars($saida["operador_nome"]) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="painel.php">🏠 Voltar ao Painel</a>
            <a href="logout.php" class="logout-link">🚪 Sair</a>
        </div>
    </div>
</body>
</html>