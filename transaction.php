<?php
require 'config.php';
require 'user.php';
session_start();

if (!isset($_SESSION['id'])) {
    echo "<p>Vous devez être connecté pour accéder à cette page.</p>";
    exit;
}


function addTransaction($transaction, $connection) {
    $stmt = $connection->prepare("SELECT id FROM categories WHERE nom = ? AND type = ?");
    $stmt->execute([$transaction['categorie'], $transaction['type']]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) return "Catégorie non trouvée.";

    $stmt = $connection->prepare("INSERT INTO transactions (user_id, category_id, montant, description, date_transaction) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $transaction['user_id'], $category['id'], $transaction['montant'],
        $transaction['description'], $transaction['date']
    ]);

    return "Transaction ajoutée avec succès.";
}

function editTransaction($idTransaction, $transaction, $connection) {
    $stmt = $connection->prepare("SELECT id FROM categories WHERE nom = ? AND type = ?");
    $stmt->execute([$transaction['categorie'], $transaction['type']]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) return "Catégorie non trouvée.";

    $stmt = $connection->prepare("UPDATE transactions SET category_id=?, montant=?, description=?, date_transaction=? WHERE id=? AND user_id=?");
    $stmt->execute([
        $category['id'], $transaction['montant'], $transaction['description'],
        $transaction['date'], $idTransaction, $transaction['user_id']
    ]);

    return "Transaction modifiée avec succès.";
}

function deleteTransaction($idTransaction, $user_id, $connection) {
    $stmt = $connection->prepare("DELETE FROM transactions WHERE id=? AND user_id=?");
    $stmt->execute([$idTransaction, $user_id]);
    return "Transaction supprimée.";
}

function listTransactionsByMonth($connection, $user_id, $year, $month) {
    $stmt = $connection->prepare("
        SELECT t.*, c.nom AS categorie, c.type 
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? 
        AND YEAR(date_transaction) = ? 
        AND MONTH(date_transaction) = ?
        ORDER BY date_transaction DESC
    ");
    $stmt->execute([$user_id, $year, $month]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllYears($connection, $user_id) {
    $stmt = $connection->prepare("SELECT DISTINCT YEAR(date_transaction) as year FROM transactions WHERE user_id=? ORDER BY year DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}



$user_id = $_SESSION['id'];
$message = "";

if (isset($_POST['submit'])) {
    $transaction = [
        'user_id' => $user_id,
        'type' => $_POST['type'] ?? '',
        'categorie' => $_POST['type'] === 'revenu' ? ($_POST['revenu_cat'] ?? '') : ($_POST['depense_cat'] ?? ''),
        'montant' => $_POST['montant'] ?? '',
        'description' => $_POST['description'] ?? '',
        'date' => $_POST['date'] ?? ''
    ];
    $transaction_id = $_POST['transaction_id'] ?? null;

    if ($transaction_id) {
        $message = editTransaction($transaction_id, $transaction, $connection);
    } else {
        $message = addTransaction($transaction, $connection);
    }
}

if (isset($_GET['delete'])) {
    $message = deleteTransaction($_GET['delete'], $user_id, $connection);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$filter_year = $_GET['year'] ?? date('Y');
$filter_month = $_GET['month'] ?? date('m');
$transactions = listTransactionsByMonth($connection, $user_id, $filter_year, $filter_month);
$years = getAllYears($connection, $user_id);

$categories = [
    'revenu' => ['Salaire', 'Bourse', 'Ventes', 'Autres'],
    'depense' => ['Logement', 'Transport', 'Alimentation', 'Santé', 'Divertissement', 'Éducation', 'Autres']
];


?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes Transactions</title>
    <style>

* {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            line-height: 1.5;
            color: #333;
        }
        

        .container {
            max-width: 1140px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        

        h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #333;
        }
        
        /* Alert */
        .alert {
            padding: 0.75rem 1.25rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: 0.25rem;
        }
        
        .alert-info {
            color: #0c5460;
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }
        

        form {
            margin-bottom: 1rem;
        }
        
        .filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .filter-form {
            display: flex;
            gap: 0.5rem;
        }
        
        select, input, button {
            font-family: inherit;
            font-size: 1rem;
            padding: 0.375rem 0.75rem;
            border-radius: 0.25rem;
            border: 1px solid #ced4da;
        }
        
        select {
            background-color: white;
            min-width: 120px;
        }
        

        button, .btn {
            cursor: pointer;
            display: inline-block;
            font-weight: 400;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            border: 1px solid transparent;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 0.25rem;
            text-decoration: none;
        }
        
        .btn-primary {
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
        }
        
        .btn-primary:hover {
            background-color: #0069d9;
            border-color: #0062cc;
        }
        
        .btn-secondary {
            color: #fff;
            background-color: #6c757d;
            border-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }
        
        .btn-success {
            color: #fff;
            background-color: #28a745;
            border-color: #28a745;
        }
        
        .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        
        .btn-danger {
            color: #fff;
            background-color: #dc3545;
            border-color: #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        
        .btn-warning {
            color: #212529;
            background-color: #ffc107;
            border-color: #ffc107;
        }
        
        .btn-warning:hover {
            background-color: #e0a800;
            border-color: #d39e00;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            border-radius: 0.2rem;
        }
        

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
            background-color: #fff;
        }
        
        th, td {
            padding: 0.75rem;
            border: 1px solid #dee2e6;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: left;
        }
        

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-dialog {
            max-width: 500px;
            margin: 1.75rem auto;
        }
        
        .modal-content {
            position: relative;
            display: flex;
            flex-direction: column;
            width: 100%;
            background-color: #fff;
            background-clip: padding-box;
            border-radius: 0.3rem;
            outline: 0;
        }
        
        .modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .modal-title {
            margin: 0;
            line-height: 1.5;
        }
        
        .btn-close {
            padding: 0.5rem;
            margin: -0.5rem -0.5rem -0.5rem auto;
            background: transparent;
            border: 0;
            cursor: pointer;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .modal-body {
            position: relative;
            flex: 1 1 auto;
            padding: 1rem;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            padding: 1rem;
            border-top: 1px solid #dee2e6;
            gap: 0.5rem;
        }
        

        .form-group {
            margin-bottom: 1rem;
        }
        
        label {
            display: inline-block;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            display: block;
            width: 100%;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .form-control:focus {
            color: #495057;
            background-color: #fff;
            border-color: #80bdff;
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .radio-group {
            display: flex;
            gap: 1rem;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Historique des Transactions</h2>

    <?php if ($message): ?>
        <div class="alert alert-info"><?= $message ?></div>
    <?php endif; ?>

    <div class="filter-bar">
        <div>
            <form method="GET" class="filter-form">
                <select name="year">
                    <?php foreach ($years as $year): ?>
                        <option value="<?= $year ?>" <?= $filter_year == $year ? 'selected' : '' ?>><?= $year ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="month">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= $filter_month == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : '' ?>>
                            <?= DateTime::createFromFormat('!m', $m)->format('F') ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <button class="btn btn-primary">Filtrer</button>
            </form>
        </div>

        <button class="btn btn-success" onclick="openModal()">Ajouter</button>
    </div>

    <table>
        <thead>
        <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Catégorie</th>
            <th>Description</th>
            <th>Montant</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($transactions as $t): ?>
            <tr>
                <td><?= $t['date_transaction'] ?></td>
                <td><?= ucfirst($t['type']) ?></td>
                <td><?= $t['categorie'] ?></td>
                <td><?= $t['description'] ?></td>
                <td><?= number_format($t['montant'], 2) ?> MAD</td>
                <td>
                    <a href="?delete=<?= $t['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Confirmer la suppression ?')">Supprimer</a>
                    <button type="button" class="btn btn-sm btn-warning" 
                        onclick='editTransaction(
                            <?= json_encode($t["id"]) ?>,
                            <?= json_encode($t["type"]) ?>,
                            <?= json_encode($t["categorie"]) ?>,
                            <?= json_encode($t["montant"]) ?>,
                            <?= json_encode($t["description"]) ?>,
                            <?= json_encode($t["date_transaction"]) ?>
                        )'>
                        Modifier
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>


<div class="modal" id="transactionModal">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouvelle Transaction</h5>
                <button type="button" class="btn-close" onclick="closeModal()">×</button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="transaction_id" value="">
                <div class="form-group">
                    <label>Type</label>
                    <div class="radio-group">
                        <label><input type="radio" name="type" value="revenu" onclick="toggleCats(this.value)"> Revenu</label>
                        <label><input type="radio" name="type" value="depense" onclick="toggleCats(this.value)"> Dépense</label>
                    </div>
                </div>
                <div class="form-group" id="revenu_cat_div" style="display:none;">
                    <label>Catégorie Revenu</label>
                    <select name="revenu_cat" class="form-control">
                        <?php foreach ($categories['revenu'] as $cat): ?>
                            <option value="<?= $cat ?>"><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="depense_cat_div" style="display:none;">
                    <label>Catégorie Dépense</label>
                    <select name="depense_cat" class="form-control">
                        <?php foreach ($categories['depense'] as $cat): ?>
                            <option value="<?= $cat ?>"><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Montant (MAD)</label>
                    <input type="number" name="montant" class="form-control" required step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Date de transaction</label>
                    <input type="date" name="date" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
                <button type="submit" name="submit" class="btn-primary">Valider</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleCats(type) {
        document.getElementById('revenu_cat_div').style.display = type === 'revenu' ? 'block' : 'none';
        document.getElementById('depense_cat_div').style.display = type === 'depense' ? 'block' : 'none';
    }

    function editTransaction(id, type, categorie, montant, description, date) {
        document.querySelector('input[name="transaction_id"]').value = id;
        document.querySelector('input[name="type"][value="' + type + '"]').checked = true;
        toggleCats(type);

        if (type === 'revenu') {
            document.querySelector('select[name="revenu_cat"]').value = categorie;
        } else {
            document.querySelector('select[name="depense_cat"]').value = categorie;
        }

        document.querySelector('input[name="montant"]').value = montant;
        document.querySelector('input[name="description"]').value = description;
        document.querySelector('input[name="date"]').value = date;
        
        openModal();
    }
    
    function openModal() {
        document.getElementById('transactionModal').classList.add('show');
    }
    
    function closeModal() {
        document.getElementById('transactionModal').classList.remove('show');
    }
</script>

</body>
</html>