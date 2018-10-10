<?php

$resumption_token = "" ;
$format = $_GET['format'] ;
$count = 0 ;
$domain = "libguides.myschool.edu" ;

if($format == "json"){
	$output = array() ;		
} else {
	$output = '<!DOCTYPE html>
				<html lang="en">
				<head>
					<meta charset="utf-8" />
					<title>Current Libguides Database Listing</title>
					<style type="text/css">
						table {
							border-collapse : collapse ;
						}	
						table, th, td {
							border : 1px solid #ccc ;
						}
						th, td {
							padding : 5px ; 
						}
						tr:nth-child(even) {
							background-color: #f0f0f0 ;
						}
						.json_block {
							margin-left : 40px ; 
						}
						.json_block_content {
							margin-left : 40px ; 
						}
					</style>	
				</head>
				<body>
				<a href="?format=json" style="float : right ; display : block ; width : 150px ; background-color : darkgreen ; color : #fff ; padding : 10px ; border-radius : 15px ; box-shadow : 3px 3px 2px #ccc ; text-align : center ; text-decoration : none ; ">View as JSON</a>
				<h1>BUL LibGuides Database Listing</h1>
					<table>
						<th>Title</th>
						<th>Publisher</th>
						<th>Description</th>
						<th>URL</th>' ;
	$output_end = "	</table>
				</body>
			</html>" ;
}

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
		$title = stripslashes($metadata->children('http://www.openarchives.org/OAI/2.0/oai_dc/')->dc->children('http://purl.org/dc/elements/1.1/')->title) ; 
		$description = stripslashes($metadata->children('http://www.openarchives.org/OAI/2.0/oai_dc/')->dc->children('http://purl.org/dc/elements/1.1/')->description) ; 
		$publisher = stripslashes($metadata->children('http://www.openarchives.org/OAI/2.0/oai_dc/')->dc->children('http://purl.org/dc/elements/1.1/')->publisher) ; 
		$url = stripslashes($metadata->children('http://www.openarchives.org/OAI/2.0/oai_dc/')->dc->children('http://purl.org/dc/elements/1.1/')->identifier) ; 
		$uid = stripslashes($record->header->identifier) ;
		$uid = preg_replace("/oai\:libguides\.com\:az\//", "", $uid) ;
 
		if($format == "json"){

		$description = str_replace("\n", " ", $description) ;
		// had to add this to remove HTML tags, because strip_tags() seems to take the contents of the element, as well, even though it's not supposed to
		$description = preg_replace("/<.*?>/", "", $description) ;
		
		$output[$count] = array("title" => $title, "description" => $description, "publisher" => $publisher, "url" => $url, "uid" => $uid ) ;
		
		++$count ; 

		} else { 
			$output .= "
						<tr>
							<td class='title'>$title</td>
							<td class='publisher'>$publisher</td>
							<td class='description'>$description</td>
							<td class='url'><a href='$url'>$url</a></td>
						</tr>" ;
			}
		}
// if there's a value in the resumption token node, go through this process again for the next page of records
} while ($resumption_token != "");

if($format == "json"){
	header('Content-type:application/json;charset=utf-8') ;
	$output = json_encode($output, JSON_UNESCAPED_SLASHES) ;
	echo $output ;
} else { 
	echo $output . $output_end ;
}

?>
