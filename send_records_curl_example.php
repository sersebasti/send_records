<?php
require_once('send_records_class.php');
$TR = new transfer_records();

//File di log
$TR->log_filename = 'log.txt';

//Parametri di connessione al dB
$TR->servername = '127.0.0.1:43306'; //server:porta
//$TR->dBusername = 's.sebastiani';    //username
//$TR->dBpassword = 'sergio9912!';     //password
$TR->dBusername = 'd.attanasi';    //username
$TR->dBpassword = 'Alstom_1267';     //password
$TR->dBname = 'omc4dl_data';         //dB

//File in locale su cui viene salvato l'ultimo id trasferito 
$TR->last_id_imported_file_path = "last_id_imported_table_issues.txt";

//Parametri della query
$TR->query_mode = 'table_view';
$TR->table = 'view_omc4dl_table_issues'; //Nome della tabella/view utilizzata nella query
$TR->id_key= 'ID_ISSUE'; //nome del campo chiave primaria nella tabella/view che determina il range di valori selezionarti nella query
$TR->id_range = 500; //Max numero di records che vengono trasferiti durante lo script

$TR->fields_convert_int = array(
  'OPENING_DATETIME',
  'CLOSURE_DATETIME',
  'LAST_UPDATE_DATETIME'
);

//Parametri invio dati
$TR->send_mode = 'curl';
$TR->curl_url = 'http://10.115.1.95/scata_receive/';
//$TR->curl_url = 'http://10.114.144.153/FAL/to_port_8080.php';
//$TR->curl_url = 'http://192.168.75.56/FAL/to_port_8080.php';

$TR->destitnation_table = 'omc4dl_table_issues_copy';

$TR->send_records();
?>