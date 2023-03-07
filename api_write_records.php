<?php
//define response type
$response = array();

//get data from curl request
$data = json_decode(file_get_contents('php://input'), true);

//set start values
$records = json_decode($data['records'], true);
$table = $data['table'];
$response['num_records'] = count($records);
$responce['last_insert_record'] = -1;

$dbname = getenv("POSTGRES_DBNAME");
$username = getenv("POSTGRES_USERNAME");
$host = getenv("POSTGRES_HOST");
$port = getenv("POSTGRES_PORT");
$password = getenv("POSTGRES_PASSWORD");

//open dB connection
$dbconn = pg_connect("host=".$host." port=".$port." dbname=".$dbname." user=".$username." password=".$password);
if(!$dbconn){$response['responce_type'] = 'fail';}


foreach ($records as $key => $record) {

  //Create insert query
  $insert_sql = "INSERT INTO " . $table . " ( ";
  foreach ($record as $col_name => $value) {
    $insert_sql.= " " . strtolower($col_name) . ",";
  }
  $insert_sql[strlen($insert_sql)-1] = ')';
  $insert_sql.= " VALUES (";
  foreach ($record as $col_name => $value) {
    $insert_sql.= " '" . $value . "',";
  }
  $insert_sql[strlen($insert_sql)-1] = ")";

  //Insert one record
  $result = pg_query($dbconn, $insert_sql);

  if (!$result) {
    $response['responce_type'] = 'fail';
    break;
  }

  else{
    $response['responce_type'] = 'success';
    $response['last_insert_record'] = $record['ID'];
  }

}


//close dB connection
pg_close($dbconn);

//echo json output - modifica test git
echo json_encode($response);

?>