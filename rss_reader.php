<?PHP

/**
 * RSSReader is designed to take an rss file and parse it's items into an array,
 * then that array can be turned into a collection of HTML <div>'s via the static 
 * function rss_array_to_html().
 *
 * As an example of basic usage the sequence of calls would be:
 * 	$rss_reader = new RSSReader();
 *	$rss_reader->set_file_to_parse("http://[WEBSITE-NAME]/feed.xml");
 *	$rss_data = $rss_reader->parse_file();
 *	echo RSSReader::rss_array_to_html($rss_data);
 *
 * Alternately the array returned by 'parse_file()' could be used in other ways
 * as determined by the user of this class.
 *
 * @author Daniel Fowler (www.alephcipher.com)
 */
class RSSReader
{
	
	private $rss_file_to_parse; // a uri of the file that will be parsed
	private $current_element_name; // the element name that is currently being parsed
	private $inside_item_element = False; // state information, are we inside an rss <ITEM>?
	private $current_item_name; // the array name (not rss related) of the current item being parsed
	private $rss_data = array(); // the array to be filled with RSS Item arrays



	/**
	 * rss_array_to_html() will take an array in the form produced by parse_file()
	 * and take every element within each <item> and turn it into a <div>.  Each 
	 * <item>'s elements will be wrapped with another <div>.  All div's output will
	 * be given a class name equal to 'rss_TAGNAME' where TAGNAME is equal to the
	 * lowercase name of the RSS tag.  The div that wraps each item will be given
	 * the class 'rss_item'. 
	 * 
	 * Several complex options exist for formatting the output of RSS <div>'s via 
	 * this functions parameters, all parameters have default values and are not
	 * required for the use of the function (except for the &$rss_array, which is
	 * required).  Many of the required parameters impact only one element of each 
	 * <item>.
	 * 
	 * The following list describes how the html output can be modified:
	 *
	 * 	no_of_items - refers to the number of items that are to be output.
	 *		For instance a value of '3' will place the topmost (most
	 *		recent) 3 <items> into <div> tags and return them.  A value
	 *		of 0 (zero) will output all items available in the &$rss_array.
	 *
	 *	summary_length - refers to the summarized length of the <description>
	 *		rss tag, the number given refers to character length.  The
	 * 		description will be summarized (shortened) via the method of
	 *		the private function get_safe_summary() (see internal
	 *		documentation).
	 *
	 *	href_title - This boolean value will determine if the title element
	 *		will be wrapped in an <a href=""> tag linking to the <link>
	 *		elements location.  false will provide plain text, true will
	 *		provide a hyperlink.
	 *
	 *	elements_to_print - This will determine which elements and in
	 *		what order they are to be included in the output.  Each
	 *		element within the <item> tag is assigned a number:
	 * 			0 = title
	 *			1 = link
	 *			2 = description 
	 *			3 = author
	 *			4 = category
	 *			5 = comments
	 *			6 = enclosure
	 *			7 = guid
	 *			8 = pubdate
	 *			9 = source
	 *		by providing these number in a string will determine
	 *		wether or not they are to be included in the output,
	 *		and in what order they are to appear.
	 *		For example: 0123456789 will print all elements in the
	 *		order given in the list above; a value of "082" will
	 *		output the title div, followed by the date div, followed
	 *		by the description div.
	 * 
	 * Outside of this functions parameters the resulting <div>'s have css
	 * class attributes.  This allows the resulting HTML to be styled via
	 * css, as desired by the user of this class.  The css classname for each
	 * div is the combination of "rss_" and the lowercase name of the element
	 * (see the list in elements_to_print).  The <div> that surrounds the
	 * list of other divs is under the style class 'rss_item'.
	 *
	 * <b>WARNING</b>: It should be noted that as it currently stands there is
	 * no way of retrieving or displaying the contents of any rss element attributes.
	 *
	 * @param array &$rss_array An array in the format produced by parse_file() 
	 *			containing the item arrays which contain the elements
	 *			of their particular item.
	 * @param int $no_of_items The number of items in from the rss_array to turn
	 *			into and output in HTML <div>'s.  A value of 0 (zero)
	 *			will output all available rss <item>'s.  Default value
	 *			is 0 (zero).
	 * @param int $summary_length The length (in characters) that the <description>
	 *			element should be shortened to.  Default value is 100.
	 * @param string $elements_to_print See this functions main documentation for  
	 *			a more thorough explanation.  This value determines which
	 *			RSS elements will be output and in what order.  Each element
	 *			within the RSS element <item> is given an number (see above)
	 *			this string is then a list of numbers that describe the 
	 *			order (left->right <=> top ->bottom) and which elements
	 *			will be output.  By not including a number in the string
	 *			that element will not be output.  Default value is:
	 *			"0123456789" which will print all available elements
	 *			cdata in the order they are listed in the rss 2.0
	 *			documentation {@link http://www.rssboard.org/rss-specification#hrelementsOfLtitemgt RSS 2.0 Specification}.
	 * @param bool $href_title True will place an href link surrounding the title,
	 *			linking to the rss element <link>'s location.  False
	 *			(the default) will not place the link and the title
	 *			will be plain text.
	 * @return string a string containing html <div>'s in the specified format
	 *			as determined by the attribute values of this function.
	 * @author Daniel Fowler (www.alephcipher.com)
	 * 
	 *	
	 */
	 // TODO: finish/proofread documentation, make documentation HTML safe.
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
	//This function wraps a given rss element into a <div> wrapper with any
	// special formatting that is necessary.
	// TODO: increase documentation
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
	// This function takes text, strips the HTML tags and then cuts it to the
	// number of characters given by $summary_length, however it subtracts characters
	// to the nearest word, such that it won't cut off words (thus the "safe")
	// TODO: increase documentation
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



	// This function translates a number to an element name
	// TODO: increase documentation
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
	// TODO: increase documentation
	public function set_file_to_parse($rss_file)
	{
		$this->rss_file_to_parse = $rss_file;
		print "the file to parse is: " .  $this->rss_file_to_parse . "<br />"; // DEBUG
	}
		

	// handles the action to take when an rss element is reached
	// TODO: increase documentation
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
	// TODO: increase documentation
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
	// TODO: increase documentation
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
