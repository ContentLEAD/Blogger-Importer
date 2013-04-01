<?php 
function db_connect_from_file(){
    $cxn_info = file_to_array( ABSPATH . 'db_settings.txt');

    $cxn = mysql_connect($cxn_info['host'],
                         $cxn_info['user'],
                         $cxn_info['pass']);
    mysql_select_db($cxn_info['database'], $cxn);
    
    return $cxn;    
}
  
function file_to_array($filename){
    $fp = fopen($filename, 'r');
    $settings = array();

    while($line = fgets($fp)){
        $line = trim($line);
        if(substr($line, 0,1) == '#' || $line == '') continue;

        $line = explode(':', $line);
        $settings[trim($line[0])] = trim($line[1]);
    }

    return $settings;    
}              
?>
