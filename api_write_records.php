<?php
/*
  Description:
   - gets records from post request ('records' field)  
   - connects to MySql or Postgress dB and saves records in tabel specified on post request ('table' field)
   - returns fail/succes ad ID of last record written 
  Version: 1.0.1
  Author: Sergio Sebastiani
  Date: 25-02-2022
*/

//define db Type
//$db_type = "Postgress";
$db_type = "MySql";

$servername = '10.115.1.95';
$port = "3306";
$username = "root";
$password = "";
$dbname = "sms";


//define response type
$response = array();

//get data from curl request
$data = json_decode(file_get_contents('php://input'), true);




//set values
$records = json_decode($data['records'], true);
$table = $data['table'];
$response['num_records'] = count($records);
$responce['last_insert_record'] = -1;



foreach ($records as $key => $record) {


  
  //Insert one record
  if($db_type == "Postgress"){
    
    //Create insert query
    $insert_sql = "INSERT INTO " . $table . " ( ";
    foreach ($record as $col_name => $value) {
      $insert_sql.= " " . $col_name . ",";
    }
    $insert_sql[strlen($insert_sql)-1] = ')';
    $insert_sql.= " VALUES (";
    foreach ($record as $col_name => $value) {
      $insert_sql.= " '" . $value . "',";
    }
    $insert_sql[strlen($insert_sql)-1] = ")";
    //echo $insert_sql;

    //Write record
    $dbconn = pg_connect("host=".$servername." port=".$port." dbname=".$dbname." user=".$username." password=".$password);
    if(!$dbconn){$response['responce_type'] = 'fail';}
    $result = pg_query($dbconn, $insert_sql);
  }

  if($db_type == "MySql"){

    //Create insert query
    $insert_sql = "INSERT INTO `" . $table . "` ( ";
    foreach ($record as $col_name => $value) {
      $insert_sql.= " `" . $col_name . "`,";
    }
    $insert_sql[strlen($insert_sql)-1] = ')';
    $insert_sql.= " VALUES (";
    foreach ($record as $col_name => $value) {
      $insert_sql.= " '" . $value . "',";
    }
    $insert_sql[strlen($insert_sql)-1] = ")";
    //echo $insert_sql;

    //Write record
    $dbconn = mysqli_connect($servername, $username, $password, $dbname);
    if(!$dbconn){$response['responce_type'] = 'fail_dbconn';}
    $result = mysqli_query($dbconn, $insert_sql);
    //print_r($result);
  } 

  
  if (!$result) {
    $response['responce_type'] = 'fail_query';
    break;
  }
  else{
    $response['responce_type'] = 'success';
    $response['last_insert_record'] = $record['ID'];
  }

}


//close dB connection
if($db_type == "Postgress"){pg_close($dbconn);}
if($db_type == "MySql"){mysqli_close($dbconn);}


//echo json output
echo json_encode($response);

?>