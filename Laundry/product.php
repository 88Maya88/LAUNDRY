<?php
    session_start();
    include_once("db_connect.php");
    
    // Check if user is logged in
    if(!isset($_SESSION["user_Username"])){
        header("Location: log.php");
        exit();
    }
    
    // Get user info from session
    $username = $_SESSION["user_Username"];
    $role = $_SESSION["user_Role"];
    
    // Handle form submissions
    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        if(isset($_POST['action'])) {
            switch($_POST['action']) {
                case 'add_product':
                    $product_name = trim($_POST['product_name']);
                    $category = trim($_POST['category']);
                    $unit = trim($_POST['unit']);
                    $current_stock = intval($_POST['current_stock']);
                    $reorder_level = intval($_POST['reorder_level']);
                    $status = trim($_POST['status']);
                    
                    $stmt = $con->prepare("INSERT INTO products (product_name, category, unit, current_stock, reorder_level, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssiiss", $product_name, $category, $unit, $current_stock, $reorder_level, $status, $username);
                    
                    if($stmt->execute()) {
                        $success_message = "Product added successfully!";
                    } else {
                        $error_message = "Error adding product: " . $stmt->error;
                    }
                    $stmt->close();
                    break;
                    
                case 'update_stock':
                    $product_id = intval($_POST['product_id']);
                    $transaction_type = trim($_POST['transaction_type']);
                    $quantity = intval($_POST['quantity']);
                    $notes = trim($_POST['notes']);
                    
                    // Get current stock
                    $stmt = $con->prepare("SELECT current_stock, product_name FROM products WHERE product_id = ?");
                    $stmt->bind_param("i", $product_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if($result->num_rows > 0) {
                        $product = $result->fetch_assoc();
                        $current_stock = $product['current_stock'];
                        $product_name = $product['product_name'];
                        
                        // Calculate new stock based on transaction type
                        if($transaction_type == 'restock') {
                            $new_stock = $current_stock + $quantity;
                        } elseif($transaction_type == 'consumption') {
                            $new_stock = $current_stock - $quantity;
                        } else { // adjustment
                            $new_stock = $quantity;
                        }
                        
                        // Ensure stock doesn't go negative
                        if($new_stock < 0) {
                            $error_message = "Cannot reduce stock below 0. Current stock: " . $current_stock;
                        } else {
                            // Update product stock
                            $stmt = $con->prepare("UPDATE products SET current_stock = ? WHERE product_id = ?");
                            $stmt->bind_param("ii", $new_stock, $product_id);
                            
                            if($stmt->execute()) {
                                // Log the transaction
                                $stmt = $con->prepare("INSERT INTO stock_transactions (product_id, transaction_type, quantity, old_stock, new_stock, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                $stmt->bind_param("isiiiis", $product_id, $transaction_type, $quantity, $current_stock, $new_stock, $notes, $username);
                                $stmt->execute();
                                
                                // Update product status based on reorder level
                                $stmt = $con->prepare("SELECT reorder_level FROM products WHERE product_id = ?");
                                $stmt->bind_param("i", $product_id);
                                $stmt->execute();
                                $reorder_result = $stmt->get_result();
                                $reorder_level = $reorder_result->fetch_assoc()['reorder_level'];
                                
                                $new_status = 'Available';
                                if($new_stock == 0) {
                                    $new_status = 'Out of Stock';
                                } elseif($new_stock <= $reorder_level) {
                                    $new_status = 'Low Stock';
                                }
                                
                                $stmt = $con->prepare("UPDATE products SET status = ? WHERE product_id = ?");
                                $stmt->bind_param("si", $new_status, $product_id);
                                $stmt->execute();
                                
                                $success_message = "Stock updated successfully! $product_name: $current_stock → $new_stock";
                            } else {
                                $error_message = "Error updating stock: " . $stmt->error;
                            }
                        }
                    } else {
                        $error_message = "Product not found!";
                    }
                    $stmt->close();
                    break;
            }
        }
    }
    
    // Get all products
    $products_query = "SELECT * FROM products ORDER BY product_name";
    $products_result = mysqli_query($con, $products_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Laundry System</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
        header { background-color: #2c3e50; color: white; padding: 20px; text-align: center; position: relative; }
        .user-info { position: absolute; top: 20px; right: 20px; color: white; }
        .logout-btn { background-color: #e74c3c; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; margin-left: 10px; }
        .back-btn { position: absolute; top: 20px; left: 20px; background-color: #3498db; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; text-decoration: none; }
        .container { padding: 20px; }
        .card { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .btn { background-color: #2ecc71; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; text-decoration: none; display: inline-block; }
        .btn:hover { background-color: #27ae60; }
        .btn-warning { background-color: #f39c12; }
        .btn-warning:hover { background-color: #e67e22; }
        .btn-danger { background-color: #e74c3c; }
        .btn-danger:hover { background-color: #c0392b; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #34495e; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        tr:hover { background-color: #e8f4f8; }
        
        .status-available { background-color: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; }
        .status-low { background-color: #fff3cd; color: #856404; padding: 4px 8px; border-radius: 4px; }
        .status-out { background-color: #f8d7da; color: #721c24; padding: 4px 8px; border-radius: 4px; }
        
        .modal { display: none; position: fixed; z-index: 999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 20px; border-radius: 8px; width: 90%; max-width: 500px; }
        .close { float: right; font-size: 20px; cursor: pointer; }
        form input, form textarea, form select { width: 100%; margin: 10px 0; padding: 8px; box-sizing: border-box; }
        
        .alert { padding: 15px; margin: 10px 0; border-radius: 4px; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .search-bar { margin-bottom: 20px; }
        .search-bar input { width: 300px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        
        .stats { display: flex; gap: 20px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); text-align: center; flex: 1; }
        .stat-number { font-size: 24px; font-weight: bold; color: #2c3e50; }
        .stat-label { color: #7f8c8d; font-size: 14px; }
    </style>
</head>
<body>
    <header>
        <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
        <h1>Product Management</h1>
        <div class="user-info">
            <?php echo htmlspecialchars($username); ?> (<?php echo htmlspecialchars($role); ?>)
            <button class="logout-btn" onclick="logout()">Logout</button>
        </div>
    </header>
    
    <div class="container">
        <?php if(isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if(isset($error_message)): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php
        // Calculate statistics
        $total_products = mysqli_num_rows($products_result);
        mysqli_data_seek($products_result, 0);
        
        $low_stock_count = 0;
        $out_of_stock_count = 0;
        $total_value = 0;
        
        while($product = mysqli_fetch_assoc($products_result)) {
            if($product['status'] == 'Low Stock') $low_stock_count++;
            if($product['status'] == 'Out of Stock') $out_of_stock_count++;
        }
        mysqli_data_seek($products_result, 0);
        ?>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_products; ?></div>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $low_stock_count; ?></div>
                <div class="stat-label">Low Stock</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $out_of_stock_count; ?></div>
                <div class="stat-label">Out of Stock</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_products - $low_stock_count - $out_of_stock_count; ?></div>
                <div class="stat-label">Available</div>
            </div>
        </div>
        
        <div class="card">
            <h2>Inventory Overview</h2>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search products..." onkeyup="searchProducts()">
                </div>
                <div>
                    <button class="btn" onclick="openModal('addProductModal')">Add Product</button>
                    <button class="btn btn-warning" onclick="openModal('updateStockModal')">Update Stock</button>
                </div>
            </div>
            
            <table id="productsTable">
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Unit</th>
                        <th>Current Stock</th>
                        <th>Reorder Level</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($product = mysqli_fetch_assoc($products_result)): ?>
                    <tr>
                        <td><?php echo $product['product_id']; ?></td>
                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($product['category']); ?></td>
                        <td><?php echo htmlspecialchars($product['unit']); ?></td>
                        <td><?php echo $product['current_stock']; ?></td>
                        <td><?php echo $product['reorder_level']; ?></td>
                        <td>
                            <span class="status-<?php echo $product['status'] == 'Available' ? 'available' : ($product['status'] == 'Low Stock' ? 'low' : 'out'); ?>">
                                <?php echo $product['status']; ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-warning" onclick="quickUpdateStock(<?php echo $product['product_id']; ?>, '<?php echo htmlspecialchars($product['product_name']); ?>', <?php echo $product['current_stock']; ?>)">Quick Update</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add Product Modal -->
    <div id="addProductModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addProductModal')">&times;</span>
            <h3>Add New Product</h3>
            <form method="post">
                <input type="hidden" name="action" value="add_product">
                <input type="text" name="product_name" placeholder="Product Name" required>
                <input type="text" name="category" placeholder="Category" required>
                <input type="text" name="unit" placeholder="Unit (e.g., kg, pieces, liters)" required>
                <input type="number" name="current_stock" placeholder="Current Stock" required min="0">
                <input type="number" name="reorder_level" placeholder="Reorder Level" required min="0">
                <select name="status" required>
                    <option value="Available">Available</option>
                    <option value="Low Stock">Low Stock</option>
                    <option value="Out of Stock">Out of Stock</option>
                </select>
                <button type="submit" class="btn">Add Product</button>
            </form>
        </div>
    </div>
    
    <!-- Update Stock Modal -->
    <div id="updateStockModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('updateStockModal')">&times;</span>
            <h3>Update Stock</h3>
            <form method="post">
                <input type="hidden" name="action" value="update_stock">
                <input type="number" name="product_id" placeholder="Product ID" required min="1">
                <select name="transaction_type" required>
                    <option value="">Select Transaction Type</option>
                    <option value="restock">Restock (Add Stock)</option>
                    <option value="consumption">Consumption (Remove Stock)</option>
                    <option value="adjustment">Adjustment (Set Exact Amount)</option>
                </select>
                <input type="number" name="quantity" placeholder="Quantity" required min="0">
                <textarea name="notes" placeholder="Notes/Reason for change" rows="3"></textarea>
                <button type="submit" class="btn">Update Stock</button>
            </form>
        </div>
    </div>
    
    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function logout() {
            if(confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }
        
        function searchProducts() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('productsTable');
            const rows = table.getElementsByTagName('tr');
            
            for(let i = 1; i < rows.length; i++) {
                const cells = rows[i].getElementsByTagName('td');
                let found = false;
                
                for(let j = 0; j < cells.length - 1; j++) {
                    if(cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
                
                rows[i].style.display = found ? '' : 'none';
            }
        }
        
        function quickUpdateStock(productId, productName, currentStock) {
            const modal = document.getElementById('updateStockModal');
            const form = modal.querySelector('form');
            
            form.querySelector('[name="product_id"]').value = productId;
            modal.querySelector('h3').textContent = `Update Stock - ${productName} (Current: ${currentStock})`;
            
            openModal('updateStockModal');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.getElementsByClassName('modal');
            for(let i = 0; i < modals.length; i++) {
                if(event.target === modals[i]) {
                    modals[i].style.display = 'none';
                }
            }
        }
    </script>
</body>
</html>