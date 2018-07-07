<?php

$issn="1471-2954" # this is issn for Proceedings B, as an example
$fromdate="2018-01-01";
$untildate="2018-07-31";
$cursor='*';

$solved_items=array();
$total_items=1;

$ti="total-results";
$nc="next-cursor";
$ct="container-title";

$oa_count=0;

while(count($solved_items)<$total_items) {

  $cr_target = json_decode(file_get_contents('https://api.crossref.org/journals/'.$issn.'/works?filter=from-pub-date:'.$fromdate.',until-pub-date:'.$untildate.',type:journal-article&select=DOI,title,publisher,author,created,container-title&rows=1000&sort=published&order=asc&cursor='.$cursor.'&mailto=janne.seppanen@peerageofscience.org'));
  
$journal = $cr_target->message->items[0]->$ct;
$publisher = $cr_target->message->items[0]->publisher;

  $total_items=$cr_target->message->$ti;

  foreach($cr_target->message->items as $id=>$entry) {

      $doi=$entry->DOI;
        
     $unpaywall=json_decode(file_get_contents("https://api.unpaywall.org/v2/".$doi."?email=janne.t.seppanen@jyu.fi"));
     
     $solved_items[]=array('doi'=>$doi,'title'=> $entry->title[0],'oa'=>$unpaywall->is_oa);
     
     if($unpaywall->is_oa==true){$oa_count++;}

      $solved_count=count($solved_items);
      $oa_rate=round(($oa_count/$solved_count)*100,1);      

  
   }
  $cursor=urlencode($cr_target->message->$nc); 
}

}

echo "OA RATE: ". $oa_rate;

?>