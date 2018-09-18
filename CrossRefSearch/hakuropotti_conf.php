<?php
define("FILEROOT",dirname(__FILE__));
define("PREVIOUS", file_get_contents(FILEROOT.'/alreadydone.txt'));	# PREVIOUS is a *php serialized* array of URLs sent to recipient in previous runs

define("HORIZON", 2);  														# Search records created HORIZON days before present moment. NOTE: 2 because timezone diff
define("SMTPHOST",'your smtp server here')	;		# Set SMTP server					
define("USER", '---');												# Authenticate user for SMTP server
define("PW", '----');												# Authenticate password for SMTP server

define("RECIPIENT", 'your CRIS manager email address here, where alerts will be sent');

define("SUPPORT", 'the support email for this script here. This address is also listed in requests going out to CrossRef');							# Who is the support and Reply-To address

define("STAFF", file_get_contents(FILEROOT.'/your array of person names and email addresses, php serialized array stored as .txt string'));			# STAFF must be a *php serialized* array of persons, where each person has at least entries ['name']=>'Sukunimi, Etunimi', ['email]='emailosoite@example.com'  #############

define("RECORDED", file_get_contents(FILEROOT.'/your comma separated list of DOIs to skip, as .txt string'));		# RECORDED is string containing DOIs that are already saved in organization's CRIS, and therefore will be skipped if found in CrossRef

?>