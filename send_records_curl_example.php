<?php
require_once('send_records_class.php');
$TR = new transfer_records();

//File di log
$TR->log_filename = 'log.txt';
$TR->log_directory = 'Logs';

//Parametri di connessione al dB
$TR->servername = 'localhost:3306'; //server:porta
//$TR->dBusername = 's.sebastiani';    //username
//$TR->dBpassword = 'sergio9912!';     //password
$TR->dBusername = 'root';    //username
$TR->dBpassword = '';     //password
$TR->dBname = 'sms';         //dB

//File in locale su cui viene salvato l'ultimo id trasferito 
$TR->last_id_imported_file_path = "last_id_imported_table_issues.txt";

//Parametri della query
$TR->query_mode = 'table_view';
$TR->table = 'outcoming'; //Nome della tabella/view utilizzata nella query
$TR->id_key= 'ID'; //nome del campo chiave primaria nella tabella/view che determina il range di valori selezionarti nella query
$TR->id_range = 50; //Max numero di records che vengono trasferiti durante lo script

$TR->fields_convert_int = array();

//Parametri invio dati
$TR->send_mode = 'curl';
$TR->curl_url = 'https://www.servicecontrolroom.it/api_write_records/';
//$TR->curl_url = 'http://10.114.144.153/FAL/to_port_8080.php';
//$TR->curl_url = 'http://192.168.75.56/FAL/to_port_8080.php';

$TR->destitnation_table = 'outcoming';

$TR->send_records();
?>