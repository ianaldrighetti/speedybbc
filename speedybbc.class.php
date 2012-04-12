<?php
////////////////////////////////////////////////////////////////////////////
//                                                                        //
//         SpeedyBBC - A full featured, and fast, BBCode parser.          //
//                        speedybbc.googlecode.com                        //
//                                                                        //
////////////////////////////////////////////////////////////////////////////
//                                                                        //
//      SpeedyBBC is released under the Microsoft Reciprocal License.     //
//                 www.opensource.org/licenses/ms-rl.html                 //
//                                                                        //
////////////////////////////////////////////////////////////////////////////
// You may use SpeedyBBC under any license (well, except the GPL, as it   //
//      is incompatible according the FSF), and you may release any       //
// modifications under the license of your choice. All that I ask is that //
//   you give credit where credit is due and to not remove this header.   //
////////////////////////////////////////////////////////////////////////////
// SpeedyBBC is released "as is," and without warranty! Absolutely no one //
//  is responsible for anything and everything that may or may not occur  //
//               when using SpeedyBBC in any shape or form.               //
////////////////////////////////////////////////////////////////////////////
//                              Version: 1.0                              //
////////////////////////////////////////////////////////////////////////////

// Just a couple SpeedyBBC parsing options.
define('SPEEDYOPT_DISAUTOLINK', 0x1);
define('SPEEDYOPT_DISSMILEYS', 0x2);

/*
	Class: SpeedyBBC

	SpeedyBBC is a BBCode parser designed to be full featured and accomplish
	the task at hand as quickly as possible.

	The SpeedyBBC class is made up of a couple different parts, one being the
	lexer (lexical parser) and the other being the interpreter. The lexer
	turns the supplied message into an array of text and tag nodes, while the
	interpreter takes the array of nodes and translates that into an HTML
	formatted message.
*/
class SpeedyBBC
{
	// Variable: tags
	// Contains all the defined BBCode tag handlers.
	private $tags;

	// Variable: index
	// Contains an array for indexing items to make parsing faster. Who
	// doesn't want freaky fast speed?
	private $index;

	// Variable: cachedir
	// Contains a directory which will be used to hold cached parsed messages.
	private $cachedir;

	// Variable: cachettl
	// How long, in seconds, a cached message should be considered "fresh."
	private $cachettl;

	// Variable: autopurge
	// Whether the class will periodically remove expired messages.
	private $autopurge;

	// Variable: autoreplace
	// Whether links will automatically be replaced.
	private $autoreplace;

	// Variable: smileys
	// An array containing smileys which should be replaced within text.
	private $smileys;

	/*
		Constructor: __construct

		Parameters:
			bool $enable_default_tags - Whether to enable the default tags which
																	come with the BBCode class, defaults to
																	true.
			string $encoding - The character encoding to use when handling
												 strings, which defaults to UTF-8. Please note this
												 option is only used if the multi-byte extension in
												 PHP is enabled (<www.php.net/mb>).
	*/
	public function __construct($enable_default_tags = true, $encoding = 'UTF-8')
	{
		$this->tags = array();
		$this->cachedir = null;
		$this->cachettl = 900;
		$this->autopurge = true;
		$this->autoreplace = true;
		$this->smileys = array();
		$this->index = array(
										 'names' => array(),
										 'empty' => array(),
										 'tags' => array(),
										 'constraints' => array(
																				'parents' => array(),
																				'children' => array(),
																			),
										 'valid' => true,
									 );

		// Let's check to see if we can set a default encoding.
		if(!empty($encoding) && function_exists('mb_internal_encoding'))
		{
			mb_internal_encoding($encoding);
		}

		if(!empty($enable_default_tags))
		{
			$tags = array(
								/* First, some basic tags. */
								array(
									'name' => 'b',
									'type' => 'basic',
									'before' => '<strong>',
									'after' => '</strong>',
								),
								array(
									'name' => 'i',
									'type' => 'basic',
									'before' => '<em>',
									'after' => '</em>',
								),
								array(
									'name' => 'u',
									'type' => 'basic',
									'before' => '<span style="text-decoration: underline !important;">',
									'after' => '</span>',
								),
								array(
									'name' => 's',
									'type' => 'basic',
									'before' => '<del>',
									'after' => '</del>',
								),
								array(
									'name' => 'sup',
									'type' => 'basic',
									'before' => '<sup>',
									'after' => '</sup>',
								),
								array(
									'name' => 'sub',
									'type' => 'basic',
									'before' => '<sub>',
									'after' => '</sub>',
								),
								/* [nobbc] tag -- disables BBCode... */
								array(
									'name' => 'nobbc',
									'type' => 'basic',
									'before' => '',
									'after' => '',
									'parse_content' => false,
								),
								/* Alignment tags */
								array(
									'name' => 'left',
									'type' => 'basic',
									'before' => '<span style="text-align: left !important;">',
									'after' => '</span>',
								),
								array(
									'name' => 'center',
									'type' => 'basic',
									'before' => '<span style="text-align: center !important;">',
									'after' => '</span>',
								),
								array(
									'name' => 'right',
									'type' => 'basic',
									'before' => '<span style="text-align: right !important;">',
									'after' => '</span>',
								),
								array(
									'name' => 'justify',
									'type' => 'basic',
									'before' => '<span style="text-align: justify !important;">',
									'after' => '</span>',
								),
								array(
									'name' => 'align',
									'type' => 'value',
									'value' => array(
															 'regex' => '~^(left|center|right|justify)$~i',
														 ),
									'before' => '<span style="text-align: {value} !important;">',
									'after' => '</span>',
								),
								/* Some value tags. */
								array(
									'name' => 'acronym',
									'type' => 'value',
									'before' => '<abbr title="{value}" class="bbcode-acronym">',
									'after' => '</abbr>',
								),
								array(
									'name' => 'abbr',
									'type' => 'value',
									'before' => '<abbr title="{value}" class="bbcode-acronym">',
									'after' => '</abbr>',
								),
								array(
									'name' => 'size',
									'type' => 'value',
									'value' => array(
															 'callback' => create_function('$value', '
																							 $value = trim($value);
																							 if(preg_match(\'~^(1|[1-9][0-9]|[1-9][0-9][0-9]|1000)(pt|px)$~\', $value, $matches))
																							 {
																								 return ((int)$matches[1]). strtolower($matches[2]);
																							 }
																							 elseif((string)$value == (string)(int)$value && (int)$value >= 0 && (int)$value <= 7)
																							 {
																							   $sizes = array(0.5, 0.67, 0.83, 1, 1.17, 1.5, 2, 2.5);
																								 return $sizes[(int)$value]. \'em\';
																							 }
																							 else
																							 {
																								 return false;
																							 }'),
														 ),
									'before' => '<span style="font-size: {value} !important;">',
									'after' => '</span>',
								),
								array(
									'name' => 'color',
									'type' => 'value',
									'value' => array(
															 'callback' => create_function('$value', '
																							 $value = trim($value);
																							 if(preg_match(\'~^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$~i\', $value) > 0)
																							 {
																								 return $value;
																							 }
																							 elseif(preg_match(\'~^(?:(?:rgb\(\s*([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\s*,\s*([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\s*,\s*([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\s*\))|(?:rgb\(\s*([0-9]|[1-9][0-9]|100)%\s*,\s*([0-9]|[1-9][0-9]|100)%\s*,\s*([0-9]|[1-9][0-9]|100)%\s*\))|(?:rgba\(\s*([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\s*,\s*([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\s*,\s*([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\s*,\s*((?:0?\.\d{1,2})|0|1)\))|(?:rgba\(\s*([0-9]|[1-9][0-9]|100)%\s*,\s*([0-9]|[1-9][0-9]|100)%\s*,\s*([0-9]|[1-9][0-9]|100)%\s*,\s*((?:0?\.\d{1,2})|0|1)\)))$~i\', $value, $matches))
																							 {
																								 	$is_alpha = strpos($value, \'rgba\') !== false;
	                                                $is_percent = strpos($value, \'%\') !== false;

																								 $start = strlen($matches[1]) > 0 ? 1 : (strlen($matches[4]) > 0 ? 4 : (strlen($matches[7]) > 0 ? 7 : 11));
																								 $values = array((int)$matches[$start]. ($is_percent ? \'%\' : \'\'), (int)$matches[$start + 1]. ($is_percent ? \'%\' : \'\'), (int)$matches[$start + 2]. ($is_percent ? \'%\' : \'\'));

																								 if($is_alpha)
																								 {
																									 $values[] = (double)$matches[$start + 3];
																								 }

																								 return \'rgb\'. ($is_alpha ? \'a\' : \'\'). \'(\'. implode(\', \', $values). \')\';
																							 }
																							 elseif(in_array(strtolower($value), array(\'aliceblue\', \'antiquewhite\', \'aqua\', \'aquamarine\', \'azure\', \'beige\', \'bisque\', \'black\', \'blanchedalmond\', \'blue\', \'blueviolet\', \'brown\', \'burlywood\', \'cadetblue\', \'chartreuse\', \'chocolate\', \'coral\', \'cornflowerblue\', \'cornsilk\', \'crimson\', \'cyan\', \'darkblue\', \'darkcyan\', \'darkgoldenrod\', \'darkgray\', \'darkgreen\', \'darkkhaki\', \'darkmagenta\', \'darkolivegreen\', \'darkorange\', \'darkorchid\', \'darkred\', \'darksalmon\', \'darkseagreen\', \'darkslateblue\', \'darkslategray\', \'darkturquoise\', \'darkviolet\', \'deeppink\', \'deepskyblue\', \'dimgray\', \'dodgerblue\', \'firebrick\', \'floralwhite\', \'forestgreen\', \'fuchsia\', \'gainsboro\', \'ghostwhite\', \'gold\', \'goldenrod\', \'gray\', \'green\', \'greenyellow\', \'honeydew\', \'hotpink\', \'indianred\', \'indigo\', \'ivory\', \'khaki\', \'lavender\', \'lavenderblush\', \'lawngreen\', \'lemonchiffon\', \'lightblue\', \'lightcoral\', \'lightcyan\', \'lightgoldenrodyellow\', \'lightgreen\', \'lightgrey\', \'lightpink\', \'lightsalmon\', \'lightseagreen\', \'lightskyblue\', \'lightslategray\', \'lightsteelblue\', \'lightyellow\', \'lime\', \'limegreen\', \'linen\', \'magenta\', \'maroon\', \'mediumaquamarine\', \'mediumblue\', \'mediumorchid\', \'mediumpurple\', \'mediumseagreen\', \'mediumslateblue\', \'mediumspringgreen\', \'mediumturquoise\', \'mediumvioletred\', \'midnightblue\', \'mintcream\', \'mistyrose\', \'moccasin\', \'navajowhite\', \'navy\', \'oldlace\', \'olive\', \'olivedrab\', \'orange\', \'orangered\', \'orchid\', \'palegoldenrod\', \'palegreen\', \'paleturquoise\', \'palevioletred\', \'papayawhip\', \'peachpuff\', \'peru\', \'pink\', \'plum\', \'powderblue\', \'purple\', \'red\', \'rosybrown\', \'royalblue\', \'saddlebrown\', \'salmon\', \'sandybrown\', \'seagreen\', \'seashell\', \'sienna\', \'silver\', \'skyblue\', \'slateblue\', \'slategray\', \'snow\', \'springgreen\', \'steelblue\', \'tan\', \'teal\', \'thistle\', \'tomato\', \'turquoise\', \'violet\', \'wheat\', \'white\', \'whitesmoke\', \'yellow\', \'yellowgreen\')))
																							 {
																								 return $value;
																							 }
																							 else
																							 {
																								 return false;
																							 }'),
														 ),
									'before' => '<span style="color: {value} !important;">',
									'after' => '</span>',
								),
								array(
									'name' => 'font',
									'type' => 'value',
									'value' => array(
															 'callback' => create_function('$value', '
																							 if(strpos($value, \',\') !== false)
																							 {
																								 $fonts = explode(\',\', $value);
																							 }
																							 else
																							 {
																								 $fonts = array($value);
																							 }

																							 $supported = array(\'arial\', \'helvetica\', \'arial black\', \'comic sans ms\', \'courier new\', \'impact\', \'lucida console\', \'monaco\', \'tahoma\', \'geneva\', \'yimes new roman\', \'trebuchet ms\', \'verdana\', \'symbol\', \'georgia\');
																							 $return = array();
																							 foreach($fonts as $font)
																							 {
																								 $font = trim($font);

																								 if(in_array(strtolower($font), $supported))
																								 {
																									 $return[] = strpos($font, \' \') === false ? $font : \'\\\'\'. $font. \'\\\'\';
																								 }
																							 }

																							 return count($return) ? implode(\', \', $return) : false;'),
														 ),
									'before' => '<span style="font-family: {value} !important;">',
									'after' => '</span>',
								),
								/* Links, such as URL's and email addresses. */
								array(
									'name' => 'url',
									'type' => 'basic',
									'callback' => create_function('$content, $dummy', '
																	if(preg_match(\'~(?:((?:http|ftp)(?:s)?://|www\.)(?:[\w+?\.\w+])+(?:[a-zA-Z0-9\~\!\@\#\$\%\^\&amp;\*\(\)_\-\=\+\\\/\?\.\:\;\\\'\,]*)?)~i\', $content))
																	{
																		return $content;
																	}
																	else
																	{
																		return false;
																	}'),
									'before' => '<a href="[content]" target="_blank">',
									'after' => '</a>',
									'parse_content' => false,
								),
								array(
									'name' => 'url',
									'type' => 'value',
									'value' => array(
															 'regex' => '~(?:((?:http|ftp)(?:s)?://|www\.)(?:[\w+?\.\w+])+(?:[a-zA-Z0-9\~\!\@\#\$\%\^\&amp;\*\(\)_\-\=\+\\\/\?\.\:\;\'\,]*)?)~i',
														 ),
									'before' => '<a href="{value}" target="_blank">',
									'after' => '</a>',
								),
								array(
									'name' => 'iurl',
									'type' => 'basic',
									'callback' => create_function('$content, $dummy', '
																	if(preg_match(\'~(?:((?:http|ftp)(?:s)?://|www\.)(?:[\w+?\.\w+])+(?:[a-zA-Z0-9\~\!\@\#\$\%\^\&amp;\*\(\)_\-\=\+\\\/\?\.\:\;\\\'\,]*)?)~i\', $content))
																	{
																		return \'<a href="\'. $content. \'>\'. $content. \'</a>\';
																	}
																	else
																	{
																		return false;
																	}'),
									'before' => '',
									'after' => '',
									'parse_content' => false,
								),
								/* allow mailto: in above!!! */
								array(
									'name' => 'iurl',
									'type' => 'value',
									'value' => array(
															 'regex' => '~(?:((?:http|ftp)(?:s)?://|www\.)(?:[\w+?\.\w+])+(?:[a-zA-Z0-9\~\!\@\#\$\%\^\&amp;\*\(\)_\-\=\+\\\/\?\.\:\;\'\,]*)?)~i',
														 ),
									'before' => '<a href="{value}">',
									'after' => '</a>',
								),
								array(
									'name' => 'email',
									'type' => 'basic',
									'callback' => create_function('$content, $dummy', '
																	if(preg_match(\'~(?:[\w-]+(?:\.[\w-]+)*@(?:[a-z0-9-]+(?:\.[a-z0-9-]+)*?\.[a-z]{2,6}|(?:\d{1,3}\.){3}\d{1,3})(?::\d{4})?)~i\', $content))
																	{
																		return $content;
																	}
																	else
																	{
																		return false;
																	}'),
									'before' => '<a href="mailto:[content]" target="_blank">',
									'after' => '</a>',
									'parse_content' => false,
								),
								array(
									'name' => 'email',
									'type' => 'value',
									'value' => array(
															 'regex' => '~(?:[\w-]+(?:\.[\w-]+)*@(?:[a-z0-9-]+(?:\.[a-z0-9-]+)*?\.[a-z]{2,6}|(?:\d{1,3}\.){3}\d{1,3})(?::\d{4})?)~i',
														 ),
									'before' => '<a href="mailto:{value}" target="_blank">',
									'after' => '</a>',
								),
								/* Images */
								array(
									'name' => 'img',
									'type' => 'basic',
									'callback' => create_function('$content, $dummy', '
																	if(preg_match(\'~(?:((?:http)(?:s)?://|www\.)(?:[\w+?\.\w+])+(?:[a-zA-Z0-9\~\!\@\#\$\%\^\&amp;\*\(\)_\-\=\+\\\/\?\.\:\;\\\'\,]*)?)~i\', $content))
																	{
																		return $content;
																	}
																	else
																	{
																		return false;
																	}'),
									'before' => '<img src="',
									'after' => '" alt="" />',
									'parse_content' => false,
								),
								array(
									'name' => 'img',
									'type' => 'attribute',
									'attributes' => array(
																		'width' => array(
																								 'regex' => '~\d+~',
																							 ),
																		'height' => array(
																									'regex' => '~\d+~',
																								),
																	),
									'callback' => create_function('$content, $data', '
																	if(preg_match(\'~(?:((?:http)(?:s)?://|www\.)(?:[\w+?\.\w+])+(?:[a-zA-Z0-9\~\!\@\#\$\%\^\&amp;\*\(\)_\-\=\+\\\/\?\.\:\;\\\'\,]*)?)~i\', $content))
																	{
																		return \'<img src="\'. $content. \'" width="\'. $data[\'width\']. \'" height="\'. $data[\'height\']. \'" alt="" />\';
																	}
																	else
																	{
																		return false;
																	}'),
									'before' => '',
									'after' => '',
									'parse_content' => false,
								),
								/* Maybe [img=...] (empty) and [img src= width= height=]? */
								/* Some empty (no closing tags) BBCode */
								array(
									'name' => 'hr',
									'type' => 'empty-basic',
									'before' => '<hr />',
									'after' => '',
								),
								array(
									'name' => 'br',
									'type' => 'empty-basic',
									'before' => '<br />',
									'after' => '',
								),
								/* Lists */
								array(
									'name' => 'list',
									'type' => 'basic',
									'before' => '<ul>',
									'after' => '</ul>',
									'block_level' => true,
									'required_children' => array('li'),
								),
								array(
									'name' => 'olist',
									'type' => 'basic',
									'before' => '<ol>',
									'after' => '</ol>',
									'block_level' => true,
									'required_children' => array('li'),
								),
								array(
									'name' => 'li',
									'type' => 'basic',
									'before' => '<li>',
									'after' => '</li>',
									'block_level' => true,
									'required_parents' => array('list', 'olist'),
								),
								/* Tables */
								array(
									'name' => 'columns',
									'type' => 'basic',
									'before' => '<table><tr><td>',
									'after' => '</td></tr></table>',
									'block_level' => true,
								),
								array(
									'name' => 'next',
									'type' => 'empty-basic',
									'before' => '</td><td>',
									'after' => '',
									'block_level' => true,
									'required_parents' => array('columns'),
								),
								array(
									'name' => 'table',
									'type' => 'basic',
									'before' => '<table>',
									'after' => '</table>',
									'block_level' => true,
									'required_children' => array('tr'),
								),
								array(
									'name' => 'tr',
									'type' => 'basic',
									'before' => '<tr>',
									'after' => '</tr>',
									'block_level' => true,
									'required_parents' => array('table'),
									'required_children' => array('td'),
								),
								array(
									'name' => 'td',
									'type' => 'basic',
									'before' => '<td>',
									'after' => '</td>',
									'block_level' => true,
									'required_parents' => array('tr'),
								),
							);

			// Let's add the tags.
			foreach($tags as $tag)
			{
				$bbctag = new BBCodeTag($tag);
				$this->add_tag($bbctag);
			}
		}
	}

	/*
		Method: add_tag

		Adds a BBCode tag to be supported in parsed messages.

		Parameters:
			object $tag - An instance of a BBCodeTag.

		Returns:
			bool - Returns true if the BBCode tag was added, false if not (such as
						 if the exact BBCode tag is already defined).

		Note:
			For more information about the capabilities of a BBCode tag, be sure
			to check out the <BBCodeTag> class itself.
	*/
	public function add_tag($tag)
	{
		// We require that you define BBCode tag through the BBCodeTag class.
		// Also, no point on having the same exact BBCode tags defined.
		if(!is_object($tag) || !is_a($tag, 'BBCodeTag') || !$tag->is_valid())
		{
			return false;
		}

		// There is a bit of a requirement... We will require that when one tag
		// name is considered empty, all other variations of that tag with the
		// same name must be empty as well.
		if((isset($this->index['empty'][$tag->name()]) && !$tag->is_empty()) || (isset($this->index['names'][$tag->name()]) && !isset($this->index['empty'][$tag->name()]) && $tag->is_empty()))
		{
			// Sorry, that's no good!
			return false;
		}

		// Not much else to do but add it.
		$tag_index = $this->substr($tag->name(), 0, 1);

		// We do this to make the search for a tag much quicker than one huge
		// array.
		if(!isset($this->tags[$tag_index]))
		{
			$this->tags[$tag_index] = array();
		}
		else
		{
			// Well, we also want to see if this tag is already defined.
			foreach($this->tags[$tag_index] as $defined_tag)
			{
				if($defined_tag == $tag)
				{
					// Looks like we have ourselves a duplicate!
					return false;
				}
			}
		}

		// Okay, wasn't as straightforward as I may have led myself to believe,
		// but who cares!
		$this->tags[$tag_index][] = $tag;

		// Let's add this tag name to the index!
		if(isset($this->index['names'][$tag->name()]))
		{
			// We want to keep track of the total tags with this name.
			$this->index['names'][$tag->name()]++;
		}
		else
		{
			// The first of it's kind!
			$this->index['names'][$tag->name()] = 1;
		}

		// An empty tag? Let's mark all names such as these as such.
		if($tag->is_empty())
		{
			if(isset($this->index['empty'][$tag->name()]))
			{
				$this->index['empty'][$tag->name()]++;
			}
			else
			{
				$this->index['empty'][$tag->name()] = 1;
			}
		}

		// All tags with the same name will have the same parent and child
		// constraints.
		if(count($tag->required_parents()) > 0)
		{
			$this->index['constraints']['parents'][$tag->name()] = array_merge($tag->required_parents(), isset($this->index['constraints']['parents'][$tag->name()]) ? $this->index['constraints']['parents'][$tag->name()] : array());
		}

		if(count($tag->required_children()) > 0)
		{
			$this->index['constraints']['children'][$tag->name()] = array_merge($tag->required_children(), isset($this->index['constraints']['children'][$tag->name()]) ? $this->index['constraints']['children'][$tag->name()] : array());
		}

		// Since something was added the basic, value and attribute indexes are
		// no longer valid!
		$this->index['valid'] = false;

		return true;
	}

	/*
		Method: tag_exists

		Checks whether there is a BBCode tag defined, whether this check be done
		through a string (which only compares tag names) or through a BBCodeTag
		instance.

		Parameters:
			mixed $tag - A string containing the tags name or an instance of
									 BBCodeTag.

		Returns:
			bool - Returns true if the tag exists, false if not.
	*/
	public function tag_exists($tag)
	{
		// Make sure it is either a string or an instance of BBCodeTag.
		if(!is_string($tag) && (!is_object($tag) || !is_a($tag, 'BBCodeTag') || !$tag->is_valid()))
		{
			return false;
		}
		// If we received a string, we can check real quick-like!
		elseif(is_string($tag))
		{
			return isset($this->index['names'][$this->strtolower(trim($tag))]);
		}

		// Now, let's see... Tag names are case-insensitive.
		$tag_index = $this->strtolower($this->substr(is_string($tag) ? $tag : $tag->name(), 0, 1));

		// Let's do a quick check.
		if(!isset($this->tags[$tag_index]))
		{
			// It doesn't exist.
			return false;
		}
		else
		{
			foreach($this->tags[$tag_index] as $defined)
			{
				// So, how are we doing this?
				if((is_string($tag) && $defined->name() == $tag) || (is_object($tag) && $defined == $tag))
				{
					return true;
				}
			}

			return false;
		}
	}

	/*
		Method: remove_tag

		Removes the specified tag.

		Parameters:
			mixed $tag - A string containing the tags name or an instance of
									 BBCodeTag.

		Returns:
			mixed - If a string is specified an integer containing number of tags
							removed will be returned, otherwise if a BBCodeTag is supplied
							true will be returned if it was removed and false if not.

		Note:
			If you specify a tag name, all tags matching the name will be removed.
	*/
	public function remove_tag($tag)
	{
		// Make sure it is either a string or an instance of BBCodeTag.
		if(!is_string($tag) && (!is_object($tag) || !is_a($tag, 'BBCodeTag') || !$tag->is_valid()))
		{
			return false;
		}

		$tag_index = $this->strtolower($this->substr(is_string($tag) ? $tag : $tag->name(), 0, 1));

		// If this isn't defined, then there is nothing we can remove.
		if(!isset($this->tags[$tag_index]))
		{
			return false;
		}
		else
		{
			// Keep track of whether we found and removed anything, and how many.
			$found = 0;
			foreach($this->tags[$tag_index] as $index => $defined)
			{
				// So, how are we doing this?
				if(is_string($tag) && $defined->name() == $tag)
				{
					// Delete it.
					unset($this->tags[$tag_index][$index]);

					// That's one less in the name index.
					$this->index['names'][$defined->name()]--;

					// No more BBCode tags by this name?
					if($this->index['names'][$defined->name()] <= 0)
					{
						// No point on keeping the name in the index, then.
						unset($this->index['names'][$defined->name()]);

						// Along with removing all constraints, as well.
						unset($this->index['constraints']['parents'], $this->index['constraints']['children']);
					}

					// An empty? An extra step, then.
					if($defined->is_empty())
					{
						$this->index['empty'][$defined->name()]--;

						if($this->index['empty'][$defined->name()] <= 0)
						{
							unset($this->index['empty'][$defined->name()]);
						}
					}

					// Flag that we found and removed something.
					$found++;
				}
				elseif(is_object($tag) && $defined == $tag)
				{
					// Just delete it, and we're done.
					unset($this->tags[$tag_index][$index]);

					// That's one less in the name index.
					$this->index['names'][$defined->name()]--;

					// No more BBCode tags by this name?
					if($this->index['names'][$defined->name()] <= 0)
					{
						// No point on keeping the name in the index, then.
						unset($this->index['names'][$defined->name()]);

						// Along with removing all constraints, as well.
						unset($this->index['constraints']['parents'], $this->index['constraints']['children']);
					}

					// An empty? An extra step, then.
					if($defined->is_empty())
					{
						$this->index['empty'][$defined->name()]--;

						if($this->index['empty'][$defined->name()] <= 0)
						{
							unset($this->index['empty'][$defined->name()]);
						}
					}

					// The indexes are no longer valid! Darn!
					$this->index['valid'] = false;

					return true;
				}
			}

			// Only invalidate the index if there were tags found and removed.
			if($found > 0)
			{
				$this->index['valid'] = false;
			}

			return $found;
		}
	}

	/*
		Method: add_smiley

		Adds a smiley which will be replaced with the specified image.

		Parameters:
			string $smiley - The smiley code, like :-P
			string $image - The URL to the image containing the smiley.
			string $name - The name of the smiley, if desired, such as "Tongue,"
										 which will be used in the <img /> tag.

		Returns:
			bool - Returns true if the smiley was added, false if not (such as if
						 the smiley already exists).

		Note:
			Smileys are replaced in a case-insensitive manner.
	*/
	public function add_smiley($smiley, $image, $name = null)
	{
		// So, does it exist?
		if($this->smiley_exists($smiley))
		{
			return false;
		}

		// Just add the smiley. Not much to it ;)
		$this->smiley[$this->htmlspecialchars($this->strtolower($smiley))] = '<img src="'. $this->htmlspecialchars($image). '" alt="'. $this->htmlspecialchars($smiley). '" title="'. $this->htmlspecialchars($name !== null ? $name : $smiley). '" />';

		return true;
	}

	/*
		Method: smiley_exists

		Checks whether the specified smiley exists.

		Parameters:
			string $smiley

		Returns:
			bool - Returns true if the smiley exists, false if not.
	*/
	public function smiley_exists($smiley)
	{
		return isset($this->smiley[$this->htmlspecialchars($smiley)]);
	}

	/*
		Method: remove_smiley

		Removes the specified smiley.

		Parameters:
			string $smiley - The smiley to be removed.

		Returns:
			bool - Returns true if the smiley was removed, false if not.
	*/
	public function remove_smiley($smiley)
	{
		// We can't remove something that doesn't exist.
		if(!$this->smiley_exists($smiley))
		{
			return false;
		}
		else
		{
			// Just remove it, simple as that.
			unset($this->smiley[$this->htmlspecialchars($smiley)]);

			return true;
		}
	}

	/*
		Method: set_cachedir

		Sets the directory which will be used to contain cached parsed messages.

		Parameters:
			string $directory - A writable directory, or null to disable caching.

		Returns:
			bool - Returns true if the directory was set successfully, false if
						 not, such as if the directory did not exist and couldn't be
						 created or if the directory is not writable.
	*/
	public function set_cachedir($directory)
	{
		// Want to disable caching? Fine with me.
		if($directory === null)
		{
			$this->cachedir = null;

			return true;
		}
		else
		{
			// Let's see, does the directory exist?
			if(is_dir($directory) && !is_writable($directory))
			{
				// Sounds good to me.
				$this->cachedir = realpath($directory);

				return true;
			}
			elseif(is_dir($directory))
			{
				// Couldn't write to it, oh well!
				return false;
			}
			else
			{
				// Let's see if we can create it.
				if(@mkdir($directory, 0755, true) && is_writable($directory))
				{
					$this->cachedir = realpath($directory);

					return true;
				}
				else
				{
					// Just in case we created, why don't we try and remove it?
					@unlink($directory. '/');

					return false;
				}
			}
		}
	}

	/*
		Method:: set_cachettl

		How long, in seconds, a cached message is considered "fresh."

		Parameters:
			int $seconds - How long messages should be considered "fresh."

		Returns:
			bool - Returns true if the ttl was set, false if not.
	*/
	public function set_cachettl($seconds)
	{
		if((int)$seconds < 0)
		{
			return false;
		}
		else
		{
			// Set it, and forget it!
			$this->set_cachettl = (int)$seconds;

			return true;
		}
	}

	/*
		Method: set_autopurge

		Whether expired cached messages should be purged periodically.

		Parameters:
			bool $enabled

		Returns:
			void - Nothing is returned by this method.
	*/
	public function set_autopurge($enabled)
	{
		$this->autopurge = !empty($enabled);
	}

	/*
		Method: parse

		Parses the specified message containing BBCode and interprets it into
		HTML.

		Parameters:
			string $message - The message to parse.
			bool $is_encoded - Whether the message has already been passed through
												 the <www.php.net/htmlspecialchars> function,
												 defaults to false.
			string $message_id - An string used to uniquely identify the message
													 in the cache, if none is supplied a hash of the
													 supplied message will be used -- which can be
													 slower.
			bool $nocache - Whether the message should not be cached.

		Returns:
			string - Returns a string containing the parsed message.

		Note:
			It is very important that the $is_encoded parameter is set correctly,
			because if the message is not encoded and the parameter is set to true
			the message will not be parsed properly... Not only that, but passing
			the message through <www.php.net/htmlspecialchars> will remove XSS
			threats (unless of course a BBCode tag doesn't properly handle input).
	*/
	public function parse($message, $is_encoded = false, $message_id = null, $nocache = false)
	{
		// Message not yet encoded? Then we should do that ourselves.
		if(empty($is_encoded))
		{
			$message = $this->htmlspecialchars($message);
		}

		// Is caching enabled?
		if($this->cachedir() !== null)
		{
			// Were we supplied a message identifier?
			if($this->strlen($message_id) == 0)
			{
				// Then we can make one, easy!
				$message_id = sha1($message);
			}

			// For safety reasons, we will hash the message ID.
			$message_id = sha1($message_id);

			// Before we do this, do you want to automatically purge all old
			// cached messages? Good idea!
			if($this->autopurge() && mt_rand(1, 100) == 54)
			{
				// Let the purge method do it for us.
				$this->purge();
			}

			// Does this message appear to have a cached version?
			if(file_exists($this->cachedir(). '/'. $message_id. '.bbc-cache.php') && filemtime($this->cachedir(). '/'. $message_id. '.bbc-cache.php') + $this->cachettl() > time())
			{
				// We need to define a constant in order to get what we want.
				if(!defined('INBBCODECLASS'))
				{
					define('INBBCODECLASS', true);
				}

				require($this->cachedir(). '/'. $message_id. '.bbc-cache.php');

				// Just to be sure...
				if(isset($message_cache))
				{
					return $message_cache;
				}
			}
			else
			{
				// Let's delete it.
				@unlink($this->cachedir(). '/'. $message_id. '.bbc-cache.php');
			}
		}

		// The to_struct method will turn the message into an array of Nodes.
		// That will then be used to interpret the message into HTML.
		$struct_data = $this->to_struct($message);

		// Is the tag index invalid? Then fix it!
		if(!$this->index['valid'])
		{
			$this->rebuild_index();
		}

		// Now we need to interpret that structure into something useful, like
		// actually use the defined BBCode tags, ya know?
		$message = $this->interpret_struct($struct_data[0], $struct_data[1]);

		// Do we want to cache the message?
		if($this->cachedir() !== null)
		{
			// The message ID was already all set, seeing as caching is enabled.
			// So, let's save it.
			$fp = fopen($this->cachedir(). '/'. $message_id. '.bbc-cache.php', 'w');
			flock($fp, LOCK_EX);

			fwrite($fp, '<?php if(!defined(\'INBBCODECLASS\')) { die; } $message_cache = '. var_export($message). '; ?>');

			flock($fp, LOCK_UN);
			fclose($fp);
		}

		return $message;
	}

	/*
		Method: to_struct

		A private method which turns the supplied message into an array of
		Nodes.

		Parameters:
			string $message

		Returns:
			array
	*/
	private function to_struct($message)
	{
		// Initialize a few useful things.
		// Such as the current position within the string.
		$cur_pos = 0;

		// The index within $struct that contains the last text index, this way
		// we can append anything to it if we have to (we don't want one text
		// node after another).
		$last_text_index = -1;
		$length = $this->strlen($message);
		$prev_pos = 0;
		$struct = array();

		// We also don't want to recalculate the size of $struct over and over
		// again.
		$struct_length = 0;

		// The current level.
		$current_level = 0;
		$opened_tags = array();
		$opened_count = 0;

		// Let's look for a possible tag.
		while(($pos = $this->strpos($message, '[', $cur_pos)) !== false && $pos + 1 < $length && $this->substr($message, $pos + 1, 1) != ' ')
		{
			// Before we handle the possible preceding text, why don't we make sure
			// that this tag will work?
			$last_pos = $pos;
			while($pos < $length)
			{
				$amp_pos = $this->strpos($message, '&', $pos);
				$brk_pos = $this->strpos($message, ']', $pos);

				// Does the bracket come before the ampersand?
				// But the ampersand may not even be a quote as well!
				if($amp_pos === false || $brk_pos === false || $brk_pos < $amp_pos || !in_array($quote_type = $this->substr($message, $amp_pos, 6), array('&quot;', '&#039;')))
				{
					// Sweet!
					$pos = $brk_pos;

					break;
				}

				// Now, can we find the next quote?
				while($amp_pos + 6 < $length && ($amp_pos = $this->strpos($message, $quote_type, $amp_pos + 6)) !== false && $this->substr($message, $amp_pos - 1, 1) == '\\');

				// Did our search come up with nothing?
				if($amp_pos + 6 >= $length || $amp_pos === false)
				{
					break;
				}

				$pos = $amp_pos + 6;
			}

			// So, did we find a valid tag?
			if($this->substr($message, $pos, 1) == ']')
			{
				// Yup, we sure did! So we will want to make a node to contain the
				// text preceding the tag.
				$saved = false;
				if($prev_pos != $last_pos)
				{
					$node = new TextNode($this->substr($message, $prev_pos, $last_pos - $prev_pos), $current_level);
					$struct[$struct_length++] = $node;
					$last_text_index = $struct_length - 1;
					$saved = true;
				}

				// Now for the tag itself.
				$node = new TagNode($this->substr($message, $last_pos, $pos - $last_pos + 1), $this->substr($message, $last_pos + 1, 1) == '/', 0, $this);

				// Woah there, horsey! Do we even have a tag by that name?
				if(isset($this->index['names'][$node->getTag()]))
				{
					// Yup, we do.
					$struct[$struct_length++] = $node;
					$last_text_index = -1;

					// Maybe this tag is being opened?
					if(!$node->is_closing())
					{
						// Set the tag nodes current level, along with adding it to the
						// list of opened tags.
						$node->setLevel($current_level);

						// We need to set the parent node of $node, along with adding
						// $node to the children of the parent.
						// This is pretty straightforward, as it is the most recently
						// opened tag.
						$node->parentNode($opened_count > 0 ? $opened_tags[$opened_count - 1]['tag'] : null);

						// If there was no parent node, then it can't really be a child,
						// can it?
						if($node->parentNode() !== null)
						{
							// This will add this node along with its other children.
							$node->parentNode()->childNodes($node);
						}

						// Before we add this to the list of opened tags, we will check
						// whether it is an empty tag, because if it is, it doesn't get
						// opened, so it won't need to be closed ;-)
						if(!$this->tag_is_empty($node->getTag()))
						{
							$opened_tags[$opened_count++] = array(
																								'tag' => $node->getTag(),
																								'level' => $current_level++,
																								'pos' => $struct_length - 1,
																							);
						}
					}
					// Nope, it is being closed, and we have a bit of work to do!
					else
					{
						// First we need to see if this tag was ever opened.
						$stop = $opened_count;
						for($index = $opened_count - 1; $index >= 0; $index--)
						{
							if($opened_tags[$index]['tag'] == $node->getTag())
							{
								// It looks like the tag was opened, at some point.
								$stop = $index;

								break;
							}
						}

						// Did we find the opening tag?
						if($stop < $opened_count)
						{
							// We may need to move the current node back so we can insert
							// any tags that need closing.
							$current = $opened_count - 1;

							// We will want to overwrite the current node (which will be
							// added back after the loop).
							$struct_length = $struct_length - 1;

							while($current > $stop)
							{
								// Take off the last tag...
								$opened_tag = $opened_tags[$current];
								unset($opened_tags[$current]);

								// Also subtract one from the total opened tag count.
								$opened_count--;

								// Add the closing tag to the structure.
								$struct[$struct_length++] = new TagNode('[/'. $opened_tag['tag']. ']', true, $opened_tag['level']);

								// !!! Do closing tags need parents?

								// Let's tell the opening tag where the ending tag is
								// located, which will make some things quite a bit faster
								// later.
								$struct[$opened_tag['pos']]->setClosingAt($struct_length - 1);

								// Move to the next.
								$current--;
							}

							// Now add the current tag we were supposed to be dealing with
							// back to $struct.
							$struct[$struct_length++] = $node;

							// Set a couple important things.
							$node->setLevel($opened_tags[$opened_count - 1]['level']);
							$struct[$opened_tags[$opened_count - 1]['pos']]->setClosingAt($struct_length - 1);

							// !!! Do closing tags need parents?

							// Now remove it from the list of opened tags.
							unset($opened_tags[--$opened_count]);

							// Now set the proper level.
							$current_level = $node->level() - 1;
						}
						else
						{
							// We will just ignore this tag, then.
							$node->setIgnore(true);
						}
					}

					// Now, everything has been handled up to this point.
					$prev_pos = $pos + 1;
				}
				// We need to do a little something if the previous content was
				// saved but not the TagNode itself.
				elseif(!empty($saved))
				{
					// We want to save this with the previous text node if we can.
					if($last_text_index > -1)
					{
						$struct[$last_text_index]->appendText($node->text());
					}
					// Otherwise we will need to create one.
					else
					{
						$struct[$struct_length++] = new TextNode($node->text(), $current_level);
						$last_text_index = $struct_length - 1;

						// Text nodes have parents, but they don't have any children.
						if($opened_count > 0)
						{
							$struct[$struct_length - 1]->parentNode($opened_tags[$opened_count - 1]['tag']);
						}
					}

					$prev_pos = $pos + 1;
				}
			}

			// Alright, let's move on!
			$cur_pos = $pos + 1;
		}

		// Was there some text left?
		if($prev_pos < $length)
		{
			// Yup, and we don't want to leave it out!
			$node = new TextNode($this->substr($message, $prev_pos), $current_level);
			$struct[$struct_length++] = $node;
		}

		// Were there any tags that weren't closed by the end of the message?
		// That's fine, we can fix that.
		if($opened_count > 0)
		{
			while(--$opened_count >= 0)
			{
				$struct[$struct_length++] = new TagNode('[/'. $opened_tags[$opened_count]['tag']. ']', true, $opened_tags[$opened_count]['level']);
				$struct[$opened_tags[$opened_count]['pos']]->setClosingAt($struct_length - 1);
			}
		}

		$start_time = microtime(true);
		for($index = 0; $index < $struct_length; $index++)
		{
			if($struct[$index]->type() == 'tag')
			{
				$struct[$index]->checkConstraints();

				if($struct[$index]->ignore())
				{
					$struct[$struct[$index]->closingAt()]->setIgnore(true);
				}
			}
		}

		// And here you go. I did my job... We will send along the structure
		// length as well. No need to have the other method recalculate it.
		return array($struct, $struct_length);
	}

	/*
		Method: tag_is_empty

		Determines whether the specified tag name is an empty tag.

		Parameters:
			string $tag_name - The name of the tag name to check.

		Returns:
			bool - Returns true if the specified tag name is an empty tag, false
						 if not.
	*/
	private function tag_is_empty($tag_name)
	{
		return isset($this->index['empty'][$tag_name]) && $this->index['empty'][$tag_name] > 0;
	}

	/*
		Method: rebuild_index

		A private method which is used to build an index of tags for fast
		retrieval when finding tags with the same name and type.

		Parameters:
			none

		Returns:
			void - Nothing is returned by this method.
	*/
	private function rebuild_index()
	{
		// Since the index is being rebuilt we want to clear it all.
		$this->index['tags'] = array();

		// Let's go through the existing tags and build the index... Makes sense
		// after all.
		foreach($this->tags as $first_char => $tags)
		{
			// ... and another for each.
			foreach($tags as $index => $tag)
			{
				// We don't want to have multiple copies of the same tag, so we will
				// just save the index.
				$this->index['tags'][substr($tag->type(), 0, 6) == 'empty-' ? substr($tag->type(), 6) : $tag->type()][$first_char][] = $index;
			}
		}

		// The index is now valid!
		$this->index['valid'] = true;
	}

	/*
		Method: find_tags

		Searches the index of supported BBCode tags to find matches for the
		specified tag.

		Parameters:
			object $node - A TagNode to find matches for.

		Returns:
			array - Returns an array containing matches to the specified tag and
							an empty array if nothing was found (of course).
	*/
	private function find_tags($node)
	{
		// What type of tag were we given?
		$type = $node->getTagType();

		// Get the first character of the tag name as well.
		$first_char = $this->substr($node->getTag(), 0, 1);

		// Let's take a look to see if we can find anything.
		if(isset($this->index['tags'][$type][$first_char]))
		{
			$matches = array();
			$attributes = $type == 'attribute' ? array_keys($node->getAttributes()) : false;
			foreach($this->index['tags'][$type][$first_char] as $index)
			{
				// Make sure the names match.
				if($this->tags[$first_char][$index]->name() != $node->getTag())
				{
					continue;
				}
				// We only need to do a check on attribute type tags.
				elseif($type == 'attribute')
				{
					// Let's check if they are all there.
					$is_match = true;
					foreach($this->tags[$first_char][$index]->attributes() as $attr_name => $options)
					{
						// Make sure that the attribute is set or that it isn't required
						// in such as case.
						if(!in_array($attr_name, $attributes) && !$options['optional'])
						{
							// It's not a match.
							$is_match = false;

							break;
						}
					}

					// So, was it a match?
					if(!$is_match)
					{
						// Nope.
						continue;
					}
				}

				// Save it... Nothing else to do.
				$matches[] = $this->tags[$first_char][$index];
			}

			return $matches;
		}
		else
		{
			// We didn't find anything because there is nothing like that!
			return array();
		}
	}

	/*
		Method: required_parents

		Retrieves the required parent tags for the specified tag name.

		Parameters:
			string $tag_name - The name of the tag.

		Returns:
			array - Returns an array containing the names of the required parent
							tags.
	*/
	public function required_parents($tag_name)
	{
		return isset($this->index['constraints']['parents'][$tag_name]) ? $this->index['constraints']['parents'][$tag_name] : array();
	}

	/*
		Method: required_children

		Retrieves the required child tags for the specified tag name.

		Parameters:
			string $tag_name - The name of the tag.

		Returns:
			array - Returns an array containing the names of the required child
							tags.
	*/
	public function required_children($tag_name)
	{
		return isset($this->index['constraints']['children'][$tag_name]) ? $this->index['constraints']['children'][$tag_name] : array();
	}

	private function interpret_struct($struct, $struct_length)
	{
		// We will need a few variables to keep track of things, such as a
		// string to store the generated message.
		$message = '';

		// Then of course something to keep track of our current location as we
		// traverse the parsed message.
		$pos = 0;

		// Keep track of opened tags...
		$opened_tags = array();
		$opened_count = 0;

		// And nope, we do not need to keep track of the opened tags, as the
		// lexer took care of that for us :-).
		while($pos < $struct_length)
		{
			// Do we need to take care of this tag? Make sure it isn't supposed to
			// be ignored -- for whatever reason, I don't care ;-).
			if($struct[$pos]->type() == 'tag' && !$struct[$pos]->ignore())
			{
				// Check to see whether it is an opening tag...
				if(!$struct[$pos]->is_closing())
				{
					// It is time to look for all the tags which could be possible
					// matches for the current tag. Luckily we have another method
					// which will handle that.
					$found = false;
					$matches = $this->find_tags($struct[$pos]);

					// So, did we find anything?
					if(count($matches) > 0)
					{
						// Let's get to work, and what work it will be :-/.
						$tag_content = null;
						$tag_handled = false;
						foreach($matches as $match)
						{
							$data = null;

							// Let's see if there is any data which needs to be validated.
							// We need to handle each tag differently, depending upon the
							// type. First off: value ([name=...])!
							if($struct[$pos]->getTagType() == 'value')
							{
								// Fetch the value of the tag so we can get started.
								$data = $struct[$pos]->getValue();

								// Now fetch the options for this match.
								$options = $match->value();
								$regex = isset($options['regex']) && $this->strlen($options['regex']) > 0 ? $options['regex'] : false;
								$callback = isset($options['callback']) && is_callable($options['callback']) ? $options['callback'] : false;

								// Does the tag have any regular expression specified?
								if($regex !== false && @preg_match($regex, $data) == 0)
								{
									// Looks like this match is, erm, no match. So our search
									// continues!
									continue;
								}

								if($callback !== false && ($data = call_user_func($callback, $data)) === false)
								{
									// Hmm, I guess the callback wasn't very happy with the
									// users selected input. Oh well, better luck next match?
									continue;
								}
							}
							// Now to handle an attribute typed tag, such as:
							// [name attr=val].
							elseif($struct[$pos]->getTagType() == 'attribute')
							{
								// Fetch all the set attributes.
								$data = $struct[$pos]->getAttributes();

								// Keep track of whether an error occurred.
								$attr_error = false;
								foreach($match->attributes() as $attr_name => $options)
								{
									// If this tag is optional, we may need to skip this if
									// the attribute isn't present.
									if($options['optional'] === true && !array_key_exists($attr_name, $data))
									{
										continue;
									}

									// Regular expression, perhaps?
									if(isset($options['regex']) && $this->strlen($options['regex']) > 0 && @preg_match($options['regex'], $data[$attr_name]) == 0)
									{
										// Well, that's no good.
										$attr_error = true;

										continue;
									}

									if(isset($options['callback']) && is_callable($options['callback']) && ($data[$attr_name] = call_user_func($options['callback'], $data[$attr_name])) === false)
									{
										// This isn't any good, either.
										$attr_error = true;

										continue;
									}
								}

								// Did we encounter an error?
								if($attr_error === true)
								{
									continue;
								}
							}

							// TODO: Disallowed/allowed children

							// Thankfully, parent/child constraints have already been
							// checked! So we can get right to it!
							// We may need to gather up the content of the tag.
							if((is_callable($match->callback()) || $match->parse_content() === false) && $tag_content === null)
							{
								// Luckily there is a function to do this for us.
								$tag_content = array_slice($struct, $pos + 1, $struct[$pos]->closingAt() - $pos - 1);
							}

							$tag_handled_content = '';
							if(is_callable($match->callback()) || $match->parse_content() === false)
							{
								// Does the tag want the content parsed or not?
								if($match->parse_content())
								{
									$tag_handled_content = $this->interpret_struct($tag_content);
								}
								// If the tag doesn't want the content parsed, then we will
								// just collect all the text components together.
								elseif(is_array($tag_content))
								{
									foreach($tag_content as $n)
									{
										$tag_handled_content .= $n->text();
									}
								}
							}

							$tag_returned_content = false;
							if(is_callable($match->callback()) && ($tag_returned_content = call_user_func($match->callback(), $tag_handled_content, $data)) === false)
							{
								// That's not a good sign.
								continue;
							}

							if($match->block_level())
							{
								// Looks like we may need to close some tags.
								$popped = null;
								while(($popped = array_pop($opened_tags)) !== null && !$popped['match']->block_level())
								{
									$message .= str_replace(array_keys($popped['replacements']), array_values($popped['replacements']), $popped['match']->after());
									$opened_count--;

									// Mark the other closing tag as ignored, it won't be
									// needed anymore.
									$struct[$popped['pos']->closingAt()]->setIgnore(true);
								}

								if($popped !== null && $popped['match']->block_level())
								{
									// Woops, we should probably put that back!
									$opened_tags[] = $popped;
									$opened_count++;
								}
							}

							// There could be some things in need of replacing.
							$replacements = array();

							// {value} gets replaced with the value.
							if($struct[$pos]->getTagType() == 'value')
							{
								$replacements['{value}'] = $data;
							}
							// ... and all {attribute name}'s get replace with their
							// values as well.
							elseif($struct[$pos]->getTagType() == 'attribute')
							{
								foreach($data as $attrName => $attrValue)
								{
									$replacements['{'. $attrName. '}'] = $attrValue;
								}
							}

							// If there was a callback to handle the content within the
							// tag, we may need to have a replacement for that as well.
							if($tag_returned_content !== false)
							{
								$replacements['[content]'] = $tag_returned_content;
							}

							$message .= str_ireplace(array_keys($replacements), array_values($replacements), $match->before());

							// Was there content which needed to be added?
							if(is_callable($match->callback()) || !$match->parse_content())
							{
								// If the BBCode tag took the handled content then we want
								// to use the returned content, otherwise the previously
								// handled (handled being parsed or not).
								$message .= $tag_returned_content !== false ? $tag_returned_content : $tag_handled_content;
							}

							// Do we need to add this to the opened tags array? We won't
							// need to if the tag is empty, if the BBCode tag returned the
							// content, or if the content wasn't parsed.
							if(!$match->is_empty() && !is_callable($match->callback()) && $match->parse_content())
							{
								// Alright, go ahead and add this tag to the opened list.
								$opened_tags[] = array(
																	 'match' => $match,
																	 'replacements' => $replacements,
																	 'pos' => $pos,
																 );
							}
							else
							{
								// Looks like we'll deal with the closing tag right now.
								$message .= str_ireplace(array_keys($replacements), array_values($replacements), $match->after());

								// Move the current position to the closing tag... Unless it
								// is an empty tag, in which case it has no closing tag.
								if(!$match->is_empty())
								{
									$pos = $struct[$pos]->closingAt();
								}
							}

							// We handled the tag, so go ahead and move along.
							$tag_handled = true;

							break;
						}

						// Move to the next item... Possibly. If not, then the tag will
						// simply be added as text below.
						if(!empty($tag_handled))
						{
							$pos++;

							continue;
						}
					}

					// If we got to this point, this means that the tag has no matches
					// so we will want to mark its closing tag as ignored.
					$struct[$pos]->setIgnore(true);
					$struct[$struct[$pos]->closingAt()]->setIgnore(true);
				}
				else
				{
					// Looks like it is a closing tag. That's pretty easy to deal with
					// unlike the opening part.
					$popped = array_pop($opened_tags);
					$opened_count--;

					if($popped !== null && $popped['match']->name() == $struct[$pos]->getTag())
					{
						$message .= str_ireplace(array_keys($replacements), array_values($replacements), $popped['match']->after());

						// Got it!
						$pos++;

						continue;
					}
					else
					{var_dump($popped);
						echo('Fatal error');
					}
				}
			}

			// Add the text to the message. This could be actual text, or it could
			// be a tag which was not defined/valid... Either way, whole really
			// cares? I know I don't! :-P.
			$message .= $this->format_text($struct[$pos]->text());

			// Move along, please.
			$pos++;
		}

    return $message;
  }

	/*
		Method: format_text

		Formats the specified text, depending upon the current options, such as
		automatically linking URL's, email addresses, and/or replacing smileys
		with images.

		Parameters:
			string $text

		Returns:
			string
	*/
	private function format_text($text)
	{
		// !!! TODO: Find a way to replace stuff NOT in HTML.

		$replacements = array(
											"\r\n" => '<br />',
											"\n" => '<br />',
										);

		// Would you like to have URLs automatically linked?
		if($this->autoreplace())
		{
			if(preg_match_all('~(?:(?:https?://|www.)(?:[-\w\.]+)+(?::\d+)?(?:/(?:[\w/_\.]*(?:\?\S+)?)?)?)~i', $text, $matches) > 0)
			{
				foreach($matches[0] as $match)
				{
					$replacements[$match] = '<a href="'. ($this->substr($match, 0, 4) == 'www.' ? 'http://' : ''). $match. '" target="_blank">'. $match. '</a>';
				}
			}
		}

		// Do you have any smileys?
		if(count($this->smileys) > 0)
		{
			// Just add them to the replacement array.
			foreach($this->smileys as $smiley)
			{
				$replacements[$smiley] = $tag;
			}
		}

		return str_ireplace(array_keys($replacements), array_values($replacements), $text);
	}

	/*
		Method: cachedir

		Returns the currently set cache directory.

		Parameters:
			none

		Returns:
			string
	*/
	public function cachedir()
	{
		return $this->cachedir;
	}

	/*
		Method: cachettl

		Returns the currently set time-to-live for cached messages, in seconds.

		Parameters:
			none

		Returns:
			int
	*/
	public function cachettl()
	{
		return $this->cachettl;
	}

	/*
		Method: autopurge

		Returns whether the class is set to periodically purge expired messages.

		Parameters:
			none

		Returns:
			bool
	*/
	public function autopurge()
	{
		return $this->autopurge;
	}

	/*
		Method: autoreplace

		Returns whether links will automatically be replaced.

		Parameters:
			none

		Returns:
			bool
	*/
	public function autoreplace()
	{
		return $this->autoreplace;
	}

	/*
		Method: purge

		Purges all expired messages from the cache directory.

		Parameters:
			bool $all - Whether to delete each cached message, even if it isn't
									expired. Defaults to false.

		Returns:
			int - Returns the total number of messages deleted.
	*/
	public function purge($all = false)
	{
		$filenames = scandir($this->cachedir());
		foreach($filenames as $filename)
		{
			// We only care about certain messages.
			if($this->substr($filename, -14, 14) == '.bbc-cache.php' && (filemtime($this->cachedir(). '/'. $filename) + $this->cachettl() > time() || $all === true))
			{
				@unlink($this->cachedir(). '/'. $filename);
			}
		}
	}

	/*
		Method: strlen

		This is a private method used to check the length of a string, which
		will use a multi-byte safe function if available.
	*/
	private function strlen($str)
	{
		return function_exists('mb_strlen') ? mb_strlen($str) : strlen($str);
	}

	/*
		Method: substr

		This is a private method used to fetch the specified portion of a
		string, which will use a multi-byte safe function if available.
	*/
	private function substr($str, $start, $length = null)
	{
		return $length === null ? (function_exists('mb_substr') ? mb_substr($str, $start) : substr($str, $start)) : (function_exists('mb_substr') ? mb_substr($str, $start, $length) : substr($str, $start, $length));
	}

	/*
		Method: strpos

		This is a private method used to fetch the position of a string inside
		the specified string, which will use a multi-byte safe function if
		avaiable.
	*/
	private function strpos($haystack, $needle, $start = null)
	{
		return function_exists('mb_strpos') ? ($start !== null ? mb_strpos($haystack, $needle, $start) : mb_strpos($haystack, $needle)) : ($start !== null ? strpos($haystack, $needle, $start) : strpos($haystack, $needle));
	}

	/*
		Method: strtolower

		This is a private method used to lowercase a string, which will use a
		multi-byte safe function if available.
	*/
	private function strtolower($str)
	{
		return function_exists('mb_strtolower') ? mb_strtolower($str) : strtolower($str);
	}

	/*
		Method: htmlspecialchars

		This is a private method used to convert certain characters into their
		HTML entity counterpart for safety, using the proper character encoding.
	*/
	private function htmlspecialchars($str)
	{
		return htmlspecialchars($str, ENT_QUOTES, function_exists('mb_internal_encoding') ? mb_internal_encoding() : 'UTF-8');
	}
}

/*
	Class: BBCodeTag

	This class is used to define a BBCode tag which can then be used in a
	message being parsed by the BBCode class.
*/
class BBCodeTag
{
	// Variable: name
	// The name of the tag.
	private $name;

	// Variable: type
	// The type of tag.
	private $type;

	// Variable: value
	// An array containing options when dealing with a value type tag.
	private $value;

	// Variable: attributes
	// An array containing options when dealing with an attribute type tag.
	private $attributes;

	// Variable: parse_content
	// Whether the content within the tag should be parsed.
	private $parse_content;

	// Variable: callback
	// A callback which will be passed the content of the tag.
	private $callback;

	// Variable: before
	// A string containing the content which will replace the BBCodes opening
	// tag.
	private $before;

	// Variable: after
	// A string containing the content which will replace the BBCodes closing
	// tag.
	private $after;

	// Variable: required_parents
	// An array containing tags which need to be opened immediately prior
	// (level wise) in order for this tag to be parsed.
	private $required_parents;

	// Variable: required_children
	// An array containing tags which need to be immediately opened (level
	// wise) for this tag to be parsed.
	private $required_children;

	// Variable: block_level
	// Whether the tag is a block tag, meaning that all tags, except other
	// block level tags, need to be closed. This is useful for quotes, where
	// tags like bold, italic, etc. would be closed before the quote tag was
	// handled, but all other opened quote tags would remain untouched.
	private $block_level;

	// Variable: disallowed_children
	// Declares a set of tags which will not be parsed inside the defined tag.
	// If this option is set then the allowed children option is automatically
	// disabled.
	private $disallowed_children;

	// Variable: allowed_children
	// Declares a set of tags which will be parsed inside of the defined tag,
	// but any tags not in the list will not be parsed. This is the inverse of
	// the disallowed children option. If this option is set the disallowed
	// children option is automatically disabled.
	private $allowed_children;

	// Variable: disable_formatting
	// An option which specifies the type of formatting that is to be disabled
	// when parsing the content within the defined tag, for example,
	// specifying SPEEDYOPT_DISAUTOLINK will disable automatically linking
	// URL's and SPEEDYOPT_DISSMILEYS will disable the replacement of smileys
	// with images. These options can be combined with a bitwise OR (|).
	private $disable_formatting;

	// Variable: replacements
	// This is an array containing values that need replacing in the after
	// part of the BBCode tag -- it is used by the BBCode parser itself, and
	// should not be messed with!
	private $replacements;

	/*
		Constructor: __construct

		All attributes are initialized, and all specified options are set.

		Parameters:
			array $options - An array containing options for the BBCode tag.

		Note:
			The following indexes are supported for $options:
				string name - See <BBCodeTag::set_name>.
				string type - See <BBCodeTag::set_type>.
				array value - See <BBCodeTag::set_value>.
				array attributes - See <BBCodeTag::set_attributes>.
				bool parse_content - See <BBCodeTag::set_parse_content>.
				callback callback - See <BBCodeTag::set_callback>.
				string before - See <BBCodeTag::set_before>.
				string after - See <BBCodeTag::set_after>.
				array required_parents - See <BBCodeTag::set_required_parents>.
				array required_children - See <BBCodeTag::set_required_children>.
				bool block_level - See <BBCodeTag::set_block_level>.
	*/
	public function __construct($options = array())
	{
		// Make everything empty.
		$this->name = null;
		$this->type = null;
		$this->value = array();
		$this->attributes = array();
		$this->parse_content = true;
		$this->callback = null;
		$this->before = null;
		$this->after = null;
		$this->required_parents = array();
		$this->required_children = array();
		$this->block_level = false;
		$this->disallowed_children = array();
		$this->allowed_children = array();
		$this->disable_formatting = null;
		$this->replacements = array();

		// Alright, let's set those options, if you did.
		foreach(array('name', 'type', 'value', 'attributes', 'parse_content',
									'callback', 'before', 'after', 'required_parents',
									'required_children', 'block_level', 'disallowed_children',
									'allowed_children', 'disable_formatting') as $attribute)
		{
			if(isset($options[$attribute]))
			{
				call_user_func(array($this, 'set_'. $attribute), $options[$attribute]);
			}
		}
	}

	/*
		Method: set_name

		Sets the name of the BBCode tag.

		Parameters:
			string $name - The name of the tag.

		Returns:
			bool - Returns true if the name is set successfully, false if not.

		Note:
			The name of tags are case-insensitive and can only contain the
			following characters: a-z, 0-9, -, and _.

			It is not recommended that the name of a tag be changed after being
			added to the BBCode class via the add_tag method due to how tags are
			mapped for quick finding.
	*/
	public function set_name($name)
	{
		$name = trim(strtolower($name));

		// Make sure the name isn't empty.
		if(strlen($name) == 0)
		{
			return false;
		}

		// The name of a tag can only contain alphanumeric characters, dashes,
		// and/or underscores.
		if(preg_match('~^([a-z0-9-_])+$~i', $name) == 0)
		{
			// We found an unacceptable character!
			return false;
		}

		// Looks okay to me.
		$this->name = $name;

		return true;
	}

	/*
		Method: set_type

		Sets the type of tag defined.

		Parameters:
			string $type - The type of the tag.

		Returns:
			bool - Returns true if the type of tag was set, false if not.

		Note:
			The following types of tags are supported:
				basic - A basic tag could be compared to such things as the <strong>
								or <em> tag, which has no attributes.
				value - A tag which supplies a value defined after an equals sign,
								like so: [url=(...)].
				attribute - A tag which has attributes, like [img width=100].

			Each of the aforementioned can be prepended with empty-, like
			empty-basic, which means the tag is not an opening tag, but a
			self-contained tag which has no tag closing it. An example of a tag
			would be [br], which would just display a line break. In such tag
			types, the following options are not available: parse_content and
			callback -- but that is to be expected, right?
	*/
	public function set_type($type)
	{
		$type = strtolower($type);

		// We only support these types of tags.
		if(!in_array($type, array('basic', 'value', 'attribute', 'empty-basic', 'empty-value', 'empty-attribute')))
		{
			return false;
		}

		$this->type = $type;

		return true;
	}

	/*
		Method: set_value

		Sets options for a tag with a value type.

		Parameters:
			string $options - An array containing options.

		Returns:
			bool - Returns true if the options were set, false if not.

		Note:
			The following options are supported:
				string regex - A string containing regex which is applied against
											 the value.
				callback callback - A callback which will be passed the value, and
														is expected to return the value or to return
														false if the value is unacceptable and can't
														be "saved."

			If the supplied regex fails against the value then the tag is not
			parsed.

			Also, if both the regex and callback option is supplied, the regex is
			applied first, then the callback is passed the value.

			The before and after values will have {value} replaced with the value
			supplied (which has been passed through regex and/or the callback).

			The value can be surrounded by quotes (single or double), or not have
			anything delimiting the value at all. If a value contains a space and
			is not surrounded by quotes it will still be included (so long as
			there is more content after the space), so [quote=Some Name] would
			have a value of Some Name, with or without quotes. If the value needs
			to contain square brackets, it MUST be contained within quotes. Quotes
			within a value are allowed, but if the value is contained within
			quotes the character must be escaped with a back slash (\).
	*/
	public function set_value($options)
	{
		// If this tag is not a value type, then forget it.
		if(!in_array($this->type(), array('value', 'empty-value')))
		{
			return false;
		}

		if(isset($options['regex']) && $this->strlen($options['regex']) > 0)
		{
			$this->value['regex'] = $options['regex'];
		}
		elseif(in_array('regex', array_keys($options)) && $options['regex'] === null)
		{
			unset($this->value['regex']);
		}

		if(isset($options['callback']) && is_callable($options['callback']))
		{
			$this->value['callback'] = $options['callback'];
		}
		elseif(in_array('callback', array_keys($options)) && $options['callback'] === null)
		{
			unset($this->value['callback']);
		}

		return true;
	}

	/*
		Method: set_attributes

		Sets the attributes and their options for an attribute type tag.

		Parameters:
			array $attributes - An array containing attribute options.

		Returns:
			bool - Returns true if the attributes were set, false if not.

		Note:
			In order to specify an attribute, simply use a key/value pair, with
			the key being the attributes name and the value containing the
			following options:
				string regex - A string containing regex to pass the value of the
											 attribute through.
				callback callback - A callback which will be passed the value of the
														attribute.
				bool optional - Whether the attribute is optional, if it is the
												replace option is REQUIRED.
				string replace - A string which contains the HTML attribute with a
												 value to be replaced if the attribute is optional.
												 For example, if a width attribute is optional this
												 option may be: ' width="[value]"' where [value]
												 will be replaced with the value specified in the
												 BBCode tag, then this replace value would replace
												 [width] in the before/after values of the tag. If
												 no option is supplied and the callback doesn't
												 provide one either the, in this example, [width]
												 would simply be replaced with nothing.

			Just like a tag's name, an attribute can only contain: a-z, 0-9, -,
			and _, they are also case-insensitive.

			The before and after values will have {attr_name} replaced with the
			attributes value, attr_name being the attributes name, of course.

			The BBCode class can parse attributes in a couple different ways,
			whether the value be surrounded with quotes or not (single or double).
			If the value of the attribute is not surrounded by quotes, the parser
			will cut the value off at the next space. A value surrounded by quotes
			may contain the quote character delimiting the value itself, so long
			as it is preceded by a back slash (\).
	*/
	public function set_attributes($attributes)
	{
		// Must be an attribute tag.
		if(!in_array($this->type(), array('attribute', 'empty-attribute')) || !is_array($attributes) || count($attributes) == 0)
		{
			return false;
		}

		// We don't want to clear the existing attributes until we are sure
		// what was passed is okay.
		$accepted = array();
		foreach($attributes as $name => $options)
		{
			$name = strtolower(trim($name));

			// Make sure the attribute name is alright and that a replace value is
			// specified if the tag is optional.
			if(preg_match('~^([a-z0-9_-])+$~', $name) == 0 || (!empty($options['optional']) && !isset($options['replace'])))
			{
				// So, that's not okay ;-)
				return false;
			}

			$accepted[$name] = array(
													 'regex' => isset($options['regex']) ? $options['regex'] : null,
													 'callback' => isset($options['callback']) && is_callable($options['callback']) ? $options['callback'] : null,
													 'optional' => !empty($options['optional']),
													 'replace' => !empty($options['optional']) && isset($options['replace']) ? $options['replace'] : null,
												 );
		}

		// Now, looks like everything is okay.
		$this->attributes = $accepted;

		return true;
	}

	/*
		Method: set_parse_content

		Whether the contents of the BBCode tag should be parsed.

		Parameters:
			bool $parse - Whether the content should be parsed.

		Returns:
			void - Nothing is returned by this method.
	*/
	public function set_parse_content($parse)
	{
		$this->parse_content = !empty($parse);
	}

	/*
		Method: set_callback

		Sets a callback which will be passed the contents of the tag.

		Parameters:
			callback $callback - The callback.

		Returns:
			bool - Returns true if the callback is set successfully, false if not
						 (such as if the tag type doesn't support it).

		Note:
			Please note that you can actually have the content replaced in the set
			before and after strings, but it is not {content} that is replaced,
			but [content] due to the possibility that an attribute could be named
			content.
	*/
	public function set_callback($callback)
	{
		if(substr($this->type(), 0, 6) == 'empty-' || ($callback !== null && !is_callable($callback)))
		{
			return false;
		}

		$this->callback = $callback;

		return true;
	}

	/*
		Method: set_before

		Sets the content which will be replaced with the opening tag.

		Parameters:
			string $before - The content to replace with the opening tag.

		Returns:
			void - Nothing is returned by this method.

		Note:
			Even if the tag is an empty tag, both the before and after options are
			used.
	*/
	public function set_before($before)
	{
		$this->before = $before;
	}

	/*
		Method: set_after

		Sets the content which will be replaced with the closing tag.

		Parameters:
			string $after - The content to replace with the closing tag.

		Returns:
			void - Nothing is returned by this method.
	*/
	public function set_after($after)
	{
		$this->after = $after;
	}

	/*
		Method: set_required_parents

		Tags which must have been just opened (level wise) in order for this tag
	  to be parsed.

	  Parameters:
			array $required - An array containing the names of tags which are
												required.

		Returns:
			bool - Returns true if the tags were set, false if not.
	*/
	public function set_required_parents($required)
	{
		if($required !== null && !is_array($required))
		{
			return false;
		}

		// Reset it.
		$this->required_parents = array();

		// We may not need to do this...
		if($required !== null)
		{
			// Looks like we do...
			foreach($required as $tag_name)
			{
				$tag_name = strtolower(trim($tag_name));

				// Make sure the name of the tag is allowed.
				if(preg_match('~^([a-z0-9_-])+$~i', $tag_name) > 0)
				{
					$this->required_parents[] = $tag_name;
				}
			}
		}

		return true;
	}

	/*
		Method: set_required_children

		Tags which must be opened immediately (level wise) in order for this tag
	  to be parsed.

	  Parameters:
			array $required - An array containing the names of tags which are
												required.

		Returns:
			bool - Returns true if the tags were set, false if not.
	*/
	public function set_required_children($required)
	{
		if($required !== null && !is_array($required))
		{
			return false;
		}

		// Reset it.
		$this->required_children = array();

		// We may not need to do this...
		if($required !== null)
		{
			foreach($required as $tag_name)
			{
				$tag_name = strtolower(trim($tag_name));

				// Make sure the name of the tag is allowed.
				if(preg_match('~^([a-z0-9_-])+$~i', $tag_name) > 0)
				{
					$this->required_children[] = $tag_name;
				}
			}
		}

		return true;
	}

	/*
		Method: set_block_level

		Sets whether the tag is a block level tag, meaning that all non-block
		level tags are closed before this tag would be opened.

		Parameters:
			bool $is_block - Whether the tag is a block level tag.

		Returns:
			void - Nothing is returned by this method.
	*/
	public function set_block_level($is_block)
	{
		$this->block_level = !empty($is_block);
	}

	/*
		Method: set_disallowed_children

		Sets tags which will not be parsed within the currently defined tag.

		Parameters:
			array $disallowed - An array containing tag names which will not be
													parsed.

		Returns:
			void - Nothing is returned by this method.

		Note:
			If this option is set then the <BBCodeTag::set_allowed_children>
			option is automatically disabled.
	*/
	public function set_disallowed_children($disallowed)
	{
		if(is_array($disallowed))
		{
			// Reset this option.
			$this->disallowed_children = array();

			foreach($disallowed as $tag_name)
			{
				$tag_name = trim($tag_name);

				// No sense in clogging up the "tubes" if the tag name isn't even
				// allowed...
				if(preg_match('~^([a-z0-9_-])+$~i', $tag_name) > 0)
				{
					// Looks good!
					$this->disallowed_children[] = $this->strtolower($tag_name);
				}
			}

			// Oh, and don't forget to disable the allowed children option!
			$this->set_allowed_children(false);
		}
		// Not an array? Then we will assume you want to unset this option.
		else
		{
			$this->disallowed_children = array();
		}
	}

	/*
		Method: set_allowed_children

		Sets tags that are only allowed to be parsed within the currently
		defined tag.

		Parameters:
			array $allowed - An array containing tag names which will only be
											 allowed to be parsed within the currently defined
											 tag.

		Returns:
			void - Nothing is returned by this method.

		Note:
			If this option is set, the <BBCodeTag::set_disallowed_children> option
			will automatically be disabled.
	*/
	public function set_allowed_children($allowed)
	{
		if(is_array($allowed))
		{
			$this->allowed_children = array();

			foreach($allowed as $tag_name)
			{
				$tag_name = trim($tag_name);

				if(preg_match('~^([a-z0-9_-])+$~i', $tag_name) > 0)
				{
					$this->allowed_children[] = $this->strtolower($tag_name);
				}
			}

			// Now disable the disallowed children option.
			$this->set_disallowed_children(false);
		}
		else
		{
			$this->allowed_children = array();
		}
	}

	/*
		Method: set_disable_formatting

		Sets what type of formatting should be disabled, such as automatically
		linking URL's and replacing smiley codes with images.

		Parameters:
			int $options - The type of formatting to disable, see the notes for
										 more information.

		Returns:
			void - Nothing is returned by this method.

		Note:
			The following options are accepted, and can be combined:
				SPEEDYOPT_DISAUTOLINK - Disables automatically linking URL's.
				SPEEDYOPT_DISSMILEYS - Disables automatically replacing smiley codes
															 (such as :)) with images.
	*/
	public function set_disable_formatting($options)
	{
		$this->disable_formatting = 0;

		// Disable automatic linking?
		if($options & SPEEDYOPT_DISAUTOLINK)
		{
			$this->disable_formatting |= SPEEDYOPT_DISAUTOLINK;
		}

		// How about smileys? Want to kill those too?!
		if($options & SPEEDYOPT_DISSMILEYS)
		{
			// Geez, what a party pooper you are!
			$this->disable_formatting |= SPEEDYOPT_DISSMILEYS;
		}
	}

	/*
		Method: set_replacements

		Sets an array containing values which need replacing in the after part
		of the defined BBCode tag.

		Parameters:
			array $replacements

		Returns:
			void - Nothing is returned by this method.

		Note:
			Don't understand what this method is for? Good... Well, that's not
			bad, I mean. This is something used by the BBCode class itself.
	*/
	public function set_replacements($replacements)
	{
		if(is_array($replacements))
		{
			$this->replacements = $replacements;
		}
	}

	/*
		The following are all accessors to their respective attributes.
	*/
	public function name()
	{
		return $this->name;
	}

	public function type()
	{
		return $this->type;
	}

	public function value()
	{
		return $this->value;
	}

	public function attributes()
	{
		return $this->attributes;
	}

	public function parse_content()
	{
		return $this->parse_content;
	}

	public function callback()
	{
		return !$this->is_empty() ? $this->callback : null;
	}

	public function before()
	{
		return $this->before;
	}

	public function after()
	{
		return $this->after;
	}

	public function required_parents()
	{
		return $this->required_parents;
	}

	public function required_children()
	{
		return $this->required_children;
	}

	public function block_level()
	{
		return $this->block_level;
	}

	public function disallowed_children()
	{
		return $this->disallowed_children;
	}

	public function allowed_children()
	{
		return $this->allowed_children;
	}

	public function disable_formatting($option = null)
	{
		return $option === null ? $this->disable_formatting : $this->disable_formatting & $option;
	}

	public function replacements()
	{
		return $this->replacements;
	}

	/*
		Method: is_empty

		Whether the tag is an 'empty' tag, meaning there is no closing tag.

		Parameters:
			none

		Returns:
			bool - Returns true if the tag is an empty tag, false if not.
	*/
	public function is_empty()
	{
		return substr($this->type(), 0, 6) == 'empty-';
	}

	/*
		Method: is_valid

		Whether this instance defines a valid and functional BBCode tag.

		Parameters:
			none

		Returns:
			bool - Returns true if the BBCode tag is functional, false if not.
	*/
	public function is_valid()
	{
		// A name for the tag is required, along with the type of tag.
		if(strlen($this->name()) == 0 || strlen($this->type()) == 0)
		{
			return false;
		}

		// Hmm, I guess this is the only other things we need to check...
		if(substr($this->type(), -9, 9) == 'attribute')
		{
			return count($this->attributes()) > 0;
		}
		else
		{
			return true;
		}
	}

	/*
		Method: strlen
	*/
	protected function strlen($str)
	{
		return function_exists('mb_strlen') ? mb_strlen($str) : strlen($str);
	}

	/*
		Method: strtolower
	*/
	protected function strtolower($str)
	{
		return function_exists('mb_strtolower') ? mb_strtolower($str) : strtolower($str);
	}
}

/*
	Class: Node

	This class is used to contain a piece of the parsed BBCode message,
	which could be just text or a BBCode tag.
*/
class Node
{
	// Variable: text
	// Contains the text of the Node.
	protected $text;

	// Variable: type
	// The type of node being contained within this Node instance.
	protected $type;

	// Variable: level
	// Contains the level at which the node resides within the parsed message.
	protected $level;

	// Variable: parentNode
	// An object containing the parent node of the current node.
	protected $parentNode;

	// Variable: childNodes
	// An array containing the child nodes of the current node.
	protected $childNodes;

	// Variable: childNodesLength
	// An integer containing the total number of child nodes.
	protected $childNodesLength;

	public function __construct()
	{
		$this->text = null;
		$this->type = null;
		$this->level = null;
		$this->parentNode = null;
		$this->childNodes(false);
	}

	/*
		Method: text

		Returns the text of the current node.

		Parameters:
			none

		Returns:
			string - Returns the text contained within the current node.
	*/
	public function text()
	{
		return $this->text;
	}

	/*
		Method: type

		Returns the type of node, such as text or tag.

		Parameters:
			none

		Returns:
			string - Returns a string containing the type of node.
	*/
	public function type()
	{
		return $this->type;
	}

	/*
    Method: isText

    Returns whether the node is a text node.

    Parameters:
      none

     Returns:
      bool - Returns true if the node is a text node.
  */
	public function isText()
	{
		return $this->type() == 'text';
	}

	/*
		Method: isTag

		Returns whether the node is a tag node.

		Parameters:
			none

		Returns:
			bool - Returns true if the node is a tag node.
	*/
	public function isTag()
	{
		return $this->type() == 'tag';
	}

	/*
		Method: appendText

		Appends a string to the existing text.

		Parameters:
			string $text - The text to append.

		Returns:
			void - Nothing is returned by this method.
	*/
	public function appendText($text)
	{
		$this->text .= $text;
	}

	/*
		Method: setText

		Sets the text of the current node.

		Parameters:
			string $text - The text.

		Returns:
			void - Nothing is returned by this method.
	*/
	public function setText($text)
	{
		$this->text = $text;
	}

	/*
		Method: setType

		Sets the type of the node, such as text or tag.

		Parameters:
			string $type - The type of the node.

		Returns:
			void - Nothing is returned by method.
	*/
	public function setType($type)
	{
		$this->type = $type;
	}

	/*
		Method: level

		Returns the level at which the node resides within the parsed message.

		Parameters:
			none

		Returns:
			int - Returns the level at which the node resides.
	*/
	public function level()
	{
		return $this->level;
	}

	/*
		Method: setLevel

		Sets the level at which the node resides within the parsed message.

		Parameters:
			int $level - The level at which to set the node at.

		Returns:
			void - Nothing is returned by this method.
	*/
	public function setLevel($level)
	{
		if((string)$level != (string)(int)$level || (int)$level < 0)
		{
			return;
		}

		$this->level = (int)$level;
	}

	/*
		Method: parentNode

		Gets or sets the parent node of the current node.

		Parameters:
			mixed $parentNode - Another node, which is the parent node of the
													current node.

		Returns:
			mixed - Returns the parent node of the current node if $parentNode is
							empty, but void otherwise.
	*/
	public function parentNode($parentNode = null)
	{
		if(is_object($parentNode) && is_a($parentNode, 'Node'))
		{
			$this->parentNode = $parentNode;
		}
		else
		{
			return $this->parentNode;
		}
	}

	/*
		Method: childNodes

		Gets, sets, or adds child nodes of the current node.

		Parameters:
			mixed $childNode - Either an array of child nodes, or a child node to
												 add along with the list of current child nodes.

		Returns:
			mixed - Returns an array of child nodes if $childNode is empty,
							otherwise nothing is returned by this method.
	*/
	public function childNodes($childNode = null)
	{
		if($childNode === false)
		{
			$this->childNodes = array();
			$this->childNodesLength = 0;
		}
		elseif(is_array($childNodes))
		{
			$this->childNodes(false);

			foreach($childNodes as $childNode)
			{
				if(is_object($childNode))
				{
					$this->childNodes($childNode);
				}
			}
		}
		elseif(is_object($childNode) && is_a($childNode, 'Node'))
		{
			$this->childNodes[] = $childNode;
			$this->childNodesLength++;
		}
		else
		{
			return $this->childNodes;
		}
	}

	/*
		Method: childNodesLength

		Returns the total number of immediate children this node contains.

		Parameters:
			none

		Returns:
			int - Returns an integer containing the number of immediate children
						this node contains.
	*/
	public function childNodesLength()
	{
		return $this->childNodesLength;
	}

	/*
		Method: strlen

		This is a protected method used to check the length of a string, which
		will use a multi-byte safe function if available.
	*/
	protected function strlen($str)
	{
		return function_exists('mb_strlen') ? mb_strlen($str) : strlen($str);
	}

	/*
		Method: substr

		This is a protected method used to fetch the specified portion of a
		string, which will use a multi-byte safe function if available.
	*/
	protected function substr($str, $start, $length = null)
	{
		return $length === null ? (function_exists('mb_substr') ? mb_substr($str, $start) : substr($str, $start)) : (function_exists('mb_substr') ? mb_substr($str, $start, $length) : substr($str, $start, $length));
	}

	/*
		Method: strpos

		This is a protected method used to find the position of the specified
		string inside another string, which will use a multi-byte safe function
		if available.
	*/
	protected function strpos($haystack, $needle, $start = null)
	{
		return function_exists('mb_strpos') ? ($start !== null ? mb_strpos($haystack, $needle, $start) : mb_strpos($haystack, $needle)) : ($start !== null ? strpos($haystack, $needle, $start) : strpos($haystack, $needle));
	}

	/*
		Method: strtolower

		This is a protected method used to lowercase a string, which will use a
		multi-byte safe function if available.
	*/
	protected function strtolower($str)
	{
		return function_exists('mb_strtolower') ? mb_strtolower($str) : strtolower($str);
	}
}

/*
	Class: TextNode

	This class inherits the Node class, and is specifically designed (ha) for
	containing just text.
*/
class TextNode extends Node
{
	/*
		Constructor: __construct

		Parameters:
			string $text - The text.
	*/
	public function __construct($text = '', $level = 0)
	{
		$this->setText($text);
		$this->setLevel($level);
		$this->type = 'text';
	}
}

/*
	Class: TagNode

	A class which inherits the Node class designed specifically for containing
	a BBCode tag.
*/
class TagNode extends Node
{
	// Variable: tag
	// The BBCode tag, such as url in [url=...]
	protected $tag;

	// Variable: tagType
	// The type of tag the node contains, such as basic, value, or attribute.
	protected $tagType;

	// Variable: value
	// The value of the tag the node contains, if it is a value type tag.
	protected $value;

	// Variable: attributes
	// The attributes of the tag the node contains, if it is of such a type.
	protected $attributes;

	// Variable: is_closing
	// Whether the tag contained is a closing tag, i.e. [/b]
	protected $is_closing;

	// Variable: ignore
	// Whether the node should be ignored when being handled by the
	// interpreter, which is likely due to the tag never being opened.
	protected $ignore;

	// Variable: closingAt
	// Indicates the location within the parsed structure that contains the
	// closing tag for this opening tag, if it is an opening tag, of course.
	protected $closingAt;

	// Variable: required
	// An array containing the require parent and child tags.
	protected $required;

	// Variable: checked
	// A boolean value indicating whether the
	protected $checked;

	/*
		Constructor: __construct

		Parameters:
			string $text - The entire string containing the tag, including the
										 square brackets.
			bool $is_closing - Whether the tag is closing, i.e. [/b]
	*/
	public function __construct($text = '', $is_closing = false, $level = 0, $speedy = null)
	{
		// All of this will be fetched and stored once the proper methods are
		// called. We won't call these now in case this tag is never encountered
		// in the message being parsed.
		$this->tag = null;
		$this->tagType = null;
		$this->value = null;
		$this->attributes = null;
		$this->setText($text);
		$this->type = 'tag';
		$this->setIsClosing($is_closing);
		$this->ignore = false;
		$this->closingAt = false;
		$this->prevNode = null;
		$this->nextNode = null;
		$this->required = array(
												'parents' => is_object($speedy) ? $speedy->required_parents($this->getTag()) : array(),
												'children' => is_object($speedy) ? $speedy->required_children($this->getTag()) : array(),
											);
		$this->checked = false;
		$this->setLevel($level);
	}

	/*
		Method: setIsClosing

		Sets whether the tag is a closing tag.

		Parameters:
			bool $is_closing - Whether the tag is a closing tag.

		Returns:
			void - Nothing is returned by this method.
	*/
	public function setIsClosing($is_closing = false)
	{
		$this->is_closing = !empty($is_closing);
	}

	/*
		Method: is_closing

		Whether the tag is a closing tag, i.e. [/b]

		Parameters:
			none

		Returns:
			bool - Returns true if the tag is closing, false if not.
	*/
	public function is_closing()
	{
		return $this->is_closing;
	}

	/*
		Method: getTag

		Gets the name of the current tag.

		Parameters:
			none

		Returns:
			string - Returns the name of the current tag.
	*/
	public function getTag()
	{
		// Do we already know?
		if($this->tag !== null)
		{
			return $this->tag;
		}

		// This could be a closing tag, in which case...
		if($this->is_closing())
		{
			// It is pretty easy to do.
			$this->tag = $this->substr(trim($this->text), 2);
			$this->tag = $this->strtolower($this->substr($this->tag, 0, $this->strlen($this->tag) - 1));
		}
		else
		{
			// It's a bit different to get the tag name from another type.
			// To get it, we will go to the position of whichever is closer:
			// a space, an equals sign, or a square bracket.
			$space = $this->strpos($this->text, ' ', 1);
			$equals = $this->strpos($this->text, '=', 1);
			$bracket = $this->strpos($this->text, ']', 1);

			$max = max($space === false ? -1 : $space, $equals === false ? -1 : $equals, $bracket === false ? -1 : $bracket);

			// Then we will remove the crud we don't want and lowercase it!
			$this->tag = $this->strtolower(trim(substr($this->text, 1, min($space === false ? $max + 1 : $space, $equals === false ? $max + 1 : $equals, $bracket === false ? $max + 1 : $bracket) - 1)));
		}

		return $this->tag;
	}

	/*
		Method: getTagType

		Gets the type of the current tag.

		Parameters:
			none

		Returns:
			string - Returns the type of the current tag, which could be: basic,
							 value, attribute or closing.
	*/
	public function getTagType()
	{
		// We don't want to keep doing this... So let's see if we already
		// checked it out.
		if($this->tagType !== null)
		{
			return $this->tagType;
		}

		// Closing? Then it's closing...
		if($this->is_closing())
		{
			$this->tagType = 'closing';
		}
		else
		{
			// We'll find this useful a couple of times.
			$nospace_text = $this->strtolower(trim(str_replace(' ', '', $this->text())));

			// It could be a basic tag.
			if($nospace_text == '['. $this->getTag(). ']')
			{
				$this->tagType = 'basic';
			}
			// Maybe a value tag...
			elseif(substr($nospace_text, 1, strlen($this->getTag()) + 1) == $this->getTag(). '=')
			{
				$this->tagType =  'value';
			}
			else
			{
				$this->tagType =  'attribute';
			}
		}

		return $this->tagType;
	}

	/*
		Method: getAttributes

		Gets all the attributes specified in the current tag.

		Parameters:
			none

		Returns:
			array - Returns an array containing all the attributes in the tag, but
							null if the tag is not an attribute tag.
	*/
	public function getAttributes()
	{
		if($this->getTagType() == 'attribute')
		{
			if($this->attributes !== null)
			{
				return $this->attributes;
			}

			// Just append one space... This will come in handy.
			$attributes = trim($this->substr($this->text(), $this->strlen($this->getTag()) + 1, -1)). ' ';

			// Set up a few useful variables.
			$pos = 0;
			$length = $this->strlen($attributes);
			$this->attributes = array();
			while($pos < $length)
			{
				// Keep going until we find no more space.
				$char = $this->substr($attributes, $pos, 1);

				// So, space?
				if($char != ' ')
				{
					// Nope!
					// Go on until we find a space or equal sign.
					$equals = $this->strpos($attributes, '=', $pos);
					$space = $this->strpos($attributes, ' ', $pos);

					// This should be the attribute's name.
					$attr_name = $this->strtolower($this->substr($attributes, $pos, ($equals === false || $equals > $space ? $space : $equals) - $pos));

					// There should be nothing between the equals sign, if there is one.
					if($equals === false || ($equals > $space && $this->strlen(trim($this->substr($attributes, $space, $equals - $space))) > 0))
					{
						// Looks like there is something else there! So this is just a
						// lonesome attribute name.
						$this->attributes[$attr_name] = true;

						$pos = $space + 1;

						// Now, we don't want to continue.
						continue;
					}

					// Let's see... We want to find the value, which we want to find by
					// going until we find anything but space.
					$pos = $equals + 1;
					while($pos < $length && $this->substr($attributes, $pos, 1) == ' ')
					{
						$pos++;
					}

					// Alright, so the position we found should not be out-of-bounds.
					if($pos >= $length)
					{
						// Woopsie!
						$this->attributes[$attr_name] = true;

						break;
					}
					// It could be a quote of some sort.
					elseif(($delimiter = $this->substr($attributes, $pos, 6)) == '&quot;' || $delimiter == '&#039;')
					{
						// Let's not forget where we came from, that's never a good idea.
						$start_pos = $pos;

						// Oh, and move passed the starting delimiter... Duh!
						$pos++;

						// This is also pretty simple, but not as simple as compared to the
						// one below. But oh well! Such is life on the farm!
						while($pos < $length)
						{
							// Try to find the delimiter, somewhere!
							$pos = $this->strpos($attributes, $delimiter, $pos);

							// Did we find it?
							if($pos === false)
							{
								// Uh, oh!
								break;
							}
							// There shouldn't be a a backslash escaping it.
							elseif($this->substr($attributes, $pos - 1, 1) == '\\')
							{
								// I guess this won't be the right place to terminate the value,
								// huh?
								$pos += 2;

								// ... and so the search goes on!
								continue;
							}

							// Looks like we found it, in which case we just quit.
							break;
						}

						// Did we come to an end?
						if($pos === false)
						{
							// In which case, we will do this:
							$this->attributes[$attr_name] = rtrim($this->substr($attributes, $start_pos + 6));

							// Let's get out of this loop, shall we?
							$pos = $length;
						}
						else
						{
							// Just fetch the value between the delimiter, and remove any
							// backslashes escaping a the delimiter in the value.
							$this->attributes[$attr_name] = str_replace('\\'. $delimiter, $delimiter, $this->substr($attributes, $start_pos + 6, $pos - $start_pos - 6));

							// Just move on, a little bit.
							$pos += 6;
						}
					}
					else
					{
						// This is really simple... Thankfully! We will just find the next
						// space, which means the value is over.
						$space = $this->strpos($attributes, ' ', $pos + 1);

						$this->attributes[$attr_name] = trim($this->substr($attributes, $pos, $space - $pos));

						// Now set the position we want to start looking at next time. Which
						// is the space we just found.
						$pos = $space + 1;
					}
				}
				else
				{
					// Yup, a space, so go on.
					$pos++;
				}
			}

			return $this->attributes;
		}
		else
		{
			return null;
		}
	}

	/*
		Method: getAttribute

		Gets the specified attribute from the current tag.

		Parameters:
			none

		Returns:
			mixed - Returns the value of the attribute, false if the attribute
							does not exist, and null if the tag is not an attribute tag.
	*/
	public function getAttribute($name)
	{
		if($this->getTagType() == 'attribute')
		{
			// We call on the getAttributes method to, well, get the attributes!
			$attributes = $this->getAttributes();

			// Make sure the attribute even exists...
			return isset($attributes[$this->strtolower($name)]) ? $attributes[$this->strtolower($name)] : false;
		}
		else
		{
			return null;
		}
	}

	/*
		Method: getValue

		Attempts to retrieve the value specified in the current tag, which is
		the value appearing after the equals sign.

		Parameters:
			none

		Returns:
			string - Returns a string containing the value of the tag, and null if
							 the tag is not a value tag.
	*/
	public function getValue()
	{
		// If this isn't a value tag it won't have a value... How surprising!
		if($this->getTagType() == 'value')
		{
			// Did we already get the value? It shouldn't change... ;-)
			if($this->value !== null)
			{
				return $this->value;
			}

			// Let's get passed the equals sign.
			$this->value = trim($this->substr($this->text(), strpos($this->text(), '=') + 1, -1));

			// The value may be surrounded by quotes.
			if(in_array($first_delimiter = $this->substr($this->value, 0, 6), array('&quot;', '&#039;')) && $this->substr($this->value, -6, 6) == $first_delimiter)
			{
				// Let's remove that stuff... Don't quite understand the point if it
				// is this type of tag, but whatever!
				$this->value = substr($this->value, 6, -6);
				$this->value = str_replace('\\'. $first_delimiter, $first_delimiter, $this->value);
			}

			// That's it...
			return $this->value;
		}
		else
		{
			return null;
		}
	}

	/*
		Method: ignore

		Whether the tag should be ignored when the entire structure is being
		parsed by the interpreter.

		Parameters:
			none

		Returns:
			bool - Returns true if the tag should be ignored, false if not.
	*/
	public function ignore()
	{
		return $this->ignore;
	}

	/*
		Method: setIgnore

		Sets whether the tag should be ignored when the entire structure is
		being parsed.

		Parameters:
			bool $ignore - Whether the tag should be ignored.

		Returns:
			void - Nothing is returned by this method.
	*/
	public function setIgnore($ignore)
	{
		$prev_ignore = $this->ignore;
		$this->ignore = !empty($ignore);

		// Do we need to signal to any other nodes that this one has been
		// ignored?
		if($this->ignore && $prev_ignore !== true)
		{
			if($this->prevNode !== null)
			{
				$this->prevNode->checkConstraints();

				// If the previous node depends on the next node (which would be the
				// instance we're in now), then it cannot possibly be valid.
				if($this->prevNode->dependsOnNext())
				{
					$this->prevNode->setIgnore(true);
				}
			}

			if($this->nextNode !== null)
			{
				$this->nextNode->checkConstraints();

				if($this->nextNode->dependsOnPrev())
				{
					$this->nextNode->setIgnore(true);
				}
			}
		}
	}

	/*
		Method: closingAt

		Returns the location within the parsed structure containing this tags
		closing tag, if it is an opening tag.

		Parameters:
			none

		Returns:
			mixed - Returns an integer containing the index of the closing tag or
							false if this tag has no closing tag (i.e. this is a closing
							tag).
	*/
	public function closingAt()
	{
		return $this->closingAt;
	}

	/*
		Method: setClosingAt

		Sets the index of the closing tag for this tag, if it is an opening tag.

		Parameters:
			int $index - The index of closing tag in the structure.

		Returns:
			bool - Returns true on success, or false on failure, which would mean
						 that this node is a closing tag.
	*/
	public function setClosingAt($index)
	{
		if($this->is_closing() || (int)$index < 0)
		{
			return false;
		}

		// Just set it, and that'll be all!
		$this->closingAt = (int)$index;

		return true;
	}

	/*
		Method: prevNode

		Gets or sets the previous tag node in relation to this node.

		Parameters:
			object $prevNode - The previous tag node.

		Returns:
			mixed - Returns the previous tag node if there is one, but null if
							there isn't any. If $prevNode is supplied, nothing is returned
							by this method.
	*/
	public function prevNode($prevNode = null)
	{
		if($prevNode !== null && is_object($prevNode))
		{
			$this->prevNode = $prevNode;
		}
		else
		{
			return $this->prevNode;
		}
	}

	/*
		Method: nextNode

		Gets or sets the next tag node in relation to this node.

		Parameters:
			object $nextNode - The next tag node.

		Returns:
			mixed - Returns the next tag node if there is one, but null if there
							isn't any. If $nextNode is supplied, nothing is returned by
							this method.
	*/
	public function nextNode($nextNode = null)
	{
		if($nextNode !== null && is_object($nextNode))
		{
			$this->nextNode = $nextNode;
		}
		else
		{
			return $this->nextNode;
		}
	}

	public function checkConstraints()
	{
		// If this has been checked already, then we don't need to do it again.
		if($this->checked || $this->is_closing())
		{
			return;
		}

		// We will check them right now!
		$this->checked = true;

		// Any required parents or children?
		if(count($this->required['parents']) > 0)
		{
			// Yup, so let's take a look.
			if($this->prevNode === null || !in_array($this->prevNode->getTag(), $this->required['parents']))
			{
				// Uh oh! Looks like this won't work. So we will ignore this tag.
				// When we mark this tag as ignored, it will signal that to any
				// respective tags, which they will in turn do the same.
				$this->setIgnore(true);

				// There will be no point in checking child constraints, if any.
				return;
			}
		}

		if(count($this->required['children']) > 0)
		{
			// Check the next node.
			if($this->nextNode === null || !in_array($this->nextNode->getTag(), $this->required['children']))
			{
				// ... and ignore!
				$this->setIgnore(true);
			}
		}
	}

	public function dependsOnNext()
	{
		return count($this->required['children']) > 0;
	}

	public function dependsOnPrev()
	{
		return count($this->required['parents']) > 0;
	}
}
?>