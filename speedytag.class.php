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
	Class: SpeedyTag

	The SpeedyTag class is used to represent a usable BBCode tag, which
	contains all the information on how the tag should be parsed, such as
	callbacks, regular expressions for validation, parent/child requirements,
	and so on.
*/
class SpeedyTag
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

	/*
		Constructor: __construct

		All attributes are initialized, and all specified options are set.

		Parameters:
			array $options - An array containing options for the BBCode tag.

		Note:
			The following indexes are supported for $options:
				string name - See <BBCodeTag::setName>.
				string type - See <BBCodeTag::setType>.
				array value - See <BBCodeTag::setValue>.
				array attributes - See <BBCodeTag::setAttributes>.
				bool parseContent - See <BBCodeTag::setParseContent>.
				callback callback - See <BBCodeTag::setCallback>.
				string before - See <BBCodeTag::setBefore>.
				string after - See <BBCodeTag::setAfter>.
				array requiredParents - See <BBCodeTag::setRequiredParents>.
				array requiredChildren - See <BBCodeTag::setRequiredChildren>.
				bool blockLevel - See <BBCodeTag::setBlockLevel>.
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

		// Lower case the index names.
		foreach($options as $option => $value)
		{
			unset($options[$option]);

			$options[SpeedyBBC::strtolower($option)] = $value;
		}

		// Alright, let's set those options, if you did.
		foreach(array('name', 'type', 'value', 'attributes', 'parsecontent',
									'callback', 'before', 'after', 'requiredparents',
									'requiredchildren', 'blocklevel', 'disallowedchildren',
									'allowedchildren', 'disableformatting') as $attribute)
		{
			if(isset($options[$attribute]))
			{
				$this->{'set'. $attribute}($options[SpeedyBBC::strtolower($attribute)]);
			}
		}
	}

	/*
		Method: setName

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
	public function setName($name)
	{
		$name = strtolower($name);

		// Make sure the name isn't empty, or otherwise invalid.
		if(SpeedyBBC::strlen($name) == 0 || preg_match('~^([a-z0-9-_])+$~i', $name) == 0)
		{
			return false;
		}

		// Looks okay to me.
		$this->name = $name;

		return true;
	}

	/*
		Method: setType

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
	public function setType($type)
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
		Method: setValue

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
	public function setValue($options)
	{
		// If this tag is not a value type, then forget it.
		if(!in_array($this->type(), array('value', 'empty-value')))
		{
			return false;
		}

		if(isset($options['regex']) && SpeedyBBC::strlen($options['regex']) > 0)
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
		Method: setAttributes

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
	public function setAttributes($attributes)
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
			$name = SpeedyBBC::strtolower($name);

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
		Method: setParseContent

		Whether the contents of the BBCode tag should be parsed.

		Parameters:
			bool $parse - Whether the content should be parsed.

		Returns:
			void - Nothing is returned by this method.
	*/
	public function setParseContent($parse)
	{
		$this->parse_content = !empty($parse);
	}

	/*
		Method: setCallback

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
	public function setCallback($callback)
	{
		if(substr($this->type(), 0, 6) == 'empty-' || ($callback !== null && !is_callable($callback)))
		{
			return false;
		}

		$this->callback = $callback;

		return true;
	}

	/*
		Method: setBefore

		Sets the content which will be replaced with the opening tag.

		Parameters:
			string $before - The content to replace with the opening tag.

		Returns:
			void - Nothing is returned by this method.

		Note:
			Even if the tag is an empty tag, both the before and after options are
			used.
	*/
	public function setBefore($before)
	{
		$this->before = $before;
	}

	/*
		Method: setAfter

		Sets the content which will be replaced with the closing tag.

		Parameters:
			string $after - The content to replace with the closing tag.

		Returns:
			void - Nothing is returned by this method.
	*/
	public function setAfter($after)
	{
		$this->after = $after;
	}

	/*
		Method: setRequiredParents

		Tags which must have been just opened (level wise) in order for this tag
	  to be parsed.

	  Parameters:
			array $required - An array containing the names of tags which are
												required.

		Returns:
			bool - Returns true if the tags were set, false if not.

		Note:
			Please note that all tags with the same name will have the same parent
			and child requirements.
	*/
	public function setRequiredParents($required)
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
				$tag_name = SpeedyBBC::strtolower($tag_name);

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
		Method: setRequiredChildren

		Tags which must be opened immediately (level wise) in order for this tag
	  to be parsed.

	  Parameters:
			array $required - An array containing the names of tags which are
												required.

		Returns:
			bool - Returns true if the tags were set, false if not.

		Note:
			Please note that all tags with the same name will have the same parent
			and child requirements.
	*/
	public function setRequiredChildren($required)
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
				$tag_name = SpeedyBBC::strtolower($tag_name);

				// Make sure the name of the tag is allowed.
				if($tag_name == '[text]' || preg_match('~^([a-z0-9_-])+$~i', $tag_name) > 0)
				{
					$this->required_children[] = $tag_name;
				}
			}
		}

		return true;
	}

	/*
		Method: setBlockLevel

		Sets whether the tag is a block level tag, meaning that all non-block
		level tags are closed before this tag would be opened.

		Parameters:
			bool $isBlock - Whether the tag is a block level tag.

		Returns:
			void - Nothing is returned by this method.
	*/
	public function setBlockLevel($isBlock)
	{
		$this->block_level = !empty($isBlock);
	}

	/*
		Method: setDisallowedChildren

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
	public function setDisallowedChildren($disallowed)
	{
		if(is_array($disallowed))
		{
			// Reset this option.
			$this->disallowed_children = array();

			foreach($disallowed as $tag_name)
			{
				$tag_name = SpeedyBBC::strtolower($tag_name);

				// No sense in clogging up the "tubes" if the tag name isn't even
				// allowed...
				if($tag_name == '[text]' || preg_match('~^([a-z0-9_-])+$~i', $tag_name) > 0)
				{
					// Looks good!
					$this->disallowed_children[] = $tag_name;
				}
			}

			// Oh, and don't forget to disable the allowed children option!
			$this->setAllowedChildren(false);
		}
		// Not an array? Then we will assume you want to unset this option.
		else
		{
			$this->disallowed_children = array();
		}
	}

	/*
		Method: setAllowedChildren

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
	public function setAllowedChildren($allowed)
	{
		if(is_array($allowed))
		{
			$this->allowed_children = array();

			foreach($allowed as $tag_name)
			{
				$tag_name = SpeedyBBC::strtolower($tag_name);

				if($tag_name == '[text]' || preg_match('~^([a-z0-9_-])+$~i', $tag_name) > 0)
				{
					$this->allowed_children[] = $tag_name;
				}
			}

			// Now disable the disallowed children option.
			$this->setDisallowedChildren(false);
		}
		else
		{
			$this->allowed_children = array();
		}
	}

	/*
		Method: setDisableFormatting

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
	public function setDisableFormatting($options)
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

	public function parseContent()
	{
		return $this->parse_content;
	}

	public function callback()
	{
		return !$this->isEmpty() ? $this->callback : null;
	}

	public function before()
	{
		return $this->before;
	}

	public function after()
	{
		return $this->after;
	}

	public function requiredParents()
	{
		return $this->required_parents;
	}

	public function requiredChildren()
	{
		return $this->required_children;
	}

	public function blockLevel()
	{
		return $this->block_level;
	}

	public function disallowedChildren()
	{
		return $this->disallowed_children;
	}

	public function allowedChildren()
	{
		return $this->allowed_children;
	}

	public function disableFormatting($option = null)
	{
		return $option === null ? $this->disable_formatting : $this->disable_formatting & $option;
	}

	/*
		Method: isEmpty

		Whether the tag is an 'empty' tag, meaning there is no closing tag.

		Parameters:
			none

		Returns:
			bool - Returns true if the tag is an empty tag, false if not.
	*/
	public function isEmpty()
	{
		return substr($this->type(), 0, 6) == 'empty-';
	}

	/*
		Method: isValid

		Whether this instance defines a valid and functional BBCode tag.

		Parameters:
			none

		Returns:
			bool - Returns true if the BBCode tag is functional, false if not.
	*/
	public function isValid()
	{
		if($this->name() === null || $this->type() === null)
		{
			return false;
		}

		// Hmm, I guess this is the only other things we need to check...
		if($this->type() == 'attribute' || $this->type() == 'empty-attribute')
		{
			return count($this->attributes()) > 0;
		}
		else
		{
			return true;
		}
	}
}
?>