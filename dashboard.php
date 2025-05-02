<?php
require 'config.php';
require 'user.php';
session_start();

if (!isset($_SESSION['id'])) {
    echo "<p>Vous devez être connecté pour accéder à cette page.</p>";
    exit;
}


function soldUser($connection, $user_id) {
    $stmt = $connection->prepare("
        SELECT SUM(CASE WHEN c.type = 'revenu' THEN t.montant ELSE -t.montant END) AS solde
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}


function detailsUser($connection, $user_id, $year, $month) {
    $stmt = $connection->prepare("
        SELECT 
            SUM(CASE WHEN c.type = 'revenu' THEN t.montant ELSE 0 END) AS total_revenus,
            SUM(CASE WHEN c.type = 'depense' THEN t.montant ELSE 0 END) AS total_depenses
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND YEAR(t.date_transaction) = ? AND MONTH(t.date_transaction) = ?
    ");
    $stmt->execute([$user_id, $year, $month]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function totalByCategory($connection, $user_id, $type, $year, $month) {
    $stmt = $connection->prepare("
        SELECT c.nom AS categorie, SUM(t.montant) AS total
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND c.type = ? 
        AND YEAR(t.date_transaction) = ? AND MONTH(t.date_transaction) = ?
        GROUP BY c.nom
    ");
    $stmt->execute([$user_id, $type, $year, $month]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function maxTransaction($connection, $user_id, $type, $year, $month) {
    $stmt = $connection->prepare("
        SELECT MAX(t.montant) AS max_montant
        FROM transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND c.type = ? 
        AND YEAR(t.date_transaction) = ? AND MONTH(t.date_transaction) = ?
    ");
    $stmt->execute([$user_id, $type, $year, $month]);
    return $stmt->fetchColumn();
}


$user_id = $_SESSION['id'];
$year = date('Y');
$month = date('m');

$solde = soldUser($connection, $user_id);
$details = detailsUser($connection, $user_id, $year, $month);
$revenusParCategorie = totalByCategory($connection, $user_id, 'revenu', $year, $month);
$depensesParCategorie = totalByCategory($connection, $user_id, 'depense', $year, $month);
$maxDepense = maxTransaction($connection, $user_id, 'depense', $year, $month);
$maxRevenu = maxTransaction($connection, $user_id, 'revenu', $year, $month);



?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Financière</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Styles de base */
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
            display: flex;
            min-height: 100vh;
        }
        

        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: #343a40;
            color: #fff;
            position: fixed;
            left: 0;
            top: 0;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header h3 {
            margin: 0;
            color: #fff;
            font-size: 1.25rem;
        }
        
        .sidebar-user {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }
        
        .sidebar-user .user-name {
            margin-top: 0.5rem;
            font-weight: bold;
        }
        
        .sidebar-menu {
            padding: 1rem 0;
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 0.25rem;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 0.75rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
            border-left: 4px solid #007bff;
        }
        
        .sidebar-menu i {
            margin-right: 0.75rem;
        }
        

        .main-content {
            flex: 1;
            margin-left: 250px;
            width: calc(100% - 250px);
            transition: all 0.3s ease;
        }
        
        .container {
            max-width: 1140px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        h1 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #333;
        }
        
        h2 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }
        
        /* Styles pour le Dashboard */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .box {
            background-color: #fff;
            border-radius: 0.25rem;
            padding: 1.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.3s ease;
        }
        
        .box:hover {
            transform: translateY(-3px);
        }
        
        .box.full-width {
            grid-column: 1 / -1;
        }
        
        .highlight-box {
            border-left: 4px solid #007bff;
        }
        
        .amount {
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
            margin: 0.5rem 0;
        }
        
        .positive {
            color: #28a745;
        }
        
        .negative {
            color: #dc3545;
        }
        
        .summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .summary-value {
            font-size: 1.25rem;
            font-weight: bold;
        }
        
        .category-list {
            list-style-type: none;
            margin: 0;
            padding: 0;
        }
        
        .category-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .category-item:last-child {
            border-bottom: none;
        }
        
        .category-name {
            font-weight: 500;
        }
        
        .category-amount {
            font-weight: bold;
        }
        
        .max-transaction {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .max-label {
            font-weight: 500;
        }
        
        .max-amount {
            font-weight: bold;
        }
        
        /* Styles pour les transactions */
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
        
        /* Modal pour transactions */
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
        

        .menu-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 0.5rem;
            cursor: pointer;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                transform: translateX(-250px);
            }
            
            .sidebar.active {
                width: 250px;
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .menu-toggle {
                display: block;
            }
            
            body.sidebar-open .main-content {
                transform: translateX(250px);
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-bar {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-form {
                margin-bottom: 1rem;
                width: 100%;
            }
        }
    </style>
</head>
<body>

<button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>


    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3>Gestion Financière</h3>
        </div>
        <div class="sidebar-user">
            <div class="user-avatar">
                <i class="fas fa-user-circle fa-3x"></i>
            </div>
            <div class="user-name">
                <?php echo $_SESSION['nom'] ?? 'Utilisateur'; ?>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="dashboard.php" <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-tachometer-alt"></i> Tableau de Bord
                </a>
            </li>
            <li>
                <a href="transaction.php" <?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'class="active"' : ''; ?>>
                    <i class="fas fa-exchange-alt"></i> Transactions
                </a>
            </li>
        </ul>
    </div>


    <div class="main-content">
        <div class="container">
            <?php if (basename($_SERVER['PHP_SELF']) == 'dashboard.php'): ?>

                <h1>Tableau de Bord</h1>
                
                <div class="dashboard-grid">
                    <div class="box highlight-box">
                        <h2>Solde Actuel</h2>
                        <p class="amount"><?= number_format($solde, 2) ?> MAD</p>
                    </div>
                    
                    <div class="box">
                        <h2>Résumé du Mois</h2>
                        <div class="summary">
                            <span>Revenus :</span>
                            <span class="summary-value positive"><?= number_format($details['total_revenus'], 2) ?> MAD</span>
                        </div>
                        <div class="summary">
                            <span>Dépenses :</span>
                            <span class="summary-value negative"><?= number_format($details['total_depenses'], 2) ?> MAD</span>
                        </div>
                    </div>
                    
                    <div class="box">
                        <h2>Revenus par Catégorie</h2>
                        <ul class="category-list">
                            <?php foreach ($revenusParCategorie as $item): ?>
                                <li class="category-item">
                                    <span class="category-name"><?= $item['categorie'] ?></span>
                                    <span class="category-amount positive"><?= number_format($item['total'], 2) ?> MAD</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="box">
                        <h2>Dépenses par Catégorie</h2>
                        <ul class="category-list">
                            <?php foreach ($depensesParCategorie as $item): ?>
                                <li class="category-item">
                                    <span class="category-name"><?= $item['categorie'] ?></span>
                                    <span class="category-amount negative"><?= number_format($item['total'], 2) ?> MAD</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="box full-width">
                        <h2>Transaction Maximale du Mois</h2>
                        <div class="max-transaction">
                            <span class="max-label">Plus grande dépense :</span>
                            <span class="max-amount negative"><?= number_format($maxDepense, 2) ?> MAD</span>
                        </div>
                        <div class="max-transaction">
                            <span class="max-label">Plus grand revenu :</span>
                            <span class="max-amount positive"><?= number_format($maxRevenu, 2) ?> MAD</span>
                        </div>
                    </div>
                </div>
                
            <?php elseif (basename($_SERVER['PHP_SELF']) == 'transactions.php'): ?>

                <h1>Historique des Transactions</h1>

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
            <?php endif; ?>
        </div>
    </div>

    <script>

            document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
            document.body.classList.toggle('sidebar-open');
        });
        

        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menuToggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(event.target) && 
                !menuToggle.contains(event.target) &&
                sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
                document.body.classList.remove('sidebar-open');
            }
        });
        
        <?php if (basename($_SERVER['PHP_SELF']) == 'transactions.php'): ?>

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
            
            document.querySelector('.modal-title').textContent = 'Modifier Transaction';
            openModal();
        }
        
        function openModal() {
            document.getElementById('transactionModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('transactionModal').classList.remove('show');

            if (!document.querySelector('input[name="transaction_id"]').value) {
                document.querySelector('form').reset();
                document.getElementById('revenu_cat_div').style.display = 'none';
                document.getElementById('depense_cat_div').style.display = 'none';
            }
            document.querySelector('.modal-title').textContent = 'Nouvelle Transaction';
        }
        <?php endif; ?>
    </script>
</body>
</html>