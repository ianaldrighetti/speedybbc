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
									'name' => 'align',
									'type' => 'value',
									'value' => array(
															 'regex' => '~^(left|center|right)$~i',
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
																							 if((string)$value == (string)(int)$value && (int)$value >= 0 && (int)$value <= 7)
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
																							 if(preg_match(\'~^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$~i\', $value) > 0)
																							 {
																								 return $value;
																							 }
																							 // !!! TODO: More colors!
																							 elseif(in_array(strtolower($value), array(\'red\', \'green\', \'blue\', \'black\', \'cyan\', \'yellow\', \'white\', \'brown\', \'darkblue\', \'darkgreen\')))
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
																	// !!! TODO: regex
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
									'after' => '', // !!! TODO: Replace \r\n
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
									'name' => 'next', // !!! TODO: Test if it works if unclosed [b]
									'type' => 'empty-basic',
									'before' => '</td><td>',
									'after' => '',
									'block_level' => true,
									'required_parents' => array('columns'),
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

		// Not much else to do but add it.
		$tag_index = $this->strtolower($this->substr($tag->name(), 0, 1));

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
					}


					return true;
				}
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
		$struct = $this->to_struct($message);

		// Now we need to interpret that structure into something useful, like
		// actually use the defined BBCode tags, ya know?
		$message = $this->interpret_struct($struct);

		// Do we want to cache the message?
		if($this->cachedir() !== null)
		{
			// The message ID was already all set, seeing as caching is enabled.
			// So, let's save it.
			$fp = fopen($this->cachedir(). '/'. $message_id. '.bbc-cache.php', 'w');
			flock($fp, LOCK_EX);

			fwrite($fp, '<?php if(!defined(\'INBBCODECLASS\')) { die; } $message_cache = '. var_export($message). ';');

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
		$struct = array();
		$length = $this->strlen($message);
		$cur_pos = 0;
		$prev_pos = 0;

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
				if($prev_pos != $last_pos)
				{
					$node = new TextNode($this->substr($message, $prev_pos, $last_pos - $prev_pos));
					$struct[] = $node;
				}

				// Now for the tag itself.
				$node = new TagNode($this->substr($message, $last_pos, $pos - $last_pos + 1), $this->substr($message, $last_pos + 1, 1) == '/');

				// Woah there, horsey! Do we even have a tag by that name?
				if(isset($this->index['names'][$node->getTag()]))
				{
					// Yup, we do.
					$struct[] = $node;

					// Now, everything has been handled up to this point.
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
			$node = new TextNode($this->substr($message, $prev_pos));
			$struct[] = $node;
		}

		// And here you go. I did my job...
		return $struct;
	}

	/*
		Method: interpret_struct

		A private method which is used to interpret the generated structure from
		a message.

		Parameters:
			array $struct - The structure to interpret.

		Returns:
			string - Returns the interpreted message.
	*/
	private function interpret_struct($struct)
	{
		// We will need a few variables to keep track of things. Like the
		// message which has been generated so far.
		$message = '';

		// The current position in the parsed structure, along with how big it
		// is (because recounting each time is slow).
		$pos = 0;
		$length = count($struct);

		// Last, but not least, which tags are opened? Since we will be messing
		// with this a lot, we should manually keep track of how many are opened
		// for performance reasons.
		$opened_tags = array();
		$opened_count = 0;
		while($pos < $length)
		{
			// Is this a tag?
			if($struct[$pos]->type() == 'tag' && !$struct[$pos]->is_closing())
			{
				// Looks like we have some work to do.
				// Well, if we have a tag which is defined.
				$tag_name = $struct[$pos]->getTag();
				$tag_index = substr($tag_name, 0, 1);

				// This is a quick way to see if it exists.
				if(isset($this->tags[$tag_index]))
				{
					// We will want to find all the matches at once in case one
					// doesn't do the job... We won't want to keep iterating through
					// the tag array.
					$found = false;
					$matches = array();

					// !!! TODO: An index of tags should be created for even faster
					// 					 retrieval.

					foreach($this->tags[$tag_index] as $tag)
					{
						// Make sure the name and type match.
						if($tag->name() == $struct[$pos]->getTag() && substr($tag->type(), -strlen($struct[$pos]->getTagType()), strlen($struct[$pos]->getTagType())) == $struct[$pos]->getTagType())
						{
							// Well, we need to check something if it is an attribute tag.
							if($struct[$pos]->type() == 'attribute')
							{
								// Make sure all the attributes are there.
								$given_attrs = array_keys($struct[$pos]->getAttributes());
								$tag_attrs = array_keys($tag->attributes());

								// They can't have matching attributes if the count doesn't
								// match...
								if(count($given_attrs) == count($tag_attrs))
								{
									$attrs_matching = 0;
									foreach($tag_attrs as $attr_name)
									{
										// Make sure this attribute was specified.
										if(in_array($attr_name, $given_attrs))
										{
											$attrs_matching++;
										}
									}

									// Did they match?
									if(count($tag_attrs) != $attrs_matching)
									{
										// Nope, so let's move on.
										continue;
									}
								}
							}

							// We found a match!
							$matches[] = $tag;
						}
					}

					// So, did we find it?
					if(count($matches) > 0)
					{
						// There will be no need to fetch the content of the tag over
						// and over again.
						$tag_content = null;
						$base_tags = null;
						$end_pos = null;
						$tag_handled = false;
						foreach($matches as $match)
						{
							// Sweet...
							$data = null;

							// Let's see if there is any data which needs validating.
							// We need to handle each tag differently depending upon the
							// type. First off: value ([name=...])!
							if($struct[$pos]->getTagType() == 'value')
							{
								// Set the value, so we can do what we need to do...
								$data = $struct[$pos]->getValue();

								// Let's get the information we need.
								$options = $match->value();
								$regex = isset($options['regex']) ? $options['regex'] : false;
								$callback = isset($options['callback']) ? $options['callback'] : false;

								// A regular expression... Perhaps?
								if($this->strlen($regex) > 0)
								{
									if(@preg_match($regex, $data) == 0)
									{
										// Uh oh, this is no good.
										continue;
									}
								}

								// Was a valid callback supplied?
								if(is_callable($callback))
								{
									// Let's see, is the value okay? Did you want to change
									// it by returning a new value?
									if(($data = call_user_func($callback, $data)) === false)
									{
										// Looks like its not okay, oh well.
										continue;
									}
								}
							}
							// Now to handle an attribute-type tag, like [name attr=val].
							elseif($struct[$pos]->getTagType() == 'attribute')
							{
								// The TagNode object can get the attributes for us.
								$data = $struct[$pos]->getAttributes();

								// Keep track of whether or not an error occurred.
								$attr_error = false;
								foreach($match->attributes() as $attr_name => $options)
								{
									// A regular expression... Perhaps?
									if(isset($options['regex']) && $this->strlen($options['regex']) > 0)
									{
										if(@preg_match($options['regex'], $data[$attr_name]) == 0)
										{
											// Uh oh, this is no good.
											$attr_error = true;

											continue;
										}
									}

									if(isset($options['callback']) && is_callable($options['callback']))
									{
										// Let's see, is the value okay? Did you want to change
										// it by returning a new value?
										if(($data[$attr_name] = call_user_func($options['callback'], $data[$attr_name])) === false)
										{
											// Looks like its not okay, oh well.
											$attr_error = true;

											continue;
										}
									}
								}

								// Did we encounter an issue?
								if(!empty($attr_error))
								{
									continue;
								}
							}

							// Well, do they want the content of the tag? There will be a
							// valid callback, then... The tag may not want the content of
							// the tag, but it also may not want the contents to be parsed,
							// which means we need to get the content, still.
							if((is_callable($match->callback()) || $match->parse_content() === false || count($match->required_children()) > 0) && $tag_content === null)
							{
								$start_pos = $pos;
								$opened = array();
								$index = 0;
								$level = 0;
								$base_tags = array();
								while($pos < $length)
								{
									if($struct[$pos]->type() == 'tag' && !$struct[$pos]->is_closing())
									{
										$opened[$index++] = $struct[$pos]->getTag();

										// Only if we are at level 1 will this be considered a
										// base tag... Which is right above the current tag.
										if($level == 1)
										{
											$base_tags[] = $struct[$pos]->getTag();
										}

										$level++;
									}
									elseif($struct[$pos]->type() == 'tag')
									{
										// Hopefully this will be easy...
										if($opened[$index - 1] == $struct[$pos]->getTag())
										{
											// POP! goes the weasel!
											array_pop($opened);

											// That's one less.
											$index--;
											$level--;
										}
										// Make sure the tag is in there somewhere...
										elseif(in_array($struct[$pos]->getTag(), $opened))
										{
											// Just gotta keep poppin' them off until we find it.
											$popped = null;
											while($struct[$pos]->getTag() != ($popped = array_pop($opened)))
											{
												$index--;
												$level--;
											}

											if($popped !== null && $popped == $struct[$pos]->getTag())
											{
												$index--;
												$level--;
											}
										}
									}
									// We also need to consider the possibility of text...
									elseif($level == 1)
									{
										$base_tags[] = 'text';
									}

									// Did we closing everything?
									if($index < 1)
									{
										break;
									}

									// Let's move along...
									$pos++;
								}

								// Let's make sure that worked...
								if($index < 1)
								{
									// Let's get the tags content.
									for($i = $start_pos + 1; $i < $pos; $i++)
									{
										$tag_content[] = $struct[$i];
									}

									$end_pos = $pos;
									$pos = $start_pos;
								}
								else
								{
									// Let's move along...
									$pos = $start_pos;

									continue;
								}
							}

							// Alright, let's check again, did they want the content
							// parsed or not?
							$tag_handled_content = '';
							if(is_callable($match->callback()) || $match->parse_content() === false)
							{
								// If they want it parsed, we will call on the interpret
								// structure method we're in now... No need to do it all
								// again!
								if($match->parse_content())
								{
									$tag_handled_content = $this->interpret_struct($tag_content);
								}
								// If they don't want it parsed, then we will just collect
								// all the text values together.
								else
								{
									foreach($tag_content as $cn)
									{
										$tag_handled_content .= $cn->text();
									}
								}
							}

							// Alright, is this a block level tag?
							if($match->block_level())
							{
								// Looks like we need to close some tags.
								$popped = null;
								while(($popped = array_pop($opened_tags)) !== null && !$popped->block_level())
								{
									$message .= str_ireplace(array_keys($popped->replacements()), array_values($popped->replacements()), $popped->after());
									$opened_count--;
								}

								if($popped !== null && $popped->block_level())
								{
									// We went slightly crazy, didn't we?
									$opened_tags[] = $popped;
								}
							}

							// Does a tag need to be opened for this one to be valid?
							if(count($match->required_parents()) > 0)
							{
								if(($opened_count > 0 && !in_array($opened_tags[$opened_count - 1]->name(), $match->required_parents())) || $opened_count == 0)
								{
									// Sorry, but it there is a tag required to be opened
									// first. Shoot!
									continue;
								}
							}

							// We just checked for required parents, how about children?
							if(count($match->required_children()) > 0)
							{
								// Well, if there are no base tags, then there certainly
								// aren't any children.
								if(count($base_tags) > 0)
								{
									// Make sure all the children which will appear
									// immediately are allowed.
									$disallowed_found = false;
									foreach($base_tags as $base_tname)
									{
										if(!in_array($base_tname, $match->required_children()))
										{
											// Looks like this tag isn't allowed.
											$disallowed_found = true;

											break;
										}
									}

									// Did we find any tags which aren't allowed?
									if($disallowed_found)
									{
										// Better luck next time?
										continue;
									}
								}
								else
								{
									// In that case, NEXT!
									continue;
								}
							}

							// Oh, did you want the contents of the tag? Alright.
							$tag_returned_content = false;
							if(is_callable($match->callback()) && ($tag_returned_content = call_user_func($match->callback(), $tag_handled_content, $data)) === false)
							{
								// That's not a good sign.
								continue;
							}

							// There could be something in need of replacing.
							$replacements = array();

							// {value} gets replaced with the value.
							if($struct[$pos]->getTagType() == 'value')
							{
								$replacements['{value}'] = $data;
							}
							// ... and all {attribute name}'s get replaced with their
							// values as well.
							elseif($struct[$pos]->getTagType() == 'attribute')
							{
								foreach($data as $attr_name => $value)
								{
									$replacements['{'. $attr_name. '}'] = $value;
								}
							}

							// Maybe there is some content that needs replacing?
							if($tag_returned_content !== false)
							{
								// We use [content] in case there is a content attribute,
								// you never know!
								$replacements['[content]'] = $tag_returned_content;
							}

							// Replace like the wind!
							$message .= str_ireplace(array_keys($replacements), array_values($replacements), $match->before());

							// Was there content which needed to be added?
							if(is_callable($match->callback()) || !$match->parse_content())
							{
								// If the BBCode tag took the handled content then we want
								// to use the returned content, otherwise the previously
								// handled (handled being parsed or not parsed).
								$message .= $tag_returned_content !== false ? $tag_returned_content : $tag_handled_content;
							}

							// Do we need to add this to the opened tags array? We won't
							// need to if the tag is empty, if the BBCode tag returned
							// content, or if the content wasn't parsed.
							if(!$match->is_empty() && !is_callable($match->callback()) && $match->parse_content())
							{
								// There could be (but probably isn't) some things in the
								// closing tag that need replacing, and since we don't want
								// to do this all over again, we will store it here:
								$match->set_replacements($replacements);

								// Now save the opened tag and increment the opened count.
								$opened_tags[] = $match;
								$opened_count++;
							}
							else
							{
								// Um, nope!!! So replace "stuff" in the after tag and add
								// it to the message.
								$message .= str_ireplace(array_keys($replacements), array_values($replacements), $match->after());

								// We may need to move the current position to after the
								// content we possibly already handled.
								if($end_pos !== null)
								{
									$pos = $end_pos;
								}
							}

							// This tag was handled successfully! Awesomesauce!
							$tag_handled = true;

							break;
						}

						// Was the tag handled?
						if(!empty($tag_handled))
						{
							// Yes, it was, so we can move along!
							$pos++;

							continue;
						}
					}
				}
			}
			// Must mean this tag is a closing tag.
			elseif($struct[$pos]->type() == 'tag')
			{
				// Before we start closing a bunch of tags, why don't we see if this
				// was even opened in the first place.
				if($opened_count > 0)
				{
					// It could be, so let's see.
					$is_opened = false;
					// !!! TODO: Keep track of the index and then use a for loop to
					//					 close everything, not a while which requires
					//					 something extra... Actually, keep track of how many.
					// ERROR: Need to start from the back!!!
					foreach($opened_tags as $open_tag)
					{
						if($open_tag->name() == $struct[$pos]->getTag())
						{
							// Ah, good. It appears this tag was opened.
							$is_opened = true;

							break;
						}
					}

					// So, what did our search find?
					if($is_opened)
					{
						// I guess the tag was opened. So here we go!
						$popped = null;
						while(($popped = array_pop($opened_tags)) !== null && $popped->name() != $struct[$pos]->getTag())
						{
							// The after tag may have "stuff" in need of replacing.
							$message .= str_ireplace(array_keys($popped->replacements()), array_values($popped->replacements()), $popped->after());
							$opened_count--;
						}

						if($popped !== null)
						{
							$message .= str_ireplace(array_keys($popped->replacements()), array_values($popped->replacements()), $popped->after());
							$opened_count--;
						}

						// This was handled...
						$pos++;

						continue;
					}
				}
			}

			// Add the text to the message. This could be actual text or it could
			// be a tag which was not defined/valid... Either way, who cares? :-P
			$message .= $this->format_text($struct[$pos]->text());

			// Move along, ya hear?
			$pos++;
		}

		// Was there still some stuff left opened? That's no good!
		if(count($opened_tags) > 0)
		{
			while(($popped = array_pop($opened_tags)) !== null)
			{
				// We don't want to mess up your layout, so close the remaining tags
				// along with replacing any "stuff."
				$message .= str_ireplace(array_keys($popped->replacements()), array_values($popped->replacements()), $popped->after());
			}
		}

		// We're done!
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
		$this->replacements = array();

		// Alright, let's set those options, if you did.
		foreach(array('name', 'type', 'value', 'attributes', 'parse_content',
									'callback', 'before', 'after', 'required_parents',
									'required_children', 'block_level') as $attribute)
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

			// Make sure the attribute name is alright.
			if(preg_match('~^([a-z0-9_-])+$~', $name) == 0)
			{
				// So, that's not okay ;-)
				return false;
			}

			$accepted[$name] = array(
													 'regex' => isset($options['regex']) ? $options['regex'] : null,
													 'callback' => isset($options['callback']) && is_callable($options['callback']) ? $options['callback'] : null,
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

	public function __construct()
	{
		$this->text = null;
		$this->type = null;
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
	public function __construct($text = '')
	{
		$this->setText($text);
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

	/*
		Constructor: __construct

		Parameters:
			string $text - The entire string containing the tag, including the
										 square brackets.
			bool $is_closing - Whether the tag is closing, i.e. [/b]
	*/
	public function __construct($text = '', $is_closing = false)
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
}
?>