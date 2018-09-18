<?php
function mailer($to,$title,$url) {
$shorttitle=substr($title,0,60);
// Message
$message = '
<html>
<head>
  <title>'.$title.'</title>
</head>
<body>
  <p><a href="'.$url.'">'.$title.'</a></p>
  
</body>
</html>
';

// To send HTML mail, the Content-type header must be set
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-type: text/html; charset=utf-8';

// Additional headers
$headers[] = 'To:  Nobody <nobody@example.com>';
$headers[] = 'From: Nobody <nobody@example.com>';

// Mail it
return(mail($to, $shorttitle, $message, implode("\r\n", $headers)));
}


?>