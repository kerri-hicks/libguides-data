<?php

$resumption_token = "" ;
$output = array() ;
$count = 0 ;
$domain = "libguides.myschool.edu" ;

do {
	// this URL is given to us in the LibGuides dashboard at https://brown.libapps.com/libguides/exports.php?action=2
	$libguides_data_URL = "http://$domain/oai.php?verb=ListRecords&metadataPrefix=oai_dc&set=az" ;
	
	if ($resumption_token == "") {
		//	we're going to use the base URL, no resumption token
		$url_to_load = $libguides_data_URL ;
	} else {
		//	tack on the resumption token that we got from the previous fetch
		$url_to_load = $libguides_data_URL . "&resumptionToken=" . $resumption_token ;
	}

	// get the remote XML file, and load it in as an XML object
	$xml_payload = simplexml_load_file($url_to_load) or die ("Sorry, I can't find the XML data right now.") ;

	// Springshare paginates the results, 100 at a time...if this page we just got is not the final page of results, there will be a node called resumptionToken with a value of the next page's URI
	$resumption_token = $xml_payload->ListRecords->resumptionToken ; 

	// grab the timestamp, because timestamps are fun!
	$timestamp = $xml_payload->responseDate ; 
	$timestamp = date('j F Y', strtotime($timestamp)) ;

	// walk through the document grabbing each record node
	foreach ($xml_payload->ListRecords->record as $record) {
		// each record has a metadata node with important stuff in it, so grab that.
		$metadata = $record->metadata ;
		// we're working with two namespaces here -- oai which contains a Dublin Core namespace, and Dublin Core, which includes native elements that we're looking for here. So we need to call the oai namespace to get the allowed Dublin Core, and then we need to call the schema for Dublin Core to allow title, description, publisher, and identifier (which is our URL)
		$title = addslashes($metadata->children('http://www.openarchives.org/OAI/2.0/oai_dc/')->dc->children('http://purl.org/dc/elements/1.1/')->title) ; 
		$description = strip_tags($metadata->children('http://www.openarchives.org/OAI/2.0/oai_dc/')->dc->children('http://purl.org/dc/elements/1.1/')->description) ; 
		$publisher = addslashes($metadata->children('http://www.openarchives.org/OAI/2.0/oai_dc/')->dc->children('http://purl.org/dc/elements/1.1/')->publisher) ; 
		$url = addslashes($metadata->children('http://www.openarchives.org/OAI/2.0/oai_dc/')->dc->children('http://purl.org/dc/elements/1.1/')->identifier) ; 
		
		$description = str_replace("\n", " ", $description) ;

		$output[$count] = array("title" => $title, "description" => $description, "publisher" => $publisher, "url" => $url ) ;

		++$count ; 
	}
// if there's a value in the resumption token node, go through this process again for the next page of records
} while ($resumption_token != "");



header('Content-type:application/json;charset=utf-8') ;
$output = json_encode($output) ;
echo $output ;
?>