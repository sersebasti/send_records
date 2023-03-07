<?php
/*
  Description:
   - gets records from MySql dB 
   - if specified from fields_convert_int array it cast to integer the selected records
   - send mode option ftp: save records (json format) on server toward ftp
   - send mode option curl: performs a curl post request to server with records attached (json format)  
  Version: 1.1.1
  Author: Sergio Sebastiani
  Date: 07-03-2023
*/

class transfer_records {
  //log filename
  public $log_filename;
  public $log_directory;

  //Locale file where is saved ID value of the last record sent  
  public $last_id_imported_file_path;

  //Max numeber of records to be considered
  public $id_range;

  //Connection to source dB parameters 
  public $serveramen;     //server:porta
  public $dBusername;     //username
  public $dBpassword;     //password
  public $dBname;         //dB
  
  //Query parameters
  public $query_mode;
  public $table; //table or view name used in the query
  public $query; //input nesterd query to use;
  public $id_key; //primary key name used in the query
 
  //Send mode option
  public $send_mode;

  //Parametri connessione ftp
  public $ftp_username;  //username
  public $ftp_userpass;  //password
  public $ftp_server;    //server
  public $ftp_folder;    //Cartella in cui vengono salvati file .json

  //Parametri invio dati curl
  public $curl_url;
  public $destitnation_table;

  //Parametri eventuali campi che vanno convertiti in int
  public $fields_convert_int = array();
  
  function send_records(){
    //set log file path
    $log_date_folder = dirname(__FILE__).'/'.$this->log_directory.'/'.date("Y-m-d");
    if(!is_dir($log_date_folder)){mkdir($log_date_folder, 0777, true);}
    $log_path = $log_date_folder.'/'.$this->log_filename;

    $log_msg = '';
    echo $log_msg.'<br>';
    file_put_contents($log_path, date("Y-m-d H:i:s") . ': '.$log_msg.PHP_EOL,FILE_APPEND );

    $log_msg = 'Start send records' ;
    echo $log_msg.'<br>';
    file_put_contents($log_path, date("Y-m-d H:i:s") . ': '.$log_msg.PHP_EOL,FILE_APPEND );

    //dB connection
    $conn = mysqli_connect($this->servername,$this->dBusername,$this->dBpassword, $this->dBname);
    if (!$conn) {die("connection failed: " . mysqli_connect_error());}
    else{echo 'connected to dB: '.$this->dBname.'<br>';}
    
    //create file with last index if not present
    if (!file_exists(dirname(__FILE__).'/'.$this->last_id_imported_file_path)) {file_put_contents(dirname(__FILE__).'/'.$this->last_id_imported_file_path, '-1');}

    //create - execute query
    $start_id_query = file_get_contents(dirname(__FILE__).'/'.$this->last_id_imported_file_path)+1;
    $log_msg = 'Start id query: '. $start_id_query; 
    echo $log_msg.'<br>';
    file_put_contents($log_path, date("Y-m-d H:i:s") . ': '.$log_msg.PHP_EOL,FILE_APPEND );


    if($this->query_mode == "table_view"){
      $sql = "SELECT * FROM $this->table WHERE $this->id_key >= $start_id_query  ORDER BY $this->id_key ASC LIMIT $this->id_range";
    }
    else if($this->query_mode == "query"){
      $sql = "SELECT * FROM (" . $this->query . " ) AS T WHERE T.$this->id_key >= $start_id_query ORDER BY T.$this->id_key ASC LIMIT $this->id_range";
    }
    else{die('no query mode defined');}
    
    echo 'sql: ' . $sql . '<br>';
    


    $result = mysqli_query($conn, $sql);
    if ($result === false) {die("Error query: " . $sql);}
    $arr = mysqli_fetch_all($result, MYSQLI_ASSOC);
    $num_rows = mysqli_num_rows($result);

    $log_msg = "Selected records number: ".$num_rows;
    echo $log_msg.'<br>';
    file_put_contents($log_path, date("Y-m-d H:i:s") . ': '.$log_msg.PHP_EOL,FILE_APPEND );
    
    //if there are new records
    if($num_rows > 0){

      //Cast to string particular records
      //On destination Postgress dB could be different format
      foreach ($arr as $key => $value) {
        foreach($this->fields_convert_int as $field_name){
          $arr[$key][$field_name] = strval($arr[$key][$field_name]);
        }
      }
      echo "Casted to string particular records".'<br>';
      
      if($this->send_mode == 'ftp'){
       //Save records on server toward ftp with .json format 
       $myJSON = json_encode($arr);
       $last_selected_id = $arr[$num_rows-1]['ID'];
       $this->ftp_filename = 'records_'.$start_id_query.'_'.$last_selected_id.'.json';
       if( file_put_contents('ftp://'.$this->ftp_username.':'.$this->ftp_userpass.'@'.$this->ftp_server.'/'.$this->ftp_folder.'/'.$this->ftp_filename,$myJSON) ){
        $log_msg = "Saved records toward ftp into file: ".$this->ftp_filename;
        echo $log_msg.'<br>';
        file_put_contents($log_path, date("Y-m-d H:i:s") . ': '.$log_msg.PHP_EOL,FILE_APPEND );
        
        //Update local file with last imported primary key value
        file_put_contents($this->last_id_imported_file_path, $last_selected_id);
        $log_msg = "Up dated last saved on file: ".dirname(__FILE__).'/'.$this->last_id_imported_file_path;
        echo $log_msg.'<br>';
        file_put_contents($log_path, date("Y-m-d H:i:s") . ': '.$log_msg.PHP_EOL,FILE_APPEND );
       }
       else{
        $log_msg = "Error saving file: ".$this->ftp_filename . " into " . $this->ftp_folder;
        echo $log_msg.'<br>';
        file_put_contents($log_path, date("Y-m-d H:i:s") . ': '.$log_msg.PHP_EOL,FILE_APPEND );
       }
      }
      if($this->send_mode == 'curl'){
        
        foreach($arr as $key => $value){
          $encoded_arr[] = array_map('utf8_encode', $value);
        }

        $data = array(
          "table" => $this->destitnation_table,
          "records" => json_encode($encoded_arr)
        );
        $myJSON = json_encode($data, JSON_FORCE_OBJECT);
      
        
      
        $options = array(
          CURLOPT_RETURNTRANSFER => true,      // return web page
          CURLOPT_HEADER         => false,     // don't return headers
          CURLOPT_FOLLOWLOCATION => true,      // follow redirects
          CURLOPT_ENCODING       => "",        // handle all encodings
          CURLOPT_USERAGENT      => "spider",  // who am i
          CURLOPT_AUTOREFERER    => true,      // set referer on redirect
          CURLOPT_CONNECTTIMEOUT => 120,       // timeout on connect
          CURLOPT_TIMEOUT        => 120,       // timeout on response
          CURLOPT_MAXREDIRS      => 10,        // stop after 10 redirects
          CURLOPT_SSL_VERIFYPEER => false,     // Disabled SSL Cert checks
          CURLOPT_SSL_VERIFYHOST => false,     // Disabled SSL Cert checks
          CURLOPT_POSTFIELDS     => $myJSON,
          CURLOPT_HTTPHEADER => array('Content-Type:application/json')
        );

        $ch = curl_init( $this->curl_url );
        curl_setopt_array( $ch, $options );
        $content = curl_exec( $ch );
        //$err     = curl_errno( $ch );
        //$errmsg  = curl_error( $ch );
        //$header  = curl_getinfo( $ch );
        curl_close( $ch );
        echo '<p>start curl response from: '.$this->curl_url.'<p>';  
        echo $content;

        $responce = json_decode($content, true);
        //print_r($responce);

        if($responce['last_insert_record']){
          $log_msg = "Saved records with curl into destination table: ".$this->destitnation_table;
          echo $log_msg.'<br>';
          file_put_contents($log_path, date("Y-m-d H:i:s") . ': '.$log_msg.PHP_EOL,FILE_APPEND );
        
          //Update local file with last imported primary key value
          file_put_contents(dirname(__FILE__).'\\'.$this->last_id_imported_file_path, $responce['last_insert_record']);
          $log_msg = "Up dated last imported record (".$responce['last_insert_record'].") on file: ".$this->last_id_imported_file_path;
          echo $log_msg.'<br>';
          file_put_contents($log_path, date("Y-m-d H:i:s") . ': '.$log_msg.PHP_EOL,FILE_APPEND );
        }
        else{
          $log_msg = "Error saving records to table: ".$this->destitnation_table;
          echo $log_msg.'<br>';
          file_put_contents($log_path, date("Y-m-d H:i:s") . ': '.$log_msg.PHP_EOL,FILE_APPEND );
        }

        echo '<p>stop curl response: '.$this->curl_url.'<p>';
      }      
    }
    else{
      $log_msg = "No records greater then: ".($start_id_query-1);
      echo $log_msg.'<br>';
      file_put_contents($log_path, date("Y-m-d H:i:s") . ': '.$log_msg.PHP_EOL,FILE_APPEND );
    }
    
    //close dB connection 
    mysqli_close($conn);
    echo 'closed connection to dB: '.$this->dBname.'<br>';

    $log_msg = 'Stop send records';
    echo $log_msg.'<br>';
    file_put_contents($log_path, date("Y-m-d H:i:s") . ': '.$log_msg.PHP_EOL,FILE_APPEND );
  }
}
?>