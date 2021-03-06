<?PHP ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-mifrosoft-com:vml">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>

	<title>PHP RSS reader testbed</title>

	<link rel="stylesheet" type="text/css" href="" />


	<script type="text/javascript">
	//<![CDATA[

	//]]>
	</script>


	<style type="text/css">

		div.rss_container
		{
			padding-left: 10px;
			border-left: #000000 dotted 1px;
		}

		div.rss_title
		{
			border-bottom: #000077 dashed 1px;
		}

		div.rss_pubdate
		{
			border-bottom: #000077 dashed 1px;
			font-size:10px;
		}


	</style> 
</head>
<body onload="" onunload="">
<?PHP 
	// PHP execution for the rss_reader
	include_once("rss_reader.php");
	$rss_reader = new RSSReader();
	//$rss_reader->set_file_to_parse("sample-rss-2.xml");
	$rss_reader->set_file_to_parse("http://codingnotes.alephcipher.com/feed/");
	$rss_data = $rss_reader->parse_file();
	//var_dump($rss_data);
	//echo RSSReader::rss_array_to_html($rss_data); // smallest possible function call
	echo RSSReader::rss_array_to_html($rss_data, 3, 0, "082", true);
?>

</body>
</html>
