<?php
namespace person;
header("Content-type: application/json; charset=utf-8");

use Exception;
use mysqli;

class Db{    
    private $db_name;
    private $server_name;
    private $userName;
    private $password;
    private $connection;

    public function __construct()
    {
        if (file_exists('config.php')){
            require_once('config.php');        
            $this->db_name = $dbName;
            $this->server_name = $host;
            $this->userName = $dbuser;
            $this->password = $dbpass;
            $this->dbConnect();
        }
    }

    public function dbConnect(){
        try{
            $connect = mysqli_connect($this->server_name, $this->userName, $this->password,  $this->db_name);
            if (mysqli_connect_error()){
                //die(mysqli_error($connect));                    
                // add something in log file
                return 0;
            } else {
                $this->connection = $connect;                
                return $connect;
            }            
        }
        catch(Exception $ex){
            // add something in log file  $ex.getmessage();            
            return 0;
        }
    }

    public function inserrtDb($tableName, $data){        
        $mySqlString = "INSERT INTO " . $tableName . " VALUES(null,'" . $data['name'] . "','" . $data['family'] . "', '" . $data['username'] . "', '" . $data['password'] . "',null)";
        var_dump($mySqlString);
        $result = mysqli_query($this->connection, $mySqlString);
        if (!$result){
            return mysqli_error($this->connection);
        } else {
            return "One Record inserted <br/>";
        }
        return $result;
    }

    public function updateDb($tableName, $updateData, $whereClause){
        $resultset = [];
        $whereClause = " WHERE " . implode(' AND ' , $whereClause);
        $setClause = " SET " . implode(',' , $updateData);

        $sqlQuery = " UPDATE " . $tableName . 
        $setClause . 
        $whereClause;        
        // die($sqlQuery);

        $result = mysqli_query($this->connection, $sqlQuery);    
        if (!$result){
            return $resultset['error'] = mysqli_error($this->connection);
        } else {
            return mysqli_affected_rows($this->connection) . " Records updated <br/>";
        }
        return $resultset['result'] = $result;
    }

    public function deleteDb($tableName, $whereClause = ["1!=1"]){
        $whereClause = " WHERE " . implode(' AND ' , $whereClause);
        $sqlQuery = " DELETE FROM  " . $tableName
        . $whereClause;        
        
        $result = mysqli_query($this->connection, $sqlQuery);
        if (!$result){
            return mysqli_error($this->connection);
        } else {
            return mysqli_affected_rows($this->connection) . " Records deleted <br/>";        
        }
        return $result;
    }

    public function getDataTable($tableName, $whereClause = null){
        // print_r($whereClause);        
        // var_dump($whereClause);

        if ($whereClause){
            $whereClause = " WHERE " . implode(' AND ' , $whereClause);
        } else {
            $whereClause = " WHERE 1";
        }
        
        $sqlQuery = " SELECT * FROM " . $tableName 
        . $whereClause
        . " ORDER BY id ASC
        ";
        // die($sqlQuery);
        $result = mysqli_query($this->connection, $sqlQuery);            
        if (!$result){
            return mysqli_error($this->connection);
        }         
        return $result;
    }

    public function showTable($tableName) {
        $result = $this->getDataTable($tableName);
        echo mysqli_num_rows($result);
        while($row = mysqli_fetch_array($result)){ //while($row = mysqli_fetch_array($result)){
            echo $row['id'] . ' - ' . $row['name'] . ' - ' . $row['family'] . ' - ' . $row['username'] . '<br/>';
            //echo $row['id'] . ' - '  . '<br/>';
        }
        return $result;
    }
    
    public function getApiData($tableName, $whereClause = null){        
        return json_encode(mysqli_fetch_all($this->getDataTable($tableName, $whereClause)));
    }
    
    public function __destruct()
    {
        $this->dbClose();
    }

    public function dbClose(){
        mysqli_close($this->connection);
        $this->connection = null;
    }
}

$tblName = "person";
$dbObj = new Db();
$data = [
    'name' => 'mohmad',
    'family' => 'sharifi',
    'username' => 'pc',
    'password' => md5('123456')    
];
// $dbObj->inserrtDb($tblName, $data);
// $dbObj->showTable($tblName);



// $a = hash("sha256", '123456');
// var_dump($a);

// if (md5('123456') == 'e10adc3949ba59abbe56e057f20f883e'){
//     echo "OK";
// }

function validateInputs($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
  }

$whereClause = [];
$updateData = [];
$delData = [];



if (isset($_POST['op']) && $_POST['op'] == 'edit' && isset($_POST['id'])){
    // echo "Edit Mode";
    if (isset($_POST['name'])){
        $updateData[] = "name = " . "'" . validateInputs($_POST['name']) . "'";
    }
    if (isset($_POST['family'])){
        $updateData[] = "family = " . "'" . validateInputs($_POST['family']) . "'";
    }
    if (intval($_POST['id'])){
        $whereClause[] = "id=" .  intval($_POST['id']); 
    }
    // filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
} 
else if (isset($_POST['op']) && $_POST['op'] == 'srch' && isset($_POST['search']) && !empty($_POST['search'])){        
    $whereClause[] = "username like " . "'%" . validateInputs($_POST['search']) . "%'";    
}
else if(isset($_POST['op']) && $_POST['op'] == 'del' && isset($_POST['id'])){
    if (intval($_POST['id'])){
        $delData[] = "id=" .  intval($_POST['id']);
    }
}
else if(isset($_POST['op']) && $_POST['op'] == 'show'){
    if (isset($_POST['name'])){
        $whereClause[] = "name = " . "'" . validateInputs($_POST['name']) . "'";
    }
    if (isset($_POST['family'])){
        $whereClause[] = "family = " . "'" . validateInputs($_POST['family']) . "'";    
    }
    if (isset($_POST['username'])){
        $whereClause[] = "username = " . "'" . validateInputs($_POST['username']) . "'";    
    }
    if (isset($_POST['password'])){
        $whereClause[] = "password = " . "'" . md5(validateInputs($_POST['password'])) . "'";    
    }
} else {
    return json_encode("Command Not Found!!!");
}

// $dbObj->getDataTable($tblName, $whereClause);


if (count($updateData)){
    return $dbObj->updateDb($tblName, $updateData, $whereClause);
}
else if (count($whereClause)){
    return $dbObj->getApiData($tblName, $whereClause);
} 
else if (count($delData)){    
    return $dbObj->deleteDb($tblName, $delData);
}