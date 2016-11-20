#!/usr/bin/php -q
<?php
/**
 * Autor cleber.campanel@gmail.com
 * 2016-11-06 Inicio dos trabalhos.
 */

set_time_limit(4000);
define('POSTLINK', 'http://localhost:8000/api/tickets');
define('FILELOG', '/var/log/readingmailpostticket.log');
define('WAITINGTIME', 5);

include 'configMail.php';

function main() {
    //while(true){
        //logSave('<<<<< Inicio >>>>>');
        $newEmails = getMails();

        if(count($newEmails) > 0){
            //logSave('**** Tem email');
            foreach ($newEmails as $email) {
              sendTicket($email);
            }

            //
        }else {
            //logSave("-Sem emails...");
            //logSave("WAITINGTIME: ".WAITINGTIME);
            //sleep(WAITINGTIME);
        }

        flush();
        //logSave('*** Fim ***');
    //}

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
    foreach($emails as $email_number) {
    $overview = imap_fetch_overview($inbox,$email_number,0);
    $structure = imap_fetchstructure($inbox, $email_number);

    if(isset($structure->parts) && is_array($structure->parts) && isset($structure->parts[1])) {
      $part = $structure->parts[1];
      $message = imap_fetchbody($inbox,$email_number,1);

      if($part->encoding == 3) {
        //logSave('entrou no 1');
        $message = imap_base64($message);
      } else if($part->encoding == 1) {
        //logSave('entrou no 2');
        $message = imap_8bit($message);
      } else {
        //logSave('entrou no 3');
        $message = imap_qprint($message);
      }
    }

    $headerInfo = imap_headerinfo($inbox,$email_number);
    $from = $headerInfo->from;

    foreach ($from as $id => $object) {
      $mail['contact_name'] = imap_utf8($object->personal);
      $mail['emails_to'] = $object->mailbox . "@" . $object->host;
    }

    $mail['title'] =  imap_utf8($overview[0]->subject);
    $mail['description'] =  $message;

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

  $curl = curl_init();
  // Set some options - we are passing in a useragent too here
  curl_setopt_array($curl, array(
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_URL => POSTLINK,
      CURLOPT_USERAGENT => 'cURL Request',
      CURLOPT_POST => 1,
      CURLOPT_POSTFIELDS => $data
  ));
  // Send the request & save response to $resp
  $resp = curl_exec($curl);
  // Close request to clear up some resources
  curl_close($curl);
}

/**
 * Sava mensagem no log.
 * @param $msg
 */
function logSave($msg) {

    $msg = "[".date('Y-m-d H:i:s')."] ".$msg;
    $contents =  json_encode($msg)."\n";
    $filename = FILELOG;
    //file_put_contents($filename, $contents, FILE_APPEND);
}

main();
