<?php
    session_start();
    
    // Check if user is NOT logged in, redirect to login
    if(!isset($_SESSION["user_Username"])){
        header("Location: log.php");
        exit();
    }
    
    // Get user info from session
    $username = $_SESSION["user_Username"];
    $role = $_SESSION["user_Role"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Laundry System Dashboard</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
    header { background-color: #2c3e50; color: white; padding: 20px; text-align: center; position: relative; }
    .user-info { position: absolute; top: 20px; right: 20px; color: white; }
    .logout-btn { background-color: #e74c3c; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; margin-left: 10px; }
    nav { background-color: #34495e; padding: 10px; display: flex; justify-content: center; gap: 15px; }
    nav a { color: white; text-decoration: none; font-weight: bold; }
    section { padding: 20px; }
    .card { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    button { background-color: #2ecc71; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; margin-top: 10px; }
    .btn { background-color: #2ecc71; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; text-decoration: none; display: inline-block; }
    .btn:hover { background-color: #27ae60; }
    .modal { display: none; position: fixed; z-index: 999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center; }
    .modal-content { background: white; padding: 20px; border-radius: 8px; width: 90%; max-width: 400px; }
    .close { float: right; font-size: 20px; cursor: pointer; }
    form input, form textarea, form select { width: 100%; margin: 10px 0; padding: 8px; box-sizing: border-box; }
    .staff-only { opacity: 0.6; }
    .admin-only { display: <?php echo ($role == 'admin') ? 'block' : 'none'; ?>; }
  </style>
</head>
<body>
  <header>
    <h1>Inventory & Feedback Management System</h1>
    <div class="user-info">
      Welcome, <?php echo htmlspecialchars($username); ?> (<?php echo htmlspecialchars($role); ?>)
      <button class="logout-btn" onclick="logout()">Logout</button>
    </div>
  </header>
  <nav>
    <a href="#inventory">Inventory</a>
    <a href="#suppliers">Suppliers</a>
    <a href="#feedback">Feedback</a>
    <a href="#complaints">Complaints</a>
    <a href="#responses" class="admin-only">Responses</a>
  </nav>
  
  <section id="inventory">
    <div class="card">
      <h2>Inventory Overview</h2>
      <a href="product.php" class="btn" style="text-decoration: none; margin-bottom: 10px;">Manage Products</a>
      <button onclick="generateReport()" <?php echo ($role != 'admin') ? 'class="staff-only" disabled' : ''; ?>>Generate Daily Report</button>
    </div>
  </section>
  
  <section id="suppliers">
    <div class="card">
      <h2>Supplier Management</h2>
      <button onclick="openModal('supplierModal')" class="admin-only">Add Supplier</button>
      <?php if($role == 'staff'): ?>
        <button onclick="openModal('supplierNoteModal')">Add Supplier Note</button>
      <?php endif; ?>
    </div>
  </section>
  
  <section id="feedback">
    <div class="card">
      <h2>Customer Feedback</h2>
      <button onclick="openModal('feedbackModal')">Submit Feedback</button>
    </div>
  </section>
  
  <section id="complaints">
    <div class="card">
      <h2>Complaints</h2>
      <button onclick="openModal('complaintModal')">File Complaint</button>
    </div>
  </section>
  
  <section id="responses" class="admin-only">
    <div class="card">
      <h2>Admin Responses</h2>
      <button onclick="openModal('responseModal')">Add Response</button>
    </div>
  </section>

  <!-- MODALS -->


  <div id="supplierNoteModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal('supplierNoteModal')">&times;</span>
      <h3>Add Internal Note</h3>
      <form action="add_supplier_note.php" method="post">
        <input type="number" name="supplier_id" placeholder="Supplier ID" required>
        <select name="note_type" required>
          <option value="">Select Note Type</option>
          <option value="delivery">Delivery Issue</option>
          <option value="quality">Quality Issue</option>
          <option value="pricing">Pricing Update</option>
          <option value="general">General Note</option>
        </select>
        <textarea name="note_content" placeholder="Internal note content..." required></textarea>
        <button type="submit">Save Note</button>
      </form>
    </div>
  </div>

  <div id="supplierModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal('supplierModal')">&times;</span>
      <h3>Add Supplier</h3>
      <form action="add_supplier.php" method="post">
        <input type="text" name="supplier_name" placeholder="Supplier Name" required>
        <input type="text" name="contact_person" placeholder="Contact Person">
        <input type="text" name="phone" placeholder="Phone">
        <input type="email" name="email" placeholder="Email">
        <textarea name="address" placeholder="Address"></textarea>
        <button type="submit">Save</button>
      </form>
    </div>
  </div>

  <div id="feedbackModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal('feedbackModal')">&times;</span>
      <h3>Submit Feedback</h3>
      <form action="submit_feedback.php" method="post">
        <select name="rating" required>
          <option value="">Rating (1–5)</option>
          <option value="1">1</option>
          <option value="2">2</option>
          <option value="3">3</option>
          <option value="4">4</option>
          <option value="5">5</option>
        </select>
        <textarea name="comments" placeholder="Your feedback..." required></textarea>
        <button type="submit">Submit</button>
      </form>
    </div>
  </div>

  <div id="complaintModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal('complaintModal')">&times;</span>
      <h3>File Complaint</h3>
      <form action="file_complaint.php" method="post">
        <textarea name="description" placeholder="Describe your complaint..." required></textarea>
        <button type="submit">Submit</button>
      </form>
    </div>
  </div>

  <div id="responseModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal('responseModal')">&times;</span>
      <h3>Add Admin Response</h3>
      <form action="add_response.php" method="post">
        <input type="number" name="complaint_id" placeholder="Complaint ID" required>
        <textarea name="message" placeholder="Response message..." required></textarea>
        <button type="submit">Submit Response</button>
      </form>
    </div>
  </div>

  <script>
    function openModal(id) {
      document.getElementById(id).style.display = 'flex';
    }
    function closeModal(id) {
      document.getElementById(id).style.display = 'none';
    }
    function generateReport() {
      alert('Generating daily inventory report...');
    }
    function logout() {
      if(confirm('Are you sure you want to logout?')) {
        window.location.href = 'logout.php';
      }
    }
  </script>
</body>
</html>