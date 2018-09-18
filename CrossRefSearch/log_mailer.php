<?php
function log_mailer($title,$url,$item,$person,$person_email) {
	$shorttitle=substr($title,0,60);
	
	$mail = new PHPMailer;
		if('condition here if you have to setup smtp email') {	
	
	### SMTPHOST, USER and PW defined in 	hakuropotti_conf.php
			
		//$mail->SMTPDebug = 3;                               // Enable verbose debug output
		$mail->isSMTP();                                      // Set mailer to use SMTP
		$mail->Host = SMTPHOST;  										// Specify main and backup SMTP servers
		$mail->SMTPAuth = true;                               // Enable SMTP authentication
		$mail->Username = USER;                 				// SMTP username
		$mail->Password = PW;                          	// SMTP password
		$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
		$mail->Port = your port number ;                                    // TCP port to connect to
	}
		
	$mail->CharSet = 'UTF-8';
	$mail->setFrom(SUPPORT, 'xx');
	$mail->addAddress(SUPPORT, 'xx');   			// support monitoring
	$mail->addReplyTo(SUPPORT, 'xx');
	
	$mail->isHTML(true);                                  // Set email format to HTML
	
	$mail->Subject = "MONITOR: ".$shorttitle;
	
	$journal='container-title';
	$j=$item->$journal;	
	$license='';
	if(isset($item->license[0]->URL)) {		
		$license=$item->license[0]->URL;
	}	

	$online= 'published-online';
	$date='date-parts';
	$onlinedate='';
	if(isset($item->$online->$date)){
		$onlinedate=$item->$online->$date;	
		$onlinedate=$onlinedate[0][2].'-'.$onlinedate[0][1].'-'.$onlinedate[0][0];
	}

	$issueddate='';
	if(isset($item->issued->$date)){
		$issueddate=$item->issued->$date;	
		if(!isset($issuedate[0][2])) {$issuedate[0][2]=' ';}
		if(!isset($issuedate[0][1])) {$issuedate[0][1]=' ';}
		if(!isset($issuedate[0][0])) {$issuedate[0][0]=' ';}		
		$issueddate=$issueddate[0][2].'-'.$issueddate[0][1].'-'.$issueddate[0][0];
	}
	
	$mail->Body    = 
	'<h2>MONITOR ALERT</h2><br>
	<a href="'.$url.'">'.$title.'</a>
	<br>
	POSSIBLE AUTHOR (verify): '.$person.' ('.$person_email.')
	<br>	
	SOURCE: '.$j[0].'
	<br>
	ONLINE: '.$onlinedate.'
	<br>	
	ISSUED: '.$issueddate.'
	<br>		
	DOI: '.$item->DOI.'
	<br>
	URL: '.$item->URL.'
	<br>
	TYPE: '.$item->type.'
	<br>';
	
	$mail->AltBody = 	
 $title."\n
 MONITOR ALERT:
 POSSIBLE AUTHOR (verify): ".$person." (".$person_email.")	
 SOURCE: ".$j[0]."
 ONLINE: ".$onlinedate."
 ISSUED: ".$issueddate."
 DOI: ".$item->DOI."
 URL: ".$item->URL."
 TYPE: ".$item->type."
 LICENSE: ".$license;
	
	if(!$mail->send()) {
	    return 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo;
	} else {
	    return 'Message has been sent';
	}
}
?>