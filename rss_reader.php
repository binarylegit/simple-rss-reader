<?PHP

/**
 * RSSReader is designed to take an rss file and parse it's items into an array,
 * then that array can be turned into an HTML list via the static function 
 * rss_array_to_html().
 *
 * WARNING: This class is still very much under initial design and the 
 * function signatures, and function outputs are likely to change!
 * USE WITH CAUTION.
 * 
 * A very simple sample use would be:
 *	include_once("rss_reader.php");
 *	$rss_reader = new RSSReader();
 *	$rss_reader->set_file_to_parse("http://codingnotes.alephcipher.com");
 *	$rss_data = $rss_reader->parse_file();
 *	echo RSSReader::rss_array_to_html($rss_data);
 *
 *
 */
class RSSReader
{
	
	private $rss_file_to_parse; // a uri of the file that will be parsed
	private $current_element_name; // the element name that is currently being parsed
	private $inside_item_element = False; // state information, are we inside an rss <ITEM>?
	private $current_item_name; // the array name (not rss related) of the current item being parsed
	private $rss_data = array(); // the array to be filled with RSS Item arrays





	// turn the array produced from parse_file into an html list
	// summary length 0 means no summarizing, length specifies characters to the nearest word
	// no_of_items - 0 means all items
	// WARNING: this function's signature is very likely to change.
	// TODO: increase the documentation for this function
	// item elements: 0=title, 1=link, 2=description, 3=author, 4=category, 5=comments, 6=enclosure, 7=guid, 8=pubdate, 9=source
	public static function rss_array_to_html(&$rss_array, $no_of_items = 0, $summary_length = 100, $elements_to_print = "0123456789", $href_title = false)
	{
		// get the number of items to print
		if( ( $no_of_items == 0 ) || ( $no_of_items > $rss_array["item_count"] ) )
		{
			$max_items = $rss_array["item_count"];
		} else {
			$max_items = $no_of_items;
		}

		//echo "max_items: " . $max_items . "<br />"; // DEBUG

		$element_list = "";
		for($item_count = 1; $item_count <= $max_items; $item_count++)
		{
			$cur_str = "item_" . $item_count;
			$element_list .= "<div class=\"rss_container\">\n";
			
			for($element_count = 0; $element_count < strlen($elements_to_print); $element_count++)
			{
				//echo "parsing element: " . $elements_to_print[$element_count]; // DEBUG
				$element_list .= RSSReader::get_element_div_wrapper($rss_array[$cur_str], $elements_to_print[$element_count], $summary_length, $href_title);

			}

			$element_list .= "</div><br />\n";
		}

		return $element_list;
		
	}



	// item elements: 0=title, 1=link, 2=description, 3=author, 4=category, 5=comments, 6=enclosure, 7=guid, 8=pubdate, 9=source
	private static function get_element_div_wrapper(&$rss_item_array, $element_no, $summary_length, $href_title=false)
	{

		$element_name = RSSReader::get_element_name_from_no($element_no);
		//echo "PARSING element: " . $element_name . "<br />"; // DEBUG

		// elements with attr's: 9 7 4 6 
		// elements with special issues: 0 2 5
		$str_to_ret = "";

		switch ($element_no)
		{

			case 0: // title
				if($href_title)
				{
					$str_to_ret = "\t<div class=\"rss_" . $element_name . "\"><a href=\"" . $rss_item_array[RSSReader::get_element_name_from_no(1)] . "\">" . $rss_item_array[$element_name] . "</a></div>\n";
				} else {
					$str_to_ret = "\t<div class=\"rss_" . $element_name . "\">" . $rss_item_array[$element_name] . "</div>\n";
				}
				break;
			case 1: // link
				$str_to_ret = "\t<div class=\"rss_" . $element_name . "\"><a href=\"" . $rss_item_array[$element_name] . "\">" . $rss_item_array[$element_name] . "</a></div>\n";
				break;
			case 2: // description
				
				$str_to_ret .= "\t<div class=\"rss_description\">" . RSSReader::get_safe_summary($rss_item_array[$element_name], $summary_length) . "</div>\n";
				break;
			case 6: // enclosure
				// TODO: figure out how to deal with this element
				//$element_list .= "\t<div class=\"rss_enclosure\">" . $rss_item_array[""] . "</div>\n";
				break;
			default: // the rest
				$str_to_ret = "\t<div class=\"rss_" . $element_name . "\">" . $rss_item_array[$element_name] . "</div>\n";
				break;
		}

		return $str_to_ret;

	}


	// summary_length = 0 -- means to use the entire thing
	private static function get_safe_summary($text_to_summarize, $summary_length)
	{

		// TODO: eventually the logic of summarizing to the nearest word should be broken out into a new function
		$desc = strip_tags($text_to_summarize);

		if($summary_length != 0)
		{
			$cur_char = $desc[$summary_length];
			while($cur_char != " ")
			{
				$summary_length--;
				$cur_char = $desc[$summary_length];
			}

			$desc = substr($desc, 0, $summary_length);
		}

		return $desc;

	}



	// TODO: move this below get_element_div_wrapper
	private static function get_element_name_from_no($element_no)
	{
	// item elements: 0=title, 1=link, 2=description, 3=author, 4=category, 5=comments, 6=enclosure, 7=guid, 8=pubdate, 9=source
		switch($element_no)
		{
			case 0:
				return "title";
			case 1:
				return "link";
			case 2:
				return "description";
			case 3:
				return "author";
			case 4:
				return "category";
			case 5:
				return "comments";
			case 6:
				return "enclosure";
			case 7:
				return "guid";
			case 8:
				return "pubdate";
			case 9:
				return "source";
			default:
				break;
		}

		return null;
	}
				
	/**
	 * Simple setter function to set the current file to be parsed
	 * 
	 * @param $rss_file The uri of a file to parse
	 */
	public function set_file_to_parse($rss_file)
	{
		$this->rss_file_to_parse = $rss_file;
		print "the file to parse is: " .  $this->rss_file_to_parse . "<br />";
	}
		

	// handles the action to take when an rss element is reached
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


	// handles the action to take when leaving an rss element
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

	// handles the character data within rss elements
	private function cdata_handler($parser, $data)
	{
		if($this->inside_item_element && $this->current_element_name != "" && $this->current_element_name != "ITEM")
		{
			$this->rss_data[$this->current_item_name][strtolower($this->current_element_name)] .= $data;
			//print "<b>&nbsp;&nbsp;&nbsp;" . $this->current_element_name . " :</b> " . $data . "<br />"; // DEBUG OUT
		}
	}


	/**
 	* this is the main function for the rss_reader, it takes either a local relative or absolute
 	* resource or a non-local http absolute resource.  It returns an array with each item in the 
 	* array being one rss <item> in an html context.
	* 
	* @return returns an array containing other arrays which contain the information inside RSS <ITEM> tags
 	*/
	// TODO: ^^ make this documentation more thorough
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
