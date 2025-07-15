<?php
    session_start();
    if(isset($_SESSION["user_Username"])){
        header("Location: dashboard.php");
        exit();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,700" rel="stylesheet">
    <title>Laundry System Login</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #34495e;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            width: 300px;
            text-align: center;
        }
        h2 {
            margin-bottom: 20px;
            color: #2c3e50;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            box-sizing: border-box;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            background-color: #2ecc71;
            color: white;
            padding: 10px;
            width: 100%;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #27ae60;
        }
        button:disabled {
            background-color: #bdc3c7;
            cursor: not-allowed;
        }
        .error-message {
            color: #e74c3c;
            margin-top: 10px;
            font-size: 14px;
        }
        .success-message {
            color: #27ae60;
            margin-top: 10px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Laundry System Login</h2>
        <form id="loginForm">
            <input type="text" id="username" name="username" placeholder="Username" required>
            <input type="password" id="password" name="password" placeholder="Password" required>
            <select name="role" id="role" required>
                <option value="">Select Role</option>
                <option value="staff">Staff</option>
                <option value="admin">Admin</option>
            </select>
            <button type="submit" id="loginBtn">Login</button>
        </form>
        <div id="message"></div>
    </div>

    <script>
        $(document).ready(function() {
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                
                const loginBtn = $('#loginBtn');
                const messageDiv = $('#message');
                
                // Disable button and show loading
                loginBtn.prop('disabled', true).text('Logging in...');
                messageDiv.html('');
                
                $.ajax({
                    url: 'login_action.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 200) {
                            messageDiv.html('<div class="success-message">' + response.message + '</div>');
                            setTimeout(function() {
                                window.location.href = 'dashboard.php';
                            }, 1000);
                        } else {
                            messageDiv.html('<div class="error-message">' + response.message + '</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        messageDiv.html('<div class="error-message">Login failed. Please try again.</div>');
                        console.error('AJAX Error:', error);
                    },
                    complete: function() {
                        loginBtn.prop('disabled', false).text('Login');
                    }
                });
            });
        });
    </script>
</body>
</html>