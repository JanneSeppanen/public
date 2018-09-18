<?php

## This script searches recent matching DOI deposits from CrossRef, given institutional affiliation (e.g. University of Jyväskylä) and person's name, and alerts any discoveries to given recipient. Can be used by institution's Current Research Information System managers, to automatically find their institution's researcher's new publications to enter into their own databases and archives. 

error_reporting(E_ALL);
date_default_timezone_set('Europe/Helsinki');

#ob_implicit_flush(true);
#ob_start();
echo "RUNNING HAKUROPOTTI";

#echo "";

echo "\nSTART LOGGER";
#echo "";

require dirname(__FILE__).'/hakuropotti_conf.php';

require dirname(__FILE__).'/PHPMailer/PHPMailerAutoload.php';

require dirname(__FILE__).'/my_mailer.php';

require dirname(__FILE__).'/log_mailer.php';

echo "\nincluding functions done";
#echo "";
ini_set('xdebug.var_display_max_depth' ,-1 );
ini_set('xdebug.var_display_max_children', -1);
ini_set('xdebug.var_display_max_data',-1);
ini_set("pcre.recursion_limit", "524");
set_time_limit(0);

echo "\nConf Initializations done";
#echo "";

	$alkuaika=time();
	
	###   STAFF is defined in hakuropotti_conf.php configuration file. It must be a *php serialized* array of potential authors you want to find articles by, where each person has at least entries ['name']=>'Sukunimi, Etunimi', ['email]='emailosoite@example.com'  #############
	###   This script does contact the author, but lists the authors's email in the "article found" -message that goes out to RECIPIENT and SUPPORT #######################

$staff=unserialize(STAFF);
#var_dump($staff);
#echo count($staff);
#echo "";
echo "\nSTAFF read";
#echo "";

$pid=SUPPORT;

$journal='container-title';
$total='total-results';
$previous=unserialize(PREVIOUS);
$alreadydone=array();
$cr_req_time=0;

foreach($staff as $id=>$person) {

#echo '\nstarting PERSON';
#echo "";
	echo "\n\n";
	echo $person['name']." (".$person['email'].")";

	$name=explode(',',$person['name']);
	$firstname='';
	if(isset($name[1])){$firstname=trim($name[1]);}
	$lastname=str_replace(' ','+',urlencode(trim($name[0])));
	$toissapaiva=time()-(HORIZON*24*60*60);
	$fromdate=date('Y-m-d',$toissapaiva);
	
	###echo ('http://api.crossref.org/works?query.author='.$lastname.'&filter=from-created-date:'.$fromdate.'&rows=100');
	###echo '\n';
	if((time()-$cr_req_time)<2){sleep(1);}
	$cr_req_time=time();
	$cr_response = json_decode(file_get_contents('http://api.crossref.org/works?query.author='.$lastname.'&filter=from-created-date:'.$fromdate.'&rows=100&mailto='.SUPPORT));
#echo '\nCross Ref request done';
	
echo "\nTOTAL RESULTS: ".$cr_response->message->$total;
echo "\n";
#ob_flush();	
	if ($cr_response->message->$total>0) {
		foreach($cr_response->message->items as $item) {	

			if(array_search($item->URL,$previous)===false && array_search($item->URL,$alreadydone)===false && strpos(RECORDED,$item->DOI)===false) { # only proceed if URL has not been sent in previous run or in this run
				$namematch=false;
				$affilmatch=false;
					foreach($item->author as $author) {
						$cr_firstname='';
						if(isset($author->given)) {
							$cr_firstname=trim($author->given,' .');
							if(strpos($cr_firstname,".") && strlen($cr_firstname)<5) {
								$cr_firstname=substr($cr_firstname,0,1);
							}
						}					
						if(isset($cr_firstname) && (strpos($cr_firstname,$firstname)!==false || substr($firstname,0,1)==$cr_firstname)&& isset($author->family) && strpos($author->family,$lastname)!==false) {
							$namematch=true;
						}
						foreach($author->affiliation as $affiliation) {
							$affiliation=(array)$affiliation;
							//var_dump($affiliation);

              ### First check if CrossRef metadata says one of the authors is from an institution we are interested in. This metadata is patchy, affiliations are only occasionally correctly entered in Crossref. Therefore the script also does a screen-scrape at DOI landing page, looking for likely affiliation entry there
							
							
							### Institution name, especially if it contains non-english characters, may be spelled in various ways in CrossRef. For example Jyväskylä / Jyvaskyla / Jyv%C3%A4skyl%C3%A4 . Enter the different ways it may appear in the comparator clause below
							
							if(isset($affiliation['name']) && (strpos($affiliation['name'],'Jyvaskyla')!==false || strpos($affiliation['name'],'Jyväskylä')!==false || strpos($affiliation['name'],urlencode('Jyväskylä'))!==false )) {
								//##echo '<strong>AFFILIAATIO LÖYTYI</strong>\n';
								$affilmatch=$affiliation['name'];
								//*var_dump($affilmatch);
								//##echo '\n';
							}
						}										
					}


 ####  If author name matches OR if institutional affiliation found, proceed....
			
				if($namematch || $affilmatch)	{
								
					if($affilmatch==false){
						$osoite=$item->URL;
						if(strpos($item->DOI,'10.1103/')===0) {
							$loppuosa=explode('/',$item->link[0]->URL);
							$loppuosa=end($loppuosa);
						$osoite='https://journals.aps.org/prc/authors/10.1103/'.$loppuosa;  ## fix for American Physical Society articles, to get to the correct landing page
						}
		//##echo('etsi nimeä täältä: '.$osoite.'\n');				
						$avaa=curl_init($osoite);
						curl_setopt($avaa, CURLOPT_HEADER, 1); 
						curl_setopt($avaa, CURLOPT_RETURNTRANSFER, 1); 
						curl_setopt($avaa,CURLOPT_FOLLOWLOCATION,1);
						curl_setopt($avaa,CURLOPT_MAXREDIRS,10);
						curl_setopt($avaa, CURLOPT_COOKIEJAR, "/cookie.txt");
			 			curl_setopt($avaa, CURLOPT_COOKIEFILE, "/cookie.txt");
			 			curl_setopt($avaa, CURLOPT_SSL_VERIFYPEER, false);
						$doisivu = curl_exec($avaa); 			
						curl_close($avaa);
#echo '\nDOI page download done';		


							### Institution name, especially if it contains non-english characters, may be spelled in various ways in article landing page. For example Jyvaskyla / Jyväskylä / Jyv%C3%A4skyl%C3%A4 / Jyv&auml;skyl&auml . Enter the different ways it may appear in the match clause below

            ### If this script runs from institution's own IP range, the landing page may contain text like "Access provided by Univwersity of Jyväskylä". We must try to ignore that and only find if institution's name appears to be associated with an author as an affiliation. Therefore the script only matches if institution name appears in the landing page AFTER the author name, within 3000 characters, and not part of a tag that just identifies access


						preg_match('/(\s|>)'.$lastname.'(.{1,3000})(?<!"WT\.site_id_name" content="University of )(Jyvaskyla|Jyväskylä|Jyv%C3%A4skyl%C3%A4|Jyv&auml;skyl&auml;)+/usi',$doisivu,$match);
//##echo '<div style="border:solid red 1px;">';
						if(!empty($match)) {
						//##echo 'AFFILIAATIO LÖYTYI';	
						$affilmatch=$match[2];			
						}
						else {
						//##echo 'EI LÖYDY';	
						}		
						#var_dump($match)	;
						# LOOK FOR PDF DOWNLOAD ####
						#preg_match('/href="([^"]*")[^>]*>\s*PDF\s*</usi',$doisivu,$match);
						#var_dump($match);			
						//##echo '</div>';	
						
						$doisivu=str_replace('%E2%80%90','-',$doisivu);
						$doisivu = preg_replace('/</','&lt',$doisivu);
						$doisivu = preg_replace('/>/','&gt',$doisivu);
						$doisivu = preg_replace('/&/','&amp',$doisivu);
		
					}	
					//var_dump($item);	
					###echo '<div style="border:solid red 2px;">'.$doisivu.'</div>';
		
					#### IF INSTITUTION FOUND, SEND EMAIL TO RECIPIENT AND SUPPORT. Email content is defined in my_mailer.php ##################
					if($affilmatch){ 
						##echo '\n\n';
						##echo $person['name'];
						##echo '\n';
						##echo $item->URL;
#echo '\ntry to send email';
						
					 	$success=my_mailer($item->title[0],$item->URL,$item,$person['name'],$person['email']);
					 //var_dump($success);	
echo "\nemail sent: ".$success;	
echo "\ntime stamp: ".date('Y-m-d H:i:s');				 			 
echo "\ntitle     : ".$item->title[0];
echo "\nlink      : ".$item->URL;
#echo "\n".array_search($item->URL,$alreadyDone);
#var_dump($alreadydone);
echo "\n\n";			
						$alreadydone[]=$item->URL;	 
					}
					
					#######   IF ONLINE DATE IS IN THE FUTURE, NAME MATCHES, BUT AFFILIATION UNKNOWN, STORE FOR FUTURE CHECK  ########
					$online= 'published-online';
					$date='date-parts';
					$onlinedate='';
					if(isset($item->$online->$date)){
						$onlinedate=$item->$online->$date;	
						if(!isset($onlinedate[0][2])) {$onlinedate[0][2]=28;}
						if(!isset($onlinedate[0][1])) {$onlinedate[0][1]=12;}
						$onlinedate=$onlinedate[0][2].'-'.$onlinedate[0][1].'-'.$onlinedate[0][0];
						if(strtotime($onlinedate)>time() && $namematch && !$affilmatch){
							echo "\nMONITOR alert sent about:";
							echo "\ntitle     : ".$item->title[0];
							echo "\nlink      : ".$item->URL;
							echo "\ntime stamp: ".date('Y-m-d H:i:s');
							$success=log_mailer($item->title[0],$item->URL,$item,$person['name'],$person['email']);										
							#var_dump($item);
							#exit;
						}
					} 													
				}	
			}
			elseif(array_search($item->URL,$previous)!==false || array_search($item->URL,$alreadydone)!==false) {
				echo "\n".$item->URL." ALREADY SENT\n";
			}
			elseif(strpos(RECORDED,$item->DOI)!==false) {
				echo "\nALREADY IN CRIS\n";
				echo "\ntitle     : ".$item->title[0];
				echo "\nlink      : ".$item->URL;
				echo "\ntime stamp: ".date('Y-m-d H:i:s');
				$item->JYX="already in CRIS";
				$success=log_mailer($item->title[0],$item->URL,$item,$person['name'],$person['email']);	
				$alreadydone[]=$item->URL;				
			}
		}
	}	
}
echo 'DONE';


### Save the list of DOIs sent to CRIS managers, so we can skip them if found again in later runs

$allsofar=array_merge($previous,$alreadydone);
file_put_contents(FILEROOT.'/alreadydone.txt', serialize($allsofar));		
#ob_end_flush();
exit(0);
?>