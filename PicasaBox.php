<?php
/*------------------------------------------------------------------------------
| PicasaBox.php
|
| PicasaBox is a php script that uses Lightbox to display images from your 
| PicasaWeb album.
|
| XML parsing logic from 
|	http://www.sitepoint.com/article/php-xml-parsing-rss-1-0
|
| See https://github.com/benrhughes/PicasaBox for updated and instructions.
|
| Created by Ben Hughes - benrhughes.com
| 16 July 2007
| Version 1.0
|
| Version 1.1
|	- Changed to use MEDIA:CONTENT URL as source of the URL, as the previous
|	  (CONTENT SRC) seems to have been removed from the feed
| Version 1.2
|	- Very minor change to make compatable with Lightbox 2.04
------------------------------------------------------------------------------*/

/*------------------------------------------------------------------------------
| USER CONFIGURATION START
------------------------------------------------------------------------------*/
$userid = "picasaviewer"; // Your Google user name

// In pixels, the length of the longest side of the displayed image. 
// Can be 72, 144, 200, 288, 320, 400, 512, 576, 640, 720 or 800
$imgmax = "512"; 

/*------------------------------------------------------------------------------
| USER CONFIGURATION END
------------------------------------------------------------------------------*/

// *** Only modify past this point if you know what you're doing ***

$album = $_GET["album"];

$insideentry = false;
$tag = "";
$title = "";
$url = "";
$link = "";

// function to parse the start of an XML element
function startElement($parser, $name, $attrs) {
	global $insideentry, $tag, $title, $url, $link;
	if ($insideentry) {
		$tag = $name;
		
		if ($name == "MEDIA:CONTENT"){
			$url = $attrs["URL"];
		}
		elseif ($name == "LINK"){
			if ($attrs["REL"] == "alternate"){
				$link = $attrs["HREF"];	
			}
		}
	} elseif ($name == "ENTRY") {
		$insideentry = true;
	}
}

// function to parse the end of an XML element
function endElement($parser, $name) {
	global $insideentry, $tag, $title, $url, $link, $photos;
	if ($name == "ENTRY") {
		$photos[] = array($title, $url, $link);
		//echo $title . ' ' . $url . ' ' . $link;
		$title = "";
		$url = "";
		$link = "";
		$insideentry = false;
	}
}

// function to parse the contents of an XML element
function characterData($parser, $data) {
	global $insideentry, $tag, $title, $url, $link;
	if ($insideentry) {
		if ($tag == "SUMMARY") {
			$title .= $data;
		}
	}
}

// Lets get started... 

// Create an XML parser, using the functions above
$xml_parser = xml_parser_create();
xml_set_element_handler($xml_parser, "startElement", "endElement");
xml_set_character_data_handler($xml_parser, "characterData");

// The URL of the album feed
$feed = "http://picasaweb.google.com/data/feed/api/user/" . $userid . "/album/" . $album . "?kind=photo";

// Open the feed
$fp = fopen($feed,"r")
	or die("Error reading RSS data.");

// Parse the feed
while ($data = fread($fp, 4096))
	xml_parse($xml_parser, $data, feof($fp))
		or die(sprintf("XML error: %s at line %d", 
			xml_error_string(xml_get_error_code($xml_parser)), 
			xml_get_current_line_number($xml_parser)));
// Close the feed
fclose($fp);
xml_parser_free($xml_parser);


// Generate the HTML
$htmlout = '<html>';

$htmlout .= '<head>';
$htmlout .= '	<title>' . $album . '</title>';
$htmlout .= '	<link rel="stylesheet" href="css/lightbox.css" type="text/css" media="screen" />';
$htmlout .= '	<script src="js/prototype.js" type="text/javascript"></script>';
$htmlout .= '	<script src="js/scriptaculous.js?load=effects,builder" type="text/javascript"></script>';
$htmlout .= '	<script src="js/lightbox.js" type="text/javascript"></script>';
$htmlout .= '	<style type="text/css">';
$htmlout .= '		body{ color: #333; font: 13px "Lucida Grande", Verdana, sans-serif;	}';
$htmlout .= 	'	.Album { width: 625px; background: #f5f5f5; padding: 5px;}';
$htmlout .= 	'	.AlbumHeader { text-align:center; padding-left:0px; }';
$htmlout .= 	'	.AlbumHeader h3 { font: normal 24px Arial, Helvetica, sans-serif; color: #FF0084; text-align: center; }';
$htmlout .= 	'	.AlbumHeader h4 { font: 16px Caflisch Script,cursive; color: #660033; text-align: center; }';
$htmlout .= 	'	.AlbumPhoto { background: #f5f5f5; margin-bottom: 10px; }';
$htmlout .= 	'	.AlbumPhoto p { float: left; padding: 4px 4px 12px 4px; border: 1px solid #ddd; background: #fff; margin: 8px; }';
$htmlout .= 	'	.AlbumPhoto span { float: left; padding: 4px 4px 12px 4px; border: 1px solid #ddd; background: #fff; margin: 8px; }';
$htmlout .= 	'	.AlbumPhoto img { border: none; }';
$htmlout .= 	'</style>';
$htmlout .= '</head>';

$htmlout .= '<body>';
$htmlout .= 	'<div class="Album">';
$htmlout .= 	'	<div class="AlbumHeader">';
$htmlout .= 	'		<h4>' . $album .'</h4>';
$htmlout .= 	'	</div>';
$htmlout .= 	'	<br clear=all>';
$htmlout .= 	'	<div class="AlbumPhoto">';

foreach($photos as $photo)
{
	$htmlout .= '<span><a href="' . $photo[1] . '?imgmax=' . $imgmax . '" rel="lightbox[' . $album . ']" title="' . $photo[0] . '"><img src="' . $photo[1] . '?imgmax=64&amp;crop=1" border=0></a></span>';
}

$htmlout .= 	'	</div>';
$htmlout .= 	'	<br clear=all>';
$htmlout .= 	'</div>';

$htmlout .= '</body></html>';

// Return the html 
print $htmlout;
exit;

?>
