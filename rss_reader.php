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
	public static function rss_array_to_html($rss_array, $no_of_items = 0, $summary_length = 100, $elements_to_print = "1111111111", $href_title = false)
	{
		// unnecessary
		//$element_print = str_split($elements_to_print);
		if( ( $no_of_items == 0 ) || ( $no_of_items > $rss_array["item_count"] ) )
		{
			$max_items = $rss_array["item_count"];
		} else {
			$max_items = $no_of_items;
		}

		echo "max_items: " . $max_items . "<br />";

		$element_list = "";
		for($i = 1; $i <= $max_items; $i++)
		{
			$cur_str = "item_" . $i;
			$element_list .= "<div class=\"rss_container\">\n";
			if($elements_to_print[0] == 1)
			{
				if($href_title)
				{
					$element_list .= "\t<div class=\"rss_title\"><a href=\"" . $rss_array[$cur_str]["LINK"] . "\">" . $rss_array[$cur_str]["TITLE"] . "</a></div>\n";
				} else {
					$element_list .= "\t<div class=\"rss_title\">" . $rss_array[$cur_str]["TITLE"] . "</div>\n";
				}
			}
			if($elements_to_print[1] == 1)
			{
				$element_list .= "\t<div class=\"rss_link\">" . $rss_array[$cur_str]["LINK"] . "</div>\n";
			}
			if($elements_to_print[2] == 1)
			{
				// TODO: eventually the logic of summarizing to the nearest word should be broken out into a new function
				$desc = strip_tags($rss_array[$cur_str]["DESCRIPTION"]);

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
					
				$element_list .= "\t<div class=\"rss_description\">" . $desc . "</div>\n";
			}
			if($elements_to_print[3] == 1)
			{
				$element_list .= "\t<div class=\"rss_author\">" . $rss_array[$cur_str]["AUTHOR"] . "</div>\n";
			}
			if($elements_to_print[4] == 1)
			{
				$element_list .= "\t<div class=\"rss_category\">" . $rss_array[$cur_str]["CATEGORY"] . "</div>\n";
			}
			if($elements_to_print[5] == 1)
			{
				$element_list .= "\t<div class=\"rss_comments\">" . $rss_array[$cur_str]["COMMENTS"] . "</div>\n";
			}
			if($elements_to_print[6] == 1)
			{
				// TODO: figure out how to deal with this element
				//$element_list .= "\t<div class=\"rss_enclosure\">" . $rss_array[$cur_str][""] . "</div>\n";
			}
			if($elements_to_print[7] == 1)
			{
				$element_list .= "\t<div class=\"rss_guid\">" . $rss_array[$cur_str]["GUID"] . "</div>\n";
			}
			if($elements_to_print[8] == 1)
			{
				$element_list .= "\t<div class=\"rss_pubdate\">" . $rss_array[$cur_str]["PUBDATE"] . "</div>\n";
			}
			if($elements_to_print[9] == 1)
			{
				// TODO: this element has attributes
				$element_list .= "\t<div class=\"rss_source\">" . $rss_array[$cur_str]["SOURCE"] . "</div>\n";
			}

			$element_list .= "</div><br />\n";
		}

		return $element_list;
		
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
			$this->rss_data[$this->current_item_name][$this->current_element_name] .= $data;
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
