<?PHP

class RSSReader
{
	
	private $rss_file_to_parse;
	private $current_element_name;
	private $inside_item_element = False;
	private $current_item_name;
	private $rss_data = array();

	// turn the array produced from parse_file into an html list
	public function rss_array_to_html($rss_array)
	{

		$element_list = "";
		for($i = 1; $i <= $rss_array["item_count"]; $i++)
		{
			$cur_str = "item_" . $i;
			$element_list .= "<ul>\n";
			$element_list .= "\t<li class=\"rss_title\">" . $rss_array[$cur_str]["TITLE"] . "</li>\n";
			$element_list .= "\t<li class=\"rss_pubdate\">" . $rss_array[$cur_str]["PUBDATE"] . "</li>\n";
			$element_list .= "</ul><br />\n";
		}

		return $element_list;
		
	}

	public function set_file_to_parse($rss_file)
	{
		$this->rss_file_to_parse = $rss_file;
		print "the file to parse is: " .  $this->rss_file_to_parse . "<br />";
	}
		

	private function rss_start_element_handler($parser, $name, $attribs)
	{
		/* DEBUG OUT
		print "beginning of element: " . $name . "<br />"; // DEBUG
		print " has attribs: "; // DEBUG
		var_dump($attribs); // DEBUG
		print "<br />"; // DEBUG
		*/

		$this->current_element_name = $name;

		if(strtoupper($name) == "ITEM")
		{
			$this->inside_item_element = True;
			//print "<b>INSIDE ITEM</b><br />"; // DEBUG OUT
			// start a new item array
			$this->current_item_name = "item_" . ++$this->rss_data["item_count"];

			$this->rss_data[$this->current_item_name] = array();
		}
	}


	private function rss_end_element_handler($parser, $name)
	{
		// print "end of element: " . $name . "<br />"; // DEBUG

		// to prevent thinking we are in an element that we aren't
		// empty out current_element_name if it is what we are in
		// this does not take into account issues of nesting
		if($name == $this->current_element_name)
		{
			$this->current_element_name = '';
		}
		if($name == "ITEM")
		{
			$this->inside_item_element = False;
		}
	}

	private function cdata_handler($parser, $data)
	{
		if($this->inside_item_element && $this->current_element_name != "" && $this->current_element_name != "ITEM")
		{
			$this->rss_data[$this->current_item_name][$this->current_element_name] .= $data;
			//print "<b>&nbsp;&nbsp;&nbsp;" . $this->current_element_name . " :</b> " . $data . "<br />"; // DEBUG OUT
		}
	}


	/**
 	* this is the main function for the rss_reader, it takes either a local relative or absolute
 	* resource or a non-local http absolute resource.  It returns an array with each item in the 
 	* array being one rss <item> in an html context.
 	*/
	public function parse_file()
	{

		$rss_data["item_count"] = 0;

		// create the parser with output in UTF-8 encoding
		$rss_parser = xml_parser_create("UTF-8");
		xml_set_object($rss_parser, &$this); // required to allow the use of parser within this "object"

		xml_set_element_handler($rss_parser, 'rss_start_element_handler', 'rss_end_element_handler') or die("failed to set element handlers");
		xml_set_character_data_handler($rss_parser, 'cdata_handler') or die("failed to set cdata handler");
	

		// open the file
		$rss_file_handle = fopen($this->rss_file_to_parse, "r") or die("failed to open rss file");

		// read the file and parse the xml
		while(!feof($rss_file_handle))
		{
			$current_data = fread($rss_file_handle, 2048);
			//print $current_data;
			xml_parse($rss_parser, $current_data, FALSE);
		
		}
	

		// free up resources
		fclose($rss_file_handle);
		xml_parser_free($rss_parser);


		return $this->rss_data;
	}
}

?>
