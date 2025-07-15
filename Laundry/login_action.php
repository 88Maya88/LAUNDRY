<?php 
    include_once("db_connect.php");
    $retVal = "";
    $isValid = true;
    $status = 400;
    $data = []; 

    $username = trim($_REQUEST['username']);
    $password = trim($_REQUEST['password']);
    $role = trim($_REQUEST['role']);

    // Check fields are empty or not
    if($username == '' || $password == '' || $role == ''){
        $isValid = false;
        $retVal = "Please fill all fields.";
    }

    // Check if user exists and validate credentials
    if($isValid){
        $stmt = $con->prepare("SELECT * FROM users WHERE Username = ? AND Role = ?");
        $stmt->bind_param("ss", $username, $role);
        $stmt->execute();
        $result = $stmt->get_result();
        $obj = mysqli_fetch_object($result); 
        $stmt->close();
        
        if($result->num_rows > 0){
            // Check if password is hashed or plain text
            if(password_verify($password, $obj->Password) || $password === $obj->Password){
                $status = 200;
                $retVal = "Success.";
                $data = $obj;
                
                // Set session variables (matching dashboard.php check)
                $_SESSION['user_Username'] = $obj->Username;
                $_SESSION['users_Userid'] = $obj->UserID;
                $_SESSION['user_Role'] = $obj->Role;
                
                // Return success response
                $myObj = array(
                    'status' => $status,
                    'data' => $data,
                    'message' => $retVal,
                    'redirect' => 'dashboard.php'
                );
            } else {
                $retVal = "You may have entered a wrong username or password.";
            }
        } else {
            $retVal = "Account does not exist or role mismatch.";
        }
    }

    // Default response structure
    if(!isset($myObj)){
        $myObj = array(
            'status' => $status,
            'data' => $data,
            'message' => $retVal  
        );
    }
    
    $myJSON = json_encode($myObj, JSON_FORCE_OBJECT);
    echo $myJSON;
?>