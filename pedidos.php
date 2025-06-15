<?php
session_start();
if (!isset($_SESSION["usuario"]) || $_SESSION["usuario"]["tipo"] !== "cliente") {
    header("Location: login.php");
    exit();
}

require "conexao.php";

$mensagem = "";
$tipo_mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $cliente_id = $_SESSION["usuario"]["id"];
    $produto_id = $_POST["produto_id"];
    $quantidade = $_POST["quantidade"];
    
    if (empty($produto_id) || empty($quantidade) || $quantidade <= 0) {
        $mensagem = "Selecione um produto e informe uma quantidade válida!";
        $tipo_mensagem = "error";
    } else {
        $sql = "INSERT INTO pedidos (cliente_id, produto_id, quantidade, data_pedido) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iid", $cliente_id, $produto_id, $quantidade);
        
        if ($stmt->execute()) {
            $mensagem = "Pedido realizado com sucesso!";
            $tipo_mensagem = "success";
        } else {
            $mensagem = "Erro ao realizar pedido. Tente novamente.";
            $tipo_mensagem = "error";
        }
    }
}

// Buscar produtos disponíveis
$produtos_query = $conn->query("SELECT id, nome, preco, estoque FROM produtos WHERE estoque > 0 ORDER BY nome");
$produtos = [];
while($row = $produtos_query->fetch_assoc()) {
    $produtos[] = $row;
}

// Buscar pedidos do cliente
$cliente_id = $_SESSION["usuario"]["id"];
$pedidos = $conn->query("SELECT p.*, pr.nome as produto_nome, pr.preco 
                        FROM pedidos p 
                        JOIN produtos pr ON p.produto_id = pr.id 
                        WHERE p.cliente_id = $cliente_id 
                        ORDER BY p.data_pedido DESC");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fazer Pedido - MTTORTATO</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container fade-in" style="max-width: 800px;">
        <h2>🛒 Fazer Pedido</h2>
        
        <?php if (!empty($mensagem)): ?>
            <div class="<?= $tipo_mensagem ?>">
                <?= $mensagem ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <h3 style="margin-bottom: 20px; color: #2c3e50;">Novo Pedido</h3>
            
            <div class="form-group">
                <label for="produto_id">Produto:</label>
                <select id="produto_id" name="produto_id" required>
                    <option value="">Selecione um produto</option>
                    <?php foreach($produtos as $produto): ?>
                        <option value="<?= $produto["id"] ?>" data-preco="<?= $produto["preco"] ?>">
                            <?= htmlspecialchars($produto["nome"]) ?> - 
                            R$ <?= number_format($produto["preco"], 2, ",", ".") ?>/m³ - 
                            Estoque: <?= number_format($produto["estoque"], 2, ",", ".") ?>m³
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="quantidade">Quantidade (m³):</label>
                <input type="number" id="quantidade" name="quantidade" step="0.01" min="0.01" required placeholder="0,00">
            </div>
            
            <div class="form-group">
                <label>Valor Total Estimado:</label>
                <p id="valor_total" style="font-size: 1.2em; font-weight: bold; color: #28a745;">R$ 0,00</p>
            </div>
            
            <button type="submit">📋 Enviar Pedido</button>
        </form>
        
        <?php if ($pedidos && $pedidos->num_rows > 0): ?>
            <h3 style="margin: 30px 0 20px 0; color: #2c3e50;">Meus Pedidos</h3>
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Valor Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($pedido = $pedidos->fetch_assoc()): ?>
                        <tr>
                            <td><?= date("d/m/Y H:i", strtotime($pedido["data_pedido"])) ?></td>
                            <td><?= htmlspecialchars($pedido["produto_nome"]) ?></td>
                            <td><?= number_format($pedido["quantidade"], 2, ",", ".") ?> m³</td>
                            <td>R$ <?= number_format($pedido["quantidade"] * $pedido["preco"], 2, ",", ".") ?></td>
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

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const produtoSelect = document.getElementById("produto_id");
            const quantidadeInput = document.getElementById("quantidade");
            const valorTotalDisplay = document.getElementById("valor_total");

            function calcularValorTotal() {
                const selectedOption = produtoSelect.options[produtoSelect.selectedIndex];
                const preco = parseFloat(selectedOption.dataset.preco || 0);
                const quantidade = parseFloat(quantidadeInput.value || 0);
                const total = preco * quantidade;
                valorTotalDisplay.textContent = `R$ ${total.toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            }

            produtoSelect.addEventListener("change", calcularValorTotal);
            quantidadeInput.addEventListener("input", calcularValorTotal);

            // Calcular valor inicial ao carregar a página
            calcularValorTotal();
        });
    </script>
</body>
</html>

