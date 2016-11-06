#!/usr/bin/php -q
<?php
/**
 * Autor cleber.campanel@gmail.com
 * 2016-11-06 Inicio dos trabalhos.
 */

set_time_limit(4000);
define('POSTLINK', 'http://localhost:8080/dihelp/ticket');
define('FILELOG', '/var/log/readingmailpostticket.log');
define('WAITINGTIME', 5);

include 'configMail.php';

function main() {
    while(true){
        logSave('Inicio');

        $newEmails = getMails();

        //var_dump($newEmails);
        if($newEmails){
            logSave('Tem email:');

            //sendTicket($data);
        }else {
            logSave("Sem emails...");
            logSave("WAITINGTIME: ".WAITINGTIME);
            sleep(WAITINGTIME);
        }

        flush();
        logSave('Fim');
    }

}

/**
 * Get nos emails nao lidos e marcar o email como lido
 * @return array|null
 */
function getMails(){
    // Connect to gmail
    $imapPath = '{imap.gmail.com:993/imap/ssl}INBOX';


    // try to connect
    $inbox = imap_open($imapPath,MAILUSERNAME,MAILPASSWORD) or die('Cannot connect to Gmail: ' . imap_last_error());

    // Apenas emails não lidos
    $emails = imap_search($inbox,'UNSEEN');

    $output = array();
    //var_dump($emails);

    if($emails != false){
        foreach($emails as $idMail) {

            $headerInfo = imap_headerinfo($inbox,$idMail);

            $mail['subject'] = $headerInfo->subject;
            $mail['toaddress'] = $headerInfo->toaddress;
            $mail['date'] = $headerInfo->date;
            $mail['message'] = imap_fetchbody($inbox, $idMail, 1, FT_INTERNAL);
            $output[] = $mail;

        }
    }

    // colse the connection
    imap_expunge($inbox);
    imap_close($inbox);
    return $output;
}

/**
 * Envia dados para uma url
 * @param $data
 * @return mixed
 */
function sendTicket($data) {

    $ch = curl_init(POSTLINK);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data))
    );

    $result = curl_exec($ch);
    return $result;
}

/**
 * Sava mensagem no log.
 * @param $msg
 */
function logSave($msg) {

    $msg = "[".date('Y-m-d H:i:s')."] ".$msg;
    $contents =  json_encode($msg)."\n";
    $filename = FILELOG;
    file_put_contents($filename, $contents, FILE_APPEND);
}

main();

?>
