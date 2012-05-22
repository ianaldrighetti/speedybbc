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

// PHP doesn't allow class constants to be set equal to a function value, so
// we will need to go ahead and set that to a global constant.
define('SPDY_MB_EXISTS', function_exists('mb_internal_encoding'));

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
	// An array containing every BBCode tag that can be handled by the
	// instance of SpeedyBBC.
	private $tags;

	// Variable: constraints
	// An array containing the parent/child constraints of a tag.
	private $constraints;

	/*
		Constructor: __construct

		Parameters:
			bool $enable_default_tags - Whether to enable the default BBCode tags
																	that come with SpeedyBBC. Defaults to
																	true.
			string $encoding - The character encoding to use when handling
												 strings, which defaults to UTF-8. Please note that
												 this option is only used if the multi-byte
												 extension in PHP is enabled (<www.php.net/mb>).
	*/
	public function __construct($enable_default_tags = true, $encoding = 'UTF-8')
	{
		$this->tags = array();
		$this->constraints = array();

		// Let's see if we can set the default encoding for the multi-byte
		// extension in PHP.
		if(!empty($encoding) && SPDY_MB_EXISTS)
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
									'parseContent' => false,
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

																							 $supported = array(\'arial\', \'helvetica\', \'arial black\', \'comic sans ms\', \'courier new\', \'impact\', \'lucida console\', \'monaco\', \'tahoma\', \'geneva\', \'times new roman\', \'trebuchet ms\', \'verdana\', \'symbol\', \'georgia\');
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
																		return (substr($content, 0, 4) == \'www.\' ? \'http://\' : \'\'). $content;
																	}
																	else
																	{
																		return false;
																	}'),
									'before' => '<a href="[content]" target="_blank">',
									'after' => '</a>',
									'parseContent' => false,
									'disallowedChildren' => array('url', 'iurl', 'email'),
								),
								array(
									'name' => 'url',
									'type' => 'value',
									'value' => array(
															 'regex' => '~(?:((?:http|ftp)(?:s)?://|www\.)(?:[\w+?\.\w+])+(?:[a-zA-Z0-9\~\!\@\#\$\%\^\&amp;\*\(\)_\-\=\+\\\/\?\.\:\;\'\,]*)?)~i',
															 'callback' => create_function('$value', '
																							 return (substr($value, 0, 4) == \'www.\' ? \'http://\' : \'\'). $value;'),
														 ),
									'before' => '<a href="{value}" target="_blank">',
									'after' => '</a>',
									'disallowedChildren' => array('url', 'iurl', 'email'),
								),
								array(
									'name' => 'iurl',
									'type' => 'basic',
									'callback' => create_function('$content, $dummy', '
																	if(preg_match(\'~(?:((?:http|ftp)(?:s)?://|www\.)(?:[\w+?\.\w+])+(?:[a-zA-Z0-9\~\!\@\#\$\%\^\&amp;\*\(\)_\-\=\+\\\/\?\.\:\;\\\'\,]*)?)~i\', $content))
																	{
																		return \'<a href="\'. (substr($content, 0, 4) == \'www.\' ? \'http://\' : \'\'). $content. \'>\'. $content. \'</a>\';
																	}
																	else
																	{
																		return false;
																	}'),
									'before' => '',
									'after' => '',
									'parseContent' => false,
									'disallowedChildren' => array('url', 'iurl', 'email'),
								),
								/* allow mailto: in above!!! */
								array(
									'name' => 'iurl',
									'type' => 'value',
									'value' => array(
															 'regex' => '~(?:((?:http|ftp)(?:s)?://|www\.)(?:[\w+?\.\w+])+(?:[a-zA-Z0-9\~\!\@\#\$\%\^\&amp;\*\(\)_\-\=\+\\\/\?\.\:\;\'\,]*)?)~i',
															 'callback' => create_function('$value', '
																							 return (substr($value, 0, 4) == \'www.\' ? \'http://\' : \'\'). $value;'),
														 ),
									'before' => '<a href="{value}">',
									'after' => '</a>',
									'disallowedChildren' => array('url', 'iurl', 'email'),
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
									'parseContent' => false,
									'disallowedChildren' => array('url', 'iurl', 'email'),
								),
								array(
									'name' => 'email',
									'type' => 'value',
									'value' => array(
															 'regex' => '~(?:[\w-]+(?:\.[\w-]+)*@(?:[a-z0-9-]+(?:\.[a-z0-9-]+)*?\.[a-z]{2,6}|(?:\d{1,3}\.){3}\d{1,3})(?::\d{4})?)~i',
														 ),
									'before' => '<a href="mailto:{value}" target="_blank">',
									'after' => '</a>',
									'disallowedChildren' => array('url', 'iurl', 'email'),
								),
								/* Images */
								array(
									'name' => 'img',
									'type' => 'basic',
									'callback' => create_function('$content, $dummy', '
																	if(preg_match(\'~(?:((?:http)(?:s)?://|www\.)(?:[\w+?\.\w+])+(?:[a-zA-Z0-9\~\!\@\#\$\%\^\&amp;\*\(\)_\-\=\+\\\/\?\.\:\;\\\'\,]*)?)~i\', $content))
																	{
																		return (substr($content, 0, 4) == \'www.\' ? \'http://\' : \'\'). $content;
																	}
																	else
																	{
																		return false;
																	}'),
									'before' => '<img src="',
									'after' => '" alt="" />',
									'parseContent' => false,
								),
								array(
									'name' => 'img',
									'type' => 'attribute',
									'attributes' => array(
																		'width' => array(
																								 'regex' => '~\d+~',
																								 'optional' => true,
																								 'replace' => '',
																							 ),
																		'height' => array(
																									'regex' => '~\d+~',
																									'optional' => true,
																									'replace' => '',
																								),
																	),
									'callback' => create_function('$content, $data', '
																	if(preg_match(\'~(?:((?:http)(?:s)?://|www\.)(?:[\w+?\.\w+])+(?:[a-zA-Z0-9\~\!\@\#\$\%\^\&amp;\*\(\)_\-\=\+\\\/\?\.\:\;\\\'\,]*)?)~i\', $content))
																	{
																		return \'<img src="\'. (substr($content, 0, 4) == \'www.\' ? \'http://\' : \'\'). $content. \'"\'. (isset($data[\'width\']) ? \' width="\'. $data[\'width\']. \'"\' : \'\'). (isset($data[\'height\']) ? \' height="\'. $data[\'height\']. \'"\' : \'\'). \' alt="" />\';
																	}
																	else
																	{
																		return false;
																	}'),
									'before' => '',
									'after' => '',
									'parseContent' => false,
								),
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
									'blockLevel' => true,
									'requiredChildren' => array('li'),
								),
								array(
									'name' => 'olist',
									'type' => 'basic',
									'before' => '<ol>',
									'after' => '</ol>',
									'blockLevel' => true,
									'requiredChildren' => array('li'),
								),
								array(
									'name' => 'li',
									'type' => 'basic',
									'before' => '<li>',
									'after' => '</li>',
									'blockLevel' => true,
									'requiredParents' => array('list', 'olist'),
								),
								/* Tables */
								array(
									'name' => 'columns',
									'type' => 'basic',
									'before' => '<table><tr><td>',
									'after' => '</td></tr></table>',
									'blockLevel' => true,
								),
								array(
									'name' => 'next',
									'type' => 'empty-basic',
									'before' => '</td><td>',
									'after' => '',
									'blockLevel' => true,
									'requiredParents' => array('columns'),
								),
								array(
									'name' => 'table',
									'type' => 'basic',
									'before' => '<table>',
									'after' => '</table>',
									'blockLevel' => true,
									'requiredChildren' => array('tr'),
								),
								array(
									'name' => 'tr',
									'type' => 'basic',
									'before' => '<tr>',
									'after' => '</tr>',
									'blockLevel' => true,
									'requiredParents' => array('table'),
									'requiredChildren' => array('td'),
								),
								array(
									'name' => 'td',
									'type' => 'basic',
									'before' => '<td>',
									'after' => '</td>',
									'blockLevel' => true,
									'requiredParents' => array('tr'),
								),
								array(
									'name' => 'td',
									'type' => 'attribute',
									'attributes' => array(
																		'colspan' => array(
																									 'regex' => '~\d+~',
																									 'replace' => ' colspan="[value]"',
																									 'optional' => true,
																								 ),
																		'width' => array(
																								 'regex' => '~\d+~',
																								 'replace' => ' width="[value]"',
																								 'optional' => true,
																							 ),
																	),
									'before' => '<td{colspan}{width}>',
									'after' => '</td>',
									'blockLevel' => true,
									'requiredParents' => array('tr'),
								),
							);

			// Let's add the tags.
			foreach($tags as $tag)
			{
				if(!$this->addTag(new SpeedyTag($tag)))
				{
					echo $tag['name'], '<br />';
				}
			}
		}
	}

	/*
		Method: addTag

		Adds a BBCode tag to be supported when parsing a message.

		Parameters:
			object $tag - An instance of <SpeedyTag>.

		Returns:
			bool - Returns true if the BBCode tag was added, false if not (such as
						 if the same BBCode tag is already defined, if it isn't a valid
						 <SpeedyTag>, or something along those lines).
	*/
	public function addTag($tag)
	{
		// We require that you define a BBCode tag through the SpeedyTag class.
		if(!is_a($tag, 'SpeedyTag') || !$tag->isValid())
		{
			return false;
		}

		// There is a bit of a requirement... We will require that when one tag
		// name is an empty tag, all other variations of that tag with the same
		// name must also be an empty tag as well.
		if(array_key_exists($tag->name(), $this->tags) && ((!empty($this->tags[$tag->name()]['isEmpty']) && !$tag->isEmpty()) || (empty($this->tags[$tag->name()]['isEmpty']) && $tag->isEmpty())))
		{
			// Sorry, but that won't work.
			return false;
		}

		// Now we can go ahead and add the tag, so long as no other identical
		// tag exists.
		if(!array_key_exists($tag->name(), $this->tags) || $this->tags[$tag->name()]['count'] < 1)
		{
			$this->tags[$tag->name()] = array(
																		'tags' => array($tag),
																		'count' => 1,
																		'isEmpty' => $tag->isEmpty(),
																	);
		}
		else
		{
			foreach($this->tags[$tag->name()]['tags'] as $defined_tag)
			{
				if($defined_tag == $tag)
				{
					// Sorry, but that tag already exists.
					return false;
				}
			}

			// There isn't another tag like this, so go ahead and add it.
			$this->tags[$tag->name()]['tags'][$this->tags[$tag->name()]['count']] = $tag;
			$this->tags[$tag->name()]['count']++;
		}

		// As noted in the SpeedyTag class, all tags with the same name must
		// have the same parent and child constraints. This otherwise checking
		// the constraints would be pretty complicated.
		if(count($tag->requiredParents()) > 0)
		{
			$this->constraints[$tag->name()]['parents'] = array_merge($tag->requiredParents(), isset($this->constraints[$tag->name()]['parents']) ? $this->constraints[$tag->name()]['parents'] : array());
		}

		if(count($tag->requiredChildren()) > 0)
		{
			$this->constraints[$tag->name()]['children'] = array_merge($tag->requiredChildren(), isset($this->constraints[$tag->name()]['children']) ? $this->constraints[$tag->name()]['children'] : array());
		}

		// Alright, the tag was added.
		return true;
	}

	/*
		Method: tagExists

		Determines whether there is a BBCode tag defined, whether by checking
		the name of the tag, or by seeing if the exact definition of a SpeedyTag
		exists.

		Parameters:
			mixed $tag - A string containing the tags name, or an instance of
									 <SpeedyTag>.

		Returns:
			bool - Returns true if the tag exists, false if not.
	*/
	public function tagExists($tag)
	{
		// Do you want to check by name?
		if(is_string($tag))
		{
			return !empty($this->tags[SpeedyBBC::strtolower($tag)]);
		}
		// Check to see if there is the possibility of $tag being within the
		// supported tags list, along with it being a SpeedyTag instance.
		elseif(!is_a($tag, 'SpeedyTag') || !$tag->isValid() || empty($this->tags[$tag->name()]['tags']))
		{
			return false;
		}
		else
		{
			// We will need to go through all of the tags.
			foreach($this->tags[$tag->name()]['tags'] as $defined_tag)
			{
				if($defined_tag == $tag)
				{
					return true;
				}
			}

			return false;
		}
	}

	/*
		Method: removeTag

		Removes the specified tag, whether it be removing all tags by name or
		by removing a single tag (through use of a <SpeedyTag>).

		Parameters:
			mixed $tag - A string containing the tag to remove or an instance of
									 <SpeedyTag>.

		Returns:
			mixed - If a string is specified an integer containing the number of
							tags removed will be returned, otherwise if a <SpeedyTag> is
							supplied then true will be returned if it was removed and
							false if the tag does not exist.
	*/
	public function removeTag($tag)
	{
		if(!is_string($tag) && (!is_a($tag, 'SpeedyTag') || !$tag->isValid()))
		{
			return false;
		}
		elseif(is_string($tag))
		{
			$tag = SpeedyBBC::strtolower($tag);

			// If there are no tags by that name, we can't really remove them, can
			// we?
			if(!array_key_exists($tag, $this->tags) || $this->tags[$tag]['count'] == 0)
			{
				return 0;
			}
			else
			{
				// First get the total number of tags we will be removing.
				$removed = $this->tags[$tag]['count'];

				// Now delete them all! Along with all their constraints as well.
				unset($this->tags[$tag], $this->constraints[$tag]);

				// ... then return the number removed.
				return $removed;
			}
		}
		else
		{
			foreach($this->tags[$tag->name()]['tags'] as $index => $defined_tag)
			{
				if($defined_tag == $tag)
				{
					// Delete the tag.
					unset($this->tags[$tag->name()]['tags'][$index]);

					// That means one less tag.
					$this->tags[$tag->name()]['count']--;

					// If there are now no tags with that name, we can go ahead and
					// delete everything.
					if($this->tags[$tag->name()]['count'] == 0)
					{
						unset($this->tags[$tag->name()], $this->constraints[$tag->name()]);
					}

					// Then go ahead and stop looking.
					return true;
				}
			}

			// If we're still going at this point, that means the tag didn't
			// exist, so we couldn't remove it.
			return false;
		}
	}

	/*
		Method: parse

		Parses the specified string and interprets any BBCode tags into HTML as
		described by all the supported BBCode tags.

		Parameters:
			string $message - The string to be parsed.
			bool $is_encoded - Whether the message has already been passed through
												 the <www.php.net/htmlspecialchars> function,
												 defaults to false.

		Returns:
			string - Returns a string containing the parsed message.

		Note:
			It is very important that the $is_encoded parameter is set correctly,
			because if the message is not encoded and the parameter is set to true
			then the message will not be parsed properly... Not only that, but
			passing the message through <www.php.net/htmlspecialchars> will remove
			XSS threats (unless of course a BBCode tag doesn't properly handle
			input).
	*/
	public function parse($message, $is_encoded = false)
	{
		// Message not yet encoded? Then we will go ahead and do that first.
		if(empty($is_encoded))
		{
			$message = SpeedyBBC::htmlspecialchars($message);
		}

		// We have a couple helper functions that will do the work for us.
		return $this->interpretStruct($this->toStruct($message));
	}

	/*
		Method: toStruct

		A private method which turns the supplied message into structured
		<SpeedyNode>'s. The highest most node is a <SpeedyNode> containing every
		child at the first level of the message (level 0), with each of those
		nodes being either a <SpeedyTextNode> (which will never have children)
		or a <SpeedyTagNode> which may contain more children, tags or text.

		Parameters:
			string $message - The message to parse into a structure.

		Returns:
			object - Returns a <SpeedyNode> containing the structured message.
	*/
	private function toStruct($message)
	{
		// Initialize a few useful things.
		// Such as the current position within the string.
		$cur_pos = 0;
		$prev_pos = 0;
		$length = SpeedyBBC::strlen($message);

		// This variable will always contain our current parent node.
		$parent = new SpeedyNode();

		// Keep track of the current level, along with the opened tags.
		$current_level = 0;
		$opened_tags = array();
		$opened_count = 0;

		// Even though we have the node's setup hierarchically, we also need a
		// linear representation as well.
		$struct = array();
		$struct_length = 0;

		// I guess we can get going! We are looking for square brackets within
		// the message, which is the sign of a possible BBCode tag.
		while(($pos = SpeedyBBC::strpos($message, '[', $cur_pos)) !== false && $pos + 1 < $length && SpeedyBBC::substr($message, $pos + 1, 1) != ' ')
		{
			// Before we handle the possible preceding text, why don't we make sure
			// that this tag will work?
			$last_pos = $pos;
			while($pos < $length)
			{
				unset($brk_pos);

				// Does the bracket come before the ampersand?
				// But the ampersand may not even be a quote as well!
				if(($amp_pos = SpeedyBBC::strpos($message, '&', $pos)) === false || ($brk_pos = SpeedyBBC::strpos($message, ']', $pos)) === false || $brk_pos < $amp_pos || !in_array($quote_type = SpeedyBBC::substr($message, $amp_pos, 6), array('&quot;', '&#039;')))
				{
					// Sweet!
					$pos = !isset($brk_pos) ? SpeedyBBC::strpos($message, ']', $pos) : $brk_pos;

					break;
				}

				// Now, can we find the next quote?
				while($amp_pos + 6 < $length && ($amp_pos = SpeedyBBC::strpos($message, $quote_type, $amp_pos + 6)) !== false && $message[$amp_pos - 1] == '\\');

				// Did our search come up with nothing?
				if($amp_pos + 6 >= $length || $amp_pos === false)
				{
					break;
				}

				$pos = $amp_pos + 6;
			}

			// So, did we find a valid tag?
			if($message[$pos] == ']')
			{
				// Yup, we sure did! So we will want to make a node to contain the
				// text preceding the tag, otherwise it will be forgotten...
				$saved = false;
				if($prev_pos != $last_pos)
				{
					$node = new SpeedyTextNode(SpeedyBBC::substr($message, $prev_pos, $last_pos - $prev_pos), $parent->level() + 1, $parent);

					// When we created the SpeedyTextNode, we set it's parent node,
					// but the parent node doesn't know of this child, so we will
					// need to go ahead and add this node to that list.
					$parent->addChildNode($node);

					// Add the node to the linear structure as well.
					$struct[$struct_length++] = $node;

					$saved = true;
				}

				// Now for the tag itself.
				$node = new SpeedyTagNode(SpeedyBBC::substr($message, $last_pos, $pos - $last_pos + 1), $parent->level() + 1, $parent);

				// Woah there, horsey! Do we even have a tag by that name?
				if($this->tagExists($node->tagName()))
				{
					// Yup, we do.
					// Maybe this tag is being opened?
					if(!$node->isClosing())
					{
						// Set the tag nodes current level, along with adding it to the
						// list of opened tags.
						$node->setLevel($parent->level() + 1);

						// Also give it the list of required tags.
						$node->setRequired(isset($this->constraints[$node->tagName()]['parents']) ? $this->constraints[$node->tagName()]['parents'] : array(), isset($this->constraints[$node->tagName()]['children']) ? $this->constraints[$node->tagName()]['children'] : array());

						$parent->addChildNode($node);

						// Add the node to the linear structure...
						$node->setPosition($struct_length);
						$struct[$struct_length++] = $node;

						// Before we add this to the list of opened tags, we will check
						// whether it is an empty tag, because if it is, it doesn't get
						// opened, so it won't need to be closed ;-)
						if(!$this->tagIsEmpty($node->tagName()))
						{
							$opened_tags[$opened_count++] = array(
																								'tagName' => $node->tagName(),
																								'level' => $parent->level() + 1,
																								'node' => $node,
																							);

							// Also, this node has now become our parent...
							$parent = $node;
						}
					}
					// Nope, it is being closed, and we have a bit of work to do!
					else
					{
						// First we need to see if this tag was ever opened.
						$stop = $opened_count;
						for($index = $opened_count - 1; $index >= 0; $index--)
						{
							if($opened_tags[$index]['tagName'] == $node->tagName())
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

							while($current > $stop)
							{
								// Take off the last tag...
								$opened_tag = $opened_tags[$current];
								unset($opened_tags[$current]);

								// Also subtract one from the total opened tag count.
								$opened_count--;

								// While the hierarchical representation of the message
								// doesn't actually contain any closing tags, the linear
								// representation does...
								$tNode = new SpeedyTagNode('[/'. $opened_tag['tagName']. ']', $opened_tag['level'], null);

								// Add it...
								$tNode->setPosition($struct_length);
								$struct[$struct_length++] = $tNode;

								// Now tell the opening node about it's closing node.
								$opened_tag['node']->setClosingNode($tNode);

								// Simply move up one.
								$parent = $parent->parentNode();

								// Move to the next.
								$current--;
							}

							// Just once more...
							$parent = $parent->parentNode();

							// Now remove it from the list of opened tags.
							$opened_tag = $opened_tags[--$opened_count];
							unset($opened_tags[$opened_count]);

							// Add the current node to the structure, along with telling
							// the opening tag about it's closing tag.
							$node->setPosition($struct_length);
							$struct[$struct_length++] = $node;
							$opened_tag['node']->setClosingNode($node);

							// Now set the proper level.
							$current_level = $node->level() - 1;
						}
						else
						{
							// We will just ignore this tag, then.
							$node->setIgnore(true);

							$node->setPosition($struct_length);
							$struct[$struct_length++] = $node;
						}
					}

					// Now, everything has been handled up to this point.
					$prev_pos = $pos + 1;
				}
				// We need to do a little something if the previous content was
				// saved but not the SpeedyTagNode itself.
				elseif(!empty($saved))
				{
					$node = new SpeedyTextNode($node->text(), $parent->level() + 1, $parent);
					$parent->addChildNode($node);

					$struct[$struct_length++] = $node;

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
			$node = new SpeedyTextNode(SpeedyBBC::substr($message, $prev_pos), $parent->level() + 1, $parent);
			$struct[$struct_length++] = $node;
		}

		// Were there any tags that weren't closed by the end of the message?
		// That's fine, we can fix that.
		if($opened_count > 0)
		{
			while(--$opened_count >= 0)
			{
				$opened_tag = $opened_tags[$opened_count];

				// Create a node to contain the closing tag.
				$tNode = new SpeedyTagNode('[/'. $opened_tag['tagName']. ']', $opened_tag['level'], null);

				// Add it to the linear structure.
				$tNode->setPosition($struct_length);
				$struct[$struct_length++] = $tNode;

				// Now tell the opening node about it's closing node.
				$opened_tag['node']->setClosingNode($tNode);

				$parent = $parent->parentNode();
			}
		}

		// Time to check constraints...
		$parent->checkConstraints();

		// We actually don't need the hierarchical view of the message anymore.
		return array($struct, $struct_length);
	}

	/*
		Method: tagIsEmpty

		Determines whether the specified tag name must be an empty tag.

		Parameters:
			none

		Returns:
			bool - Returns true if the tag name is an empty tag.
	*/
	private function tagIsEmpty($tagName)
	{
		return !empty($this->tags[$tagName]['isEmpty']);
	}

	/*
		Method: interpretStruct

		Interprets the passed structure information into an HTML based message.

		Parameters:
			array $structInfo - An array containing the parsed structure, along
													with the number of elements within the structure.

		Returns:
			string - Returns a string containing the final product of SpeedyBBC --
							 a message with it's BBCode tags translated into HTML.
	*/
	public function interpretStruct($structInfo)
	{
		// Extract the structure along with the length of that structure.
		list($struct, $struct_length) = $structInfo;

		// We need a string to contain the message we're building up.
		$message = '';

		// Then we need to keep track of our current location as we traverse the
		// structure of the parsed message.
		$pos = 0;

		// Finally, a place to keep track of all the opened tags. If you're
		// wondering why we need to when the toStruct method already took care
		// of any careless people (such as missing closing tags, or closing tags
		// which are misplaced, etc.), it is because we could not anticipate
		// whether the tag is a block level tag (at that time)... So we may need
		// to still close tags ourselves.
		$opened_tags = array();
		$opened_count = 0;

		// Let's get started!
		while($pos < $struct_length)
		{
			// Do we need to do anything special for this node? Is it a tag?
			// Also make sure it isn't ignored -- because if it is, we will treat
			// it as text.
			if($struct[$pos]->isTag() && !$struct[$pos]->ignore())
			{
				if(!$struct[$pos]->isClosing())
				{
					// It is time to look for all the tags which could be possible
					// matches for the current tag. Luckily we have another method
					// which will handle that.
					$found = false;

					// Let's get to work, and what work it will be :-/.
					$tag_content = null;
					$tag_handled = false;
					foreach($this->tags[$struct[$pos]->tagName()]['tags'] as $match)
					{
						if(($match->isEmpty() ? SpeedyBBC::substr($match->type(), 6) : $match->type()) != $struct[$pos]->tagType())
						{
							continue;
						}

						$data = null;

						// Let's see if there is any data which needs to be validated.
						// We need to handle each tag differently, depending upon the
						// type. First off: value ([name=...])!
						if($struct[$pos]->tagType() == 'value' || $struct[$pos]->tagType() == 'empty-value')
						{
							// Fetch the value of the tag so we can get started.
							$data = $struct[$pos]->value();

							// Now fetch the options for this match.
							$options = $match->value();
							$regex = isset($options['regex']) && SpeedyBBC::strlen($options['regex']) > 0 ? $options['regex'] : false;
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
						elseif($struct[$pos]->tagType() == 'attribute' || $struct[$pos]->tagType() == 'empty-attribute')
						{
							// Fetch all the set attributes.
							$data = $struct[$pos]->attributes();

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
								if(isset($options['regex']) && SpeedyBBC::strlen($options['regex']) > 0 && @preg_match($options['regex'], $data[$attr_name]) == 0)
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

						// Perhaps the match disallows (or only allows) certain tags?
						if($match->allowedChildren() !== null || $match->disallowedChildren() !== null)
						{
							// Not much for us to do here...
							$struct[$pos]->applyChildConstraints($match->allowedChildren() !== null ? $match->allowedChildren() : $match->disallowedChildren(), $match->allowedChildren() !== null);
						}

						// Thankfully, parent/child constraints have already been
						// checked! So we can get right to it!
						// We may need to gather up the content of the tag.
						if((is_callable($match->callback()) || $match->parseContent() === false) && $tag_content === null)
						{
							// Luckily there is a function to do this for us.
							$tag_content = array_slice($struct, $pos + 1, $struct[$pos]->closingNode()->position() - $pos - 1);
						}

						$tag_handled_content = '';
						if(is_callable($match->callback()) || $match->parseContent() === false)
						{
							// Does the tag want the content parsed or not?
							if($match->parseContent())
							{
								$tag_handled_content = $this->interpretStruct(array($tag_content, $struct[$pos]->closingNode()->position() - $pos + 1));
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

						if($match->blockLevel())
						{
							// Looks like we may need to close some tags.
							$popped = null;
							while(($popped = array_pop($opened_tags)) !== null && !$popped['match']->blockLevel())
							{
								$message .= str_replace(array_keys($popped['replacements']), array_values($popped['replacements']), $popped['match']->after());
								$opened_count--;

								// Mark the other closing tag as ignored, it won't be
								// needed anymore.
								$popped['node']->closingNode()->setIgnore(true);
							}

							if($popped !== null && $popped['match']->blockLevel())
							{
								// Woops, we should probably put that back!
								$opened_tags[] = $popped;
								$opened_count++;
							}
						}

						// There could be some things in need of replacing.
						$replacements = array();
						$replacement_count = 0;

						// {value} gets replaced with the value.
						if($struct[$pos]->tagType() == 'value' || $struct[$pos]->tagType() == 'empty-value')
						{
							$replacements['{value}'] = $data;
							$replacement_count++;
						}
						// ... and all {attribute name}'s get replace with their
						// values as well.
						elseif($struct[$pos]->tagType() == 'attribute' || $struct[$pos]->tagType() == 'empty-attribute')
						{
							foreach($match->attributes() as $attrName => $attrData)
							{
								// Perhaps the attribute is optional, in which place we need
								// to do something a bit different.
								if(!empty($attrData['optional']))
								{
									$data[$attrName] = array_key_exists($attrName, $data) ? str_replace('[value]', $data[$attrName], $attrData['replace']) : '';
								}

								$replacements['{'. $attrName. '}'] = $data[$attrName];
								$replacement_count++;
							}
						}

						// If there was a callback to handle the content within the
						// tag, we may need to have a replacement for that as well.
						if($tag_returned_content !== false)
						{
							$replacements['[content]'] = $tag_returned_content;
							$replacement_count++;
						}

						$message .= $replacement_count > 0 ? str_ireplace(array_keys($replacements), array_values($replacements), $match->before()) : $match->before();

						// Was there content which needed to be added?
						if(is_callable($match->callback()) || !$match->parseContent())
						{
							// If the BBCode tag took the handled content then we want
							// to use the returned content, otherwise the previously
							// handled (handled being parsed or not).
							$message .= $tag_returned_content !== false ? $tag_returned_content : $tag_handled_content;
						}

						// Do we need to add this to the opened tags array? We won't
						// need to if the tag is empty, if the BBCode tag returned the
						// content, or if the content wasn't parsed.
						if(!$match->isEmpty() && !is_callable($match->callback()) && $match->parseContent())
						{
							// Alright, go ahead and add this tag to the opened list.
							$opened_tags[] = array(
																 'match' => $match,
																 'replacements' => $replacement_count > 0 ? $replacements : null,
																 'pos' => $pos,
																 'node' => $struct[$pos],
															 );
						}
						else
						{
							// Looks like we'll deal with the closing tag right now.
							$message .= $replacement_count > 0 ? str_ireplace(array_keys($replacements), array_values($replacements), $match->after()) : $match->after();

							// Move the current position to the closing tag... Unless it
							// is an empty tag, in which case it has no closing tag.
							if(!$match->isEmpty())
							{
								$pos = $struct[$pos]->closingNode()->position();
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
					else
					{
						// We may need to close the tags preceding this one.
						if($struct[$pos]->dependsOnParent())
						{
							do
							{
								$popped = array_pop($opened_tags);

								if($popped !== null)
								{
									$opened_count--;

									// Now close the tag.
									$message .= $popped['replacements'] !== null ? str_ireplace(array_keys($popped['replacements']), array_values($popped['replacements']), $popped['match']->after()) : $popped['match']->after();
								}
							}
							while($popped !== null && $popped['node']->dependsOnParent());

							// We may have popped off one to many.
							if($popped !== null && !$popped->['node']->dependsOnParent())
							{
								$opened_tags[$opened_count++] = $popped;
							}
						}
					}

					// If we got to this point, this means that the tag has no
					// matches. Just a note, by marking this node as ignored, it's
					// closing node will automatically be marked as ignored as well.
					$struct[$pos]->setIgnore(true);
				}
				else
				{
					// Looks like we will be closing a tag. That's a simple thing to
					// do.
					$popped = array_pop($opened_tags);
					$opened_count--;

					// Did I say easy? I meant easy so long as something doesn't go
					// horribly wrong.
					if($popped !== null && $popped['node']->tagName() == $struct[$pos]->tagName())
					{
						$message .= $popped['replacements'] !== null ? str_ireplace(array_keys($popped['replacements']), array_values($popped['replacements']), $popped['match']->after()) : $popped['match']->after();

						// Got it!
						$pos++;

						continue;
					}
					else
					{
						// !!! Not sure what to do... :P
						die('Fatal error');
					}
				}
			}

			// If we have consecutive text nodes (or tag nodes which are to be
			// ignored anyways), why not combine them all into one?
			$buffer = '';
			while($pos < $struct_length && ($struct[$pos]->isText() || ($struct[$pos]->isTag() && $struct[$pos]->ignore())))
			{
				$buffer .= $struct[$pos++]->text();
			}

			// Add the text to the message. This could be actual text, or it could
			// be a tag which was not defined/valid... Either way, who really
			// cares? I know I don't! :-P.
			$message .= $this->format_text($buffer);
		}

		return $message;
	}

	/*
		Method: find_tags

		Finds and returns an array containing all the tags which can be a
		possible match for the specified tag node.

		Parameters:
			object $node - A <SpeedyTagNode> to find matches for.

		Returns:
			array - Returns an array containing matches for the specified tag and
							an empty array if nothing was found (of course).
	*/
	private function find_tags($node)
	{
		// If the node isn't a tag, or if there are no tags with that name, we
		// can go ahead and immediately return an empty array.
		if(!$node->isTag() || !$this->tagExists($node->tagName()))
		{
			return array();
		}
		else
		{
			// Let's take a look at the defined tags.
			$matches = array();
			foreach($this->tags[$node->tagName()]['tags'] as $tag)
			{
				$tagType = $tag->isEmpty() ? SpeedyBBC::substr($tag->type(), 6) : $tag->type();

				// Make sure the types match.
				if($node->tagType() != $tagType)
				{
					continue;
				}

				// If it is an attribute tag, we need to check to make sure all
				// the right attributes are there -- or that they can be missing.
				if($tag->type() == 'attribute' || $tag->type() == 'empty-attribute')
				{
					$is_match = true;
					foreach($tag->attributes() as $attrName => $options)
					{
						// Make sure that the attribute is defined, or that it can be
						// missing.
						if($node->attribute($attrName) === false && !$options['optional'])
						{
							// It's not a match.
							$is_match = false;

							break;
						}
					}

					// If it wasn't a match, we'll move on to the next.
					if(!$is_match)
					{
						continue;
					}
				}

				// Looks like we have a match!
				$matches[] = $tag;
			}
		}

		return $matches;
	}

	/*
		Method: format_text

		Formats the specified string by linking URL's, replacing smiley codes
		with images, and so on.

		Parameters:
			string $message - The string to format.

		Returns:
			string - Returns the formatted string.
	*/
	private function format_text($message)
	{
		$replacements = array(
											"\r\n" => '<br />',
											"\n" => '<br />',
										);

		return str_ireplace(array_keys($replacements), array_values($replacements), $message);
	}

	/*
		Method: strlen

		Returns the length of the given string. This method will use strlen's
		multi-byte counterpart if available.

		Parameters:
			string $str - The string being measured for length.

		Returns:
			int - The length of the string.
	*/
	public static function strlen($str)
	{
		return SPDY_MB_EXISTS ? mb_strlen($str) : strlen($str);
	}

	/*
		Method: substr

		Returns the portion of string specified by the start and length
		parameters. This method will use substr's multi-byte counterpart if
		available.

		Parameters:
			string $str - The input string.
			int $start - The position in the string to start at.
			int $length - The total characters to retrieve beginning at $start.

		Returns:
			string - Returns the extracted part of the string.
	*/
	public static function substr($str, $start, $length = null)
	{
		if($length === null)
		{
			return SPDY_MB_EXISTS ? mb_substr($str, $start) : substr($str, $start);
		}
		else
		{
			return SPDY_MB_EXISTS ? mb_substr($str, $start, $length) : substr($str, $start, $length);
		}
	}

	/*
		Method: strpos

		Returns an integer indicating the position within the specified string
		(haystack) where the first occurrence of $needle is located, starting
		from the specified $start position. This method will use strpos's
		multi-byte counterpart if available.

		Parameters:
			string $haystack - The string to search.
			string $needle - The string to search for in $haystack.
			int $start - The position within $haystack to start searching for
									 $needle.

		Returns:
			mixed - Returns an integer with the location of $needle, but false if
							$needle was not found.
	*/
	public static function strpos($haystack, $needle, $start = null)
	{
		if($start === null)
		{
			return SPDY_MB_EXISTS ? mb_strpos($haystack, $needle) : strpos($haystack, $needle);
		}
		else
		{
			return SPDY_MB_EXISTS ? mb_strpos($haystack, $needle, $start) : strpos($haystack, $needle, $start);
		}
	}

	/*
		Method: strtolower

		Returns the string with all characters lowercased. This method will use
		strtolower's multi-byte counterpart if available.

		Parameters:
			string $str - The string to lowercase.

		Returns:
			string - The lowercased string.
	*/
	public static function strtolower($str)
	{
		return SPDY_MB_EXISTS ? mb_strtolower($str) : strtolower($str);
	}

	/*
		Method: htmlspecialchars

		Returns the string with <, >, ', " and & replaced with their HTML entity
		counterpart.

		Parameters:
			string $str

		Returns:
			string
	*/
	public static function htmlspecialchars($str)
	{
		return htmlspecialchars($str, ENT_QUOTES, SPDY_MB_EXISTS ? mb_internal_encoding() : 'UTF-8');
	}
}

require(dirname(__FILE__). '/speedynode.class.php');
require(dirname(__FILE__). '/speedytag.class.php');
?>