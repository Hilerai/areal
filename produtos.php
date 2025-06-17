<?php
session_start();
if (!isset($_SESSION["usuario"]) || $_SESSION["usuario"]["tipo"] !== "gestor") {
    header("Location: login.php");
    exit();
}

require "conexao.php";

$mensagem = "";
$tipo_mensagem = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["action"]) && $_POST["action"] === "add") {
        $nome = trim($_POST["nome"]);
        $preco = $_POST["preco"];
        $estoque = $_POST["estoque"];
        
        if (empty($nome) || empty($preco) || empty($estoque)) {
            $mensagem = "Todos os campos são obrigatórios!";
            $tipo_mensagem = "error";
        } else {
            $sql = "INSERT INTO produtos (nome, preco, estoque) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sdd", $nome, $preco, $estoque);
            
            if ($stmt->execute()) {
                $mensagem = "Produto cadastrado com sucesso!";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Erro ao cadastrar produto. Tente novamente.";
                $tipo_mensagem = "error";
            }
        }
    } elseif (isset($_POST["action"]) && $_POST["action"] === "edit") {
        $id = $_POST["id"];
        $nome = trim($_POST["nome"]);
        $preco = $_POST["preco"];
        $estoque = $_POST["estoque"];

        if (empty($nome) || empty($preco) || empty($estoque)) {
            $mensagem = "Todos os campos são obrigatórios para edição!";
            $tipo_mensagem = "error";
        } else {
            $sql = "UPDATE produtos SET nome = ?, preco = ?, estoque = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sddi", $nome, $preco, $estoque, $id);
            if ($stmt->execute()) {
                $mensagem = "Produto atualizado com sucesso!";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Erro ao atualizar produto. Tente novamente.";
                $tipo_mensagem = "error";
            }
        }
    } elseif (isset($_POST["action"]) && $_POST["action"] === "delete") {
        $id = $_POST["id"];
        $sql = "DELETE FROM produtos WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $mensagem = "Produto excluído com sucesso!";
            $tipo_mensagem = "success";
        } else {
            $mensagem = "Erro ao excluir produto. Tente novamente.";
            $tipo_mensagem = "error";
        }
    }
}

// Buscar produtos existentes
$produtos = $conn->query("SELECT * FROM produtos ORDER BY nome");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Produtos - MTTORTATO</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container fade-in" style="max-width: 900px;">
        <h2>📦 Gerenciar Produtos</h2>
        
        <?php if (!empty($mensagem)): ?>
            <div class="<?= $tipo_mensagem ?>">
                <?= $mensagem ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <h3 style="margin-bottom: 20px; color: #2c3e50;">Cadastrar Novo Produto</h3>
            <input type="hidden" name="action" value="add">
            
            <div class="form-group">
                <label for="nome">Nome do Produto:</label>
                <input type="text" id="nome" name="nome" required placeholder="Ex: Areia Média">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="preco">Preço por Metro Cúbico (R$):</label>
                    <input type="number" id="preco" name="preco" step="0.01" min="0" required placeholder="0.00">
                </div>
                
                <div class="form-group">
                    <label for="estoque">Estoque (m³):</label>
                    <input type="number" id="estoque" name="estoque" step="0.01" min="0" required placeholder="0.00">
                </div>
            </div>
            
            <button type="submit">💾 Salvar Produto</button>
        </form>
        
        <?php if ($produtos && $produtos->num_rows > 0): ?>
            <h3 style="margin: 30px 0 20px 0; color: #2c3e50;">Produtos Cadastrados</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Preço (R$/m³)</th>
                        <th>Estoque (m³)</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($produto = $produtos->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($produto["nome"]) ?></td>
                            <td>R$ <?= number_format($produto["preco"], 2, ",", ".") ?></td>
                            <td><?= number_format($produto["estoque"], 2, ",", ".") ?> m³</td>
                            <td>
                                <button class="edit-btn" onclick="openEditModal(<?= $produto["id"] ?>, '<?= htmlspecialchars($produto["nome"]) ?>', <?= $produto["preco"] ?>, <?= $produto["estoque"] ?>)">Editar</button>
                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('Tem certeza que deseja excluir este produto?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $produto["id"] ?>">
                                    <button type="submit" class="delete-btn">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; margin-top: 20px; color: #777;">Nenhum produto cadastrado ainda.</p>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="painel.php">🏠 Voltar ao Painel</a>
            <a href="logout.php" class="logout-link">🚪 Sair</a>
        </div>
    </div>

    <!-- Modal de Edição -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeEditModal()">&times;</span>
            <h3>Editar Produto</h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit-id" name="id">
                
                <div class="form-group">
                    <label for="edit-nome">Nome do Produto:</label>
                    <input type="text" id="edit-nome" name="nome" required>
                </div>
                
                <div class="form-group">
                    <label for="edit-preco">Preço por Metro Cúbico (R$):</label>
                    <input type="number" id="edit-preco" name="preco" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="edit-estoque">Estoque (m³):</label>
                    <input type="number" id="edit-estoque" name="estoque" step="0.01" min="0" required>
                </div>
                
                <button type="submit">Atualizar Produto</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, nome, preco, estoque) {
            document.getElementById("edit-id").value = id;
            document.getElementById("edit-nome").value = nome;
            document.getElementById("edit-preco").value = preco;
            document.getElementById("edit-estoque").value = estoque;
            document.getElementById("editModal").style.display = "block";
        }

        function closeEditModal() {
            document.getElementById("editModal").style.display = "none";
        }

        window.onclick = function(event) {
            const modal = document.getElementById("editModal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>

