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
        $placa = strtoupper(trim($_POST["placa"]));
        $modelo = trim($_POST["modelo"]);
        $capacidade_m3 = $_POST["capacidade_m3"];
        $status = $_POST["status"];
        
        if (empty($placa) || empty($modelo) || empty($capacidade_m3)) {
            $mensagem = "Todos os campos são obrigatórios!";
            $tipo_mensagem = "error";
        } else {
            $sql = "INSERT INTO veiculos (placa, modelo, capacidade_m3, status) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssds", $placa, $modelo, $capacidade_m3, $status);
            
            if ($stmt->execute()) {
                $mensagem = "Veículo cadastrado com sucesso!";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Erro ao cadastrar veículo. Verifique se a placa já não está cadastrada.";
                $tipo_mensagem = "error";
            }
        }
    } elseif (isset($_POST["action"]) && $_POST["action"] === "edit") {
        $id = $_POST["id"];
        $placa = strtoupper(trim($_POST["placa"]));
        $modelo = trim($_POST["modelo"]);
        $capacidade_m3 = $_POST["capacidade_m3"];
        $status = $_POST["status"];

        if (empty($placa) || empty($modelo) || empty($capacidade_m3)) {
            $mensagem = "Todos os campos são obrigatórios para edição!";
            $tipo_mensagem = "error";
        } else {
            $sql = "UPDATE veiculos SET placa = ?, modelo = ?, capacidade_m3 = ?, status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdsi", $placa, $modelo, $capacidade_m3, $status, $id);
            if ($stmt->execute()) {
                $mensagem = "Veículo atualizado com sucesso!";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Erro ao atualizar veículo. Verifique se a placa já não está cadastrada.";
                $tipo_mensagem = "error";
            }
        }
    } elseif (isset($_POST["action"]) && $_POST["action"] === "delete") {
        $id = $_POST["id"];
        $sql = "DELETE FROM veiculos WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $mensagem = "Veículo excluído com sucesso!";
            $tipo_mensagem = "success";
        } else {
            $mensagem = "Erro ao excluir veículo. Tente novamente.";
            $tipo_mensagem = "error";
        }
    }
}

// Buscar veículos existentes
$veiculos = $conn->query("SELECT * FROM veiculos ORDER BY placa");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Veículos - MTTORTATO</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container fade-in" style="max-width: 1000px;">
        <h2>🚛 Gerenciar Veículos</h2>
        
        <?php if (!empty($mensagem)): ?>
            <div class="<?= $tipo_mensagem ?>">
                <?= $mensagem ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <h3 style="margin-bottom: 20px; color: #2c3e50;">Cadastrar Novo Veículo</h3>
            <input type="hidden" name="action" value="add">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="placa">Placa do Veículo:</label>
                    <input type="text" id="placa" name="placa" required placeholder="Ex: ABC-1234" maxlength="10">
                </div>
                
                <div class="form-group">
                    <label for="modelo">Modelo/Marca:</label>
                    <input type="text" id="modelo" name="modelo" required placeholder="Ex: Mercedes-Benz 1113">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="capacidade_m3">Capacidade (m³):</label>
                    <input type="number" id="capacidade_m3" name="capacidade_m3" step="0.01" min="0" required placeholder="0.00">
                </div>
                
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status" required>
                        <option value="disponivel">🟢 Disponível</option>
                        <option value="em_uso">🟡 Em Uso</option>
                        <option value="manutencao">🔴 Manutenção</option>
                        <option value="inativo">⚫ Inativo</option>
                    </select>
                </div>
            </div>
            
            <button type="submit">🚛 Salvar Veículo</button>
        </form>
        
        <?php if ($veiculos && $veiculos->num_rows > 0): ?>
            <h3 style="margin: 30px 0 20px 0; color: #2c3e50;">Frota de Veículos</h3>
            <table>
                <thead>
                    <tr>
                        <th>Placa</th>
                        <th>Modelo/Marca</th>
                        <th>Capacidade (m³)</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($veiculo = $veiculos->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($veiculo["placa"]) ?></strong></td>
                            <td><?= htmlspecialchars($veiculo["modelo"]) ?></td>
                            <td><?= number_format($veiculo["capacidade_m3"], 2, ",", ".") ?> m³</td>
                            <td>
                                <?php
                                $status_icons = [
                                    'disponivel' => '🟢 Disponível',
                                    'em_uso' => '🟡 Em Uso',
                                    'manutencao' => '🔴 Manutenção',
                                    'inativo' => '⚫ Inativo'
                                ];
                                echo $status_icons[$veiculo["status"]] ?? $veiculo["status"];
                                ?>
                            </td>
                            <td>
                                <button class="edit-btn" onclick="openEditModal(<?= $veiculo["id"] ?>, '<?= htmlspecialchars($veiculo["placa"]) ?>', '<?= htmlspecialchars($veiculo["modelo"]) ?>', <?= $veiculo["capacidade_m3"] ?>, '<?= $veiculo["status"] ?>')">Editar</button>
                                <form method="POST" style="display:inline-block;" onsubmit="return confirm('Tem certeza que deseja excluir este veículo?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $veiculo["id"] ?>">
                                    <button type="submit" class="delete-btn">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; margin-top: 20px; color: #777;">Nenhum veículo cadastrado ainda.</p>
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
            <h3>Editar Veículo</h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit-id" name="id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-placa">Placa do Veículo:</label>
                        <input type="text" id="edit-placa" name="placa" required maxlength="10">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-modelo">Modelo/Marca:</label>
                        <input type="text" id="edit-modelo" name="modelo" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-capacidade_m3">Capacidade (m³):</label>
                        <input type="number" id="edit-capacidade_m3" name="capacidade_m3" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-status">Status:</label>
                        <select id="edit-status" name="status" required>
                            <option value="disponivel">🟢 Disponível</option>
                            <option value="em_uso">🟡 Em Uso</option>
                            <option value="manutencao">🔴 Manutenção</option>
                            <option value="inativo">⚫ Inativo</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit">Atualizar Veículo</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, placa, modelo, capacidade_m3, status) {
            document.getElementById("edit-id").value = id;
            document.getElementById("edit-placa").value = placa;
            document.getElementById("edit-modelo").value = modelo;
            document.getElementById("edit-capacidade_m3").value = capacidade_m3;
            document.getElementById("edit-status").value = status;
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

        // Formatação automática da placa
        document.getElementById("placa").addEventListener("input", function(e) {
            let value = e.target.value.replace(/[^A-Za-z0-9]/g, "").toUpperCase();
            if (value.length > 3) {
                value = value.substring(0, 3) + "-" + value.substring(3, 7);
            }
            e.target.value = value;
        });

        document.getElementById("edit-placa").addEventListener("input", function(e) {
            let value = e.target.value.replace(/[^A-Za-z0-9]/g, "").toUpperCase();
            if (value.length > 3) {
                value = value.substring(0, 3) + "-" + value.substring(3, 7);
            }
            e.target.value = value;
        });
    </script>
</body>
</html>

