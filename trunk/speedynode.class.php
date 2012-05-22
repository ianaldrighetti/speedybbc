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
	Class: SpeedyNode

	This class is used to contain a piece of a parsed BBCode message, this
	class is not used directly, but is inherited by <SpeedyTextNode> and
	<SpeedyTagNode>.
*/
class SpeedyNode
{
	// Variable: text
	// A string which contains the text the node represents.
	protected $text;

	// Variable: type
	// A string indicating the type of the node.
	protected $type;

	// Variable: level
	// An integer containing the level at which the node resides in regards to
	// the topmost parent (starting at 0).
	protected $level;

	// Variable: parentNode
	// An object containing the nodes parent.
	protected $parentNode;

	// Variable: childNodes
	// An array containing the nodes child nodes.
	protected $childNodes;

	// Variable: childNodesLength
	// An integer containing the the total number of immediate child nodes.
	protected $childNodesLength;

	// Variable: checked
	// A boolean value indicating whether the node's constraints have been
	// checked.
	protected $checked;

	/*
		Constructor: __construct

		Parameters:
			string $text - The text the node represents.
			string $type - The type of node.
			int $level - The level at which the node resides.
			object $parentNode - The parent node of the node.
	*/
	public function __construct($text = null, $type = null, $level = 0, $parentNode = null)
	{
		// Initialize everything to null and 0's.
		$this->text = null;
		$this->type = null;
		$this->level = 0;
		$this->parentNode = null;
		$this->childNodes = array();
		$this->childNodesLength = 0;
		$this->checked = false;

		// Did we get any arguments?
		if($text !== null)
		{
			$this->setText($text);
		}

		if($type !== null)
		{
			$this->setType($type);
		}

		if($level !== 0)
		{
			$this->setLevel($level);
		}

		if($parentNode !== null)
		{
			$this->setParentNode($parentNode);
		}
	}

	/*
		Method: text

		Returns a string containing the text the node is representing.

		Parameters:
			none

		Returns:
			string
	*/
	public function text()
	{
		return $this->text;
	}

	/*
		Method: setText

		Sets the text the node represents.

		Parameters:
			string $text - The text to have the node represent.

		Returns:
			bool - Returns true on success and false on failure.
	*/
	public function setText($text)
	{
		if(!is_string($text))
		{
			return false;
		}
		else
		{
			$this->text = $text;

			return false;
		}
	}

	/*
		Method: appendText

		Appends text to the current text value of the node.

		Parameters:
			string $text - The tect to append.

		Returns:
			bool - Returns true on success, false on failure.
	*/
	public function appendText($text)
	{
		if(!is_string($text))
		{
			return false;
		}
		else
		{
			$this->text .= $text;

			return true;
		}
	}

	/*
		Method: type

		Returns the type of the node.

		Parameters:
			none

		Returns:
			string
	*/
	public function type()
	{
		return $this->type;
	}

	/*
		Method: setType

		Sets the type of the node.

		Parameters:
			string $type - The type of the node.

		Returns:
			bool - Returns true on success and false on failure.
	*/
	public function setType($type)
	{
		if(strlen($type) == 0)
		{
			return false;
		}
		else
		{
			$this->type = strtolower($type);

			return true;
		}
	}

	/*
		Method: isText

		Indicates whether the node is a text node.

		Parameters:
			none

		Returns:
			bool - Returns true if the node is a text node, false if not.
	*/
	public function isText()
	{
		return $this->type == 'text' || empty($this->type);
	}

	/*
		Method: isTag

		Indicates whether the node is a tag node.

		Parameters:
			none

		Returns:
			bool - Returns true if the node is a tag node, false if not.
	*/
	public function isTag()
	{
		return $this->type == 'tag';
	}

	/*
		Method: level

		Returns the level at which the node resides.

		Parameters:
			none

		Returns:
			int
	*/
	public function level()
	{
		return $this->level;
	}

	/*
		Method: setLevel

		Sets the level at which the node resides.

		Parameters:
			int $level - The level of the node, starting at 0.

		Returns:
			bool - Returns true on success and false on failure.
	*/
	public function setLevel($level)
	{
		if((int)$level < 0)
		{
			return false;
		}
		else
		{
			$this->level = (int)$level;

			return true;
		}
	}

	/*
		Method: parentNode

		Returns the parent of the node.

		Parameters:
			none

		Returns:
			object - Returns the parent of the node, however if the node has no
							 parent then null is returned.
	*/
	public function parentNode()
	{
		return $this->parentNode;
	}

	/*
		Method: setParentNode

		Sets the parent of the node.

		Parameters:
			object $parentNode - The parent node.

		Returns:
			bool - Returns true if the parent node was set successfully, but false
						 if the parameter is not valid (not a SpeedyNode).
	*/
	public function setParentNode($parentNode)
	{
		// Make sure the node is valid.
		if(!is_a($parentNode, 'SpeedyNode'))
		{
			return false;
		}
		else
		{
			$this->parentNode = $parentNode;

			return true;
		}
	}

	/*
		Method: childNodes

		Returns an array containing all the nodes children.

		Parameters:
			none

		Returns:
			array - Returns an array of SpeedyNode objects which are the node's
							children.
	*/
	public function childNodes()
	{
		return $this->childNodes;
	}

	/*
		Method: childNodesLength

		Returns the total number of immediate children the node has.

		Parameters:
			none

		Returns:
			int - Returns an integer containing the total number of immediate
						children the node has, if any.
	*/
	public function childNodesLength()
	{
		return $this->childNodesLength;
	}

	/*
		Method: addChildNode

		Adds a child node to the node.

		Parameters:
			object $childNode - A child node to add.

		Returns:
			bool - Returns true if the node is added successfully, false if the
						 parameter is not valid (not a SpeedyNode).
	*/
	public function addChildNode($childNode)
	{
		if(!is_a($childNode, 'SpeedyNode'))
		{
			return false;
		}
		else
		{
			$this->childNodes[] = $childNode;
			$this->childNodesLength++;

			return true;
		}
	}

	/*
		Method: checkConstraints
	*/
	public function checkConstraints()
	{
		if($this->checked)
		{
			return;
		}

		$this->checked = true;

		// All the child nodes need to check their constraints.
		if($this->childNodesLength > 0)
		{
			foreach($this->childNodes as $childNode)
			{
				// That is unless they're a text node, in which case they have no
				// constraints anyways.
				if(!$childNode->isText() && !$childNode->checked())
				{
					$childNode->checkConstraints();
				}
			}
		}
	}
}

/*
	Class: SpeedyTextNode

	This class inherits the <SpeedyNode> class, and is specifically designed
	(ha) for containing just text.
*/
class SpeedyTextNode extends SpeedyNode
{
	/*
		Constructor: __construct

		Parameters:
			string $text - The text the node will represent.
			int $level - The level at which the node resides.
			object $parentNode - The node's parent.
	*/
	public function __construct($text = '', $level = 0, $parentNode = null)
	{
		$this->setText($text);
		$this->setLevel($level);
		$this->setType('text');
		$this->setParentNode($parentNode);
	}
}

/*
	Class: SpeedyTagNode

	This class inherits the <SpeedyNode>, and is specificially designed for
	containing a BBCode tag. This class will handle all the parsing of the tag
	itself, such as determining its type (basic, value or attribute) then
	parsing the set value or attributes, if necessary.
*/
class SpeedyTagNode extends SpeedyNode
{
	// Variable: tagName
	// A string containing the tags name, such as url for [url=...].
	private $tagName;

	// Variable: tagType
	// A string containing the tags type, which is either basic, value or
	// attribute.
	private $tagType;

	// Variable: value
	// A string containing the value of the tag the node contains, if
	// applicable.
	private $value;

	// Variable: attributes
	// An array containing all the attributes within the tag the node
	// contains, if applicable.
	private $attributes;

	// Variable: ignore
	// A boolean indicating whether the tag is to be ignored (not parsed, but
	// that doesn't necessarily mean its children are to be ignored as well).
	private $ignore;

	// Variable: required
	// An array containing the required parent and child tags for the tag to
	// be parsed.
	private $required;

	// Variable: closingNode
	// This node is the current node's closing tag. This is used so that when
	// the closing tags opening tag is ignored, it can be too.
	private $closingNode;

	// Variable: position
	// An integer containing the position within the linear representation of
	// the parsed message that the node resides.
	private $position;

	/*
		Constructor: __construct

		Parameters:
			string $text - The text of the tag for the node to represent.
			int $level - The level at which the node resides.
			object $parentNode - The node's parent.
	*/
	public function __construct($text = '', $level = 0, $parentNode = null, $position = null)
	{
		// Initialize everything to null's and 0's.
		$this->tagName = null;
		$this->tagType = null;
		$this->value = null;
		$this->attributes = null;
		$this->ignore = false;
		$this->required = array(
												'parents' => array(),
												'parents_count' => 0,
												'children' => array(),
												'children_count' => 0,
											);
		$this->closingNode = null;
		$this->position = null;

		// Now set everything we need to, as set by the parameters.
		$this->setText($text);
		$this->setType('tag');
		$this->setLevel($level);
		$this->setParentNode($parentNode);
		$this->setPosition($position);
	}

	/*
		Method: tagName

		Gets the name of the tag the node represents.

		Parameters:
			none

		Returns:
			string - Returns the name of the tag.
	*/
	public function tagName()
	{
		// We don't need to do this over and over again.
		if($this->tagName !== null)
		{
			return $this->tagName;
		}
		// If the tag is closing, it's a pretty simple thing to figure out the
		// tag name.
		elseif(SpeedyBBC::substr($this->text, 1, 1) == '/')
		{
			$this->tagName = SpeedyBBC::strtolower(SpeedyBBC::substr($this->text, 2, -1));

			return $this->tagName;
		}

		// We will start looking for a space, equals sign, or a closing bracket
		// right after the opening bracket.
		$pos = 1;
		$length = SpeedyBBC::strlen($this->text);
		while($pos < $length && $this->text[$pos] != ' ' && $this->text[$pos] != '=' && $this->text[$pos] != ']')
		{
			// If we are within the while-block, that means we can keep moving
			// along.
			$pos++;
		}

		// $pos now contains the index position of the first space, first equals
		// sign, or the first closing bracket.
		$this->tagName = SpeedyBBC::strtolower(SpeedyBBC::substr($this->text, 1, $pos - 1));

		return $this->tagName;
	}

	/*
		Method: tagType

		Gets the type of the tag the node represents, which is either basic,
		value or attribute.

		Parameters:
			none

		Returns:
			string - Returns the type of the tag.
	*/
	public function tagType()
	{
		// If we've already determined the tag type, we don't need to do it
		// again.
		if($this->tagType !== null)
		{
			return $this->tagType;
		}
		// We can get this out of the way real quick-like.
		elseif(SpeedyBBC::substr($this->text, 1, 1) == '/')
		{
			$this->tagType = 'closing';

			return $this->tagType;
		}

		// We will need to use this a couple of times.
		$nospace_text = SpeedyBBC::strtolower(trim(str_replace(' ', '', $this->text)));

		// If it is a basic tag, then it should just be the tag name surrounded
		// by brackets.
		if($nospace_text == '['. $this->tagName(). ']')
		{
			$this->tagType = 'basic';
		}
		// If it is a value tag, then it should be a bracket, followed by the
		// tag name, which is then followed by an equals sign.
		elseif(SpeedyBBC::substr($nospace_text, 1, SpeedyBBC::strlen($this->tagName()) + 1) == $this->tagName(). '=')
		{
			$this->tagType = 'value';
		}
		else
		{
			// What else could it be? Hmm?
			$this->tagType = 'attribute';
		}

		return $this->tagType;
	}

	/*
		Method: isClosing

		Indicates whether the tag the node is representing is a closing tag.

		Parameters:
			none

		Returns:
			bool - Returns true if the tag is a closing tag, and false if not.
	*/
	public function isClosing()
	{
		return $this->tagType() == 'closing';
	}

	/*
		Method: value

		Returns the value of the tag represented by this node, the value being
		what appears after the equals sign.

		Parameters:
			none

		Returns:
			string - Returns a string containing the value of the tag, and null if
							 the node is not a value tag.

		Note:
			The parser will check if the value is surrounded by single or double
			quotes. If the value is surrounded by single/double quotes, they will
			not be contained within the returned value.

			Also note that any escaped quotes within the value will be fixed.
	*/
	public function value()
	{
		// We can't retrieve the value of the tag if it isn't a value tag, can
		// we? I didn't think so!
		if($this->tagType() != 'value')
		{
			return null;
		}
		// We don't want to go through this process each and every time someone
		// wishes to retrieve the tags value...
		elseif($this->value !== null)
		{
			return $this->value;
		}
		else
		{
			// First off, let's get passed that equals sign.
			$this->value = trim(SpeedyBBC::substr($this->text, SpeedyBBC::strpos($this->text, '=') + 1, -1));

			// Now to see if the value is surrounded by quotes, single or double.
			if(in_array($first_delimiter = SpeedyBBC::substr($this->value, 0, 6), array('&quot;', '&#039;')) && SpeedyBBC::substr($this->value, -6, 6) == $first_delimiter)
			{
				// Remove those quotes... No need for them anymore.
				$this->value = SpeedyBBC::substr($this->value, 6, -6);
				$this->value = str_replace('\\'. $first_delimiter, $first_delimiter, $this->value);
			}

			// That's it...
			return $this->value;
		}
	}

	/*
		Method: attributes

		Returns an array containing all of the attributes contained within the
		tag the node represents.

		Parameters:
			none

		Returns:
			array - Returns an array containing all the attribute names and their
							values contained within the tag, however null is returned if
							the tag is not an attribute tag.

		Note:
			Just as with the way value tags are parsed, any quotes will be not be
			contained within the attributes value, with any escaped quotes being
			fixed.

			An attribute value may have a boolean value of true, indicating that
			the attribute does not have a value, but simply that it was set, so
			like:

				attr1=myvalue attr2 attr3="another value"

			The array would look something like this:

				array(
					'attr1' => 'myvalue',
					'attr2' => true,
					'attr3' => 'another value',
				)
	*/
	public function attributes()
	{
		// Make sure the tag type is correct.
		if($this->tagType() != 'attribute')
		{
			return null;
		}
		elseif($this->attributes !== null)
		{
			return $this->attributes;
		}
		else
		{
			// Just append one space... This will come in handy.
			$attributes = trim(SpeedyBBC::substr($this->text(), SpeedyBBC::strlen($this->tagName()) + 1, -1)). ' ';

			// Set up a few useful variables.
			$pos = 0;
			$length = SpeedyBBC::strlen($attributes);
			$this->attributes = array();
			while($pos < $length)
			{
				// Keep going until we find no more space.
				$char = SpeedyBBC::substr($attributes, $pos, 1);

				// So, space?
				if($char != ' ')
				{
					// Nope!
					// Go on until we find a space or equal sign.
					$equals = SpeedyBBC::strpos($attributes, '=', $pos);
					$space = SpeedyBBC::strpos($attributes, ' ', $pos);

					// This should be the attribute's name.
					$attr_name = SpeedyBBC::strtolower(SpeedyBBC::substr($attributes, $pos, ($equals === false || $equals > $space ? $space : $equals) - $pos));

					// There should be nothing between the equals sign, if there is one.
					if($equals === false || ($equals > $space && SpeedyBBC::strlen(trim(SpeedyBBC::substr($attributes, $space, $equals - $space))) > 0))
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
					while($pos < $length && SpeedyBBC::substr($attributes, $pos, 1) == ' ')
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
					elseif(($delimiter = SpeedyBBC::substr($attributes, $pos, 6)) == '&quot;' || $delimiter == '&#039;')
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
							$pos = SpeedyBBC::strpos($attributes, $delimiter, $pos);

							// Did we find it?
							if($pos === false)
							{
								// Uh, oh!
								break;
							}
							// There shouldn't be a a backslash escaping it.
							elseif(SpeedyBBC::substr($attributes, $pos - 1, 1) == '\\')
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
							$this->attributes[$attr_name] = rtrim(SpeedyBBC::substr($attributes, $start_pos + 6));

							// Let's get out of this loop, shall we?
							$pos = $length;
						}
						else
						{
							// Just fetch the value between the delimiter, and remove any
							// backslashes escaping a the delimiter in the value.
							$this->attributes[$attr_name] = str_replace('\\'. $delimiter, $delimiter, SpeedyBBC::substr($attributes, $start_pos + 6, $pos - $start_pos - 6));

							// Just move on, a little bit.
							$pos += 6;
						}
					}
					else
					{
						// This is really simple... Thankfully! We will just find the next
						// space, which means the value is over.
						$space = SpeedyBBC::strpos($attributes, ' ', $pos + 1);

						$this->attributes[$attr_name] = trim(SpeedyBBC::substr($attributes, $pos, $space - $pos));

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
	}

	/*
		Method: attribute

		Returns the specified attribute's value.

		Parameters:
			string $name - The name of the attribute.

		Returns:
			mixed - Returns the value of the attribute if it exists, false if the
							attribute does not exist, and null if the tag is not an
							attribute tag.

		Note:
			Attribute names are case insensitive.
	*/
	public function attribute($name)
	{
		if($this->tagType() != 'attribute')
		{
			return null;
		}

		// Get the array of the attributes, we will call on the attributes
		// method, which will parse them out if need be.
		$attributes = $this->attributes();

		// Now return the attribute value, if any.
		return array_key_exists(SpeedyBBC::strtolower($name), $attributes) ? $attributes[SpeedyBBC::strtolower($name)] : false;
	}

	/*
		Method: ignore

		Indicates whether the tag is to be ignored, as in the tag should not be
		parsed, but that doesn't mean any of the child nodes will be ignored as
		well.

		Parameters:
			none

		Returns:
			bool - Returns true if the tag is to be ignored, and false if not.

		Note:
			This is used to determine whether the tag has it's parent/child
			constraints met.
	*/
	public function ignore()
	{
		return $this->ignore;
	}

	/*
		Method: setIgnore

		Sets whether the tag should be ignored.

		Parameters:
			bool $ignore - Whether to ignore the tag.

		Returns:
			void - Nothing is returned by this method.
	*/
	public function setIgnore($ignore)
	{
		$prevIgnore = $this->ignore;
		$this->ignore = !empty($ignore);

		// If we have a closing node, then we should set it's ignore state to
		// reflect this one.
		if($this->closingNode() !== null)
		{
			$this->closingNode()->setIgnore($this->ignore);
		}

		// Do we need to signal to any other nodes that this one has been
		// ignored?
		if($this->ignore && $prevIgnore !== true)
		{
			// Alert the parent node, unless there isn't one, or if it isn't a tag
			// (which shouldn't be a possibility anyways, seeing as text nodes
			// can't have children -- so sad).
			if($this->parentNode() !== null && $this->parentNode()->isTag() && $this->parentNode()->dependsOnChildren())
			{
				// We will want to make sure the parent node has checked it's own
				// constraints... If it already checked them, that's fine, it won't
				// do it more than once.
				$this->parentNode()->checkConstraints();

				$this->parentNode()->setIgnore(true);
			}

			// If the node has no children, then we can't notify them of the
			// ignored status, can we? ;-)
			if($this->childNodesLength > 0)
			{
				// Unlike with the parent node, we can have multiple child nodes,
				// so we will need to loop through and notify each of the child
				// nodes -- if necessary.
				foreach($this->childNodes as $childNode)
				{
					// If the child node doesn't depend on it's parent (this node)
					// then no need to ignore it as well.
					if($childNode->isText() || !$childNode->dependsOnParent())
					{
						// We can ignore children that are text nodes.
						continue;
					}

					// Make sure the constraints have been checked.
					$childNode->checkConstraints();

					$childNode->setIgnore(true);
				}
			}
		}
	}
	/*
		Method: setRequired

		Sets the required parent and child tags for the node.

		Parameters:
			array $parents - An array containing the tag names of required parents
											 in order for this tag to be parsed.
			array $children - An array containing the tag names of required
												children in order for this tag to be parsed.

		Returns:
			bool - Returns true on success, false on failure.
	*/
	public function setRequired($parents, $children)
	{
		if(!is_array($parents) || !is_array($children))
		{
			return false;
		}
		else
		{
			$this->required = array(
													'parents' => $parents,
													'parents_count' => count($parents),
													'children' => $children,
													'children_count' => count($children),
												);

			return true;
		}
	}

	/*
		Method: closingNode

		Returns the object containing the node's closing tag, if any.

		Parameters:
			none

		Returns:
			mixed - Returns the object containing the node's closing tag if it has
							one, but null if not.
	*/
	public function closingNode()
	{
		return $this->closingNode;
	}

	/*
		Method: setClosingNode

		Sets the node's closing node.

		Parameters:
			object $closingNode - The node's closing node (must be an instance of
														<SpeedyTagNode>).

		Returns:
			bool - Returns true if the closing node was set, false if not (such as
						 if the passed object was not a <SpeedyTagNode>, if it wasn't
						 a closing tag, and so on).
	*/
	public function setClosingNode($closingNode)
	{
		// A node cannot have a closing tag if it is a closing tag itself. Also,
		// the passed object must be a SpeedyTagNode, as well as a closing tag.
		if($this->isClosing() || !is_a($closingNode, 'SpeedyTagNode') || !$closingNode->isClosing())
		{
			return false;
		}
		else
		{
			$this->closingNode = $closingNode;

			return true;
		}
	}

	/*
		Method: position

		The position at which the node resides within the linear (array)
		representation of the parsed message.

		Parameters:
			none

		Returns:
			int - Returns the index position at which the node resides within the
						linear structure of the parsed message. However, if this is not
						set, then null is returned.
	*/
	public function position()
	{
		return $this->position;
	}

	/*
		Method: setPosition

		Sets the position at which the node resides within the linear (array)
		representation of the parsed message.

		Parameters:
			int $position - The index position of the node.

		Returns:
			bool - Returns true on success, false on failure.
	*/
	public function setPosition($position)
	{
		if((int)$position < 0)
		{
			return false;
		}
		else
		{
			$this->position = (int)$position;

			return true;
		}
	}

	/*
		Method: dependsOnParent

		Indicates whether the tag represented by the node depends on its parent
		in order to be considered valid.

		Parameters:
			none

		Returns:
			bool - Returns true if the tag requires that the parent node be valid
						 in order for this tag to be considered valid as well.
	*/
	public function dependsOnParent()
	{
		return $this->required['parents_count'] > 0;
	}

	/*
		Method: requiredParents

		Returns an array containing the names of the tag that the node depends
		upon.

		Parameters:
			none

		Returns:
			array - An array containing tag names.
	*/
	public function requiredParents()
	{
		return $this->required['parents'];
	}

	/*
		Method: dependsOnChildren

		Indicates whether the tag represented by the node depends on its
		children in order to be considered valid.

		Parameters:
			none

		Returns:
			bool - Returns true if the tag requires that the children be valid in
						 order for this tag to be considered valid as well.
	*/
	public function dependsOnChildren()
	{
		return $this->required['children_count'] > 0;
	}

	/*
		Method: checkConstraints

		When this method is called the tag node will check all of its set
		constraints. If the constraints are not met, then the tag will be set to
		be ignored when the interpreting occurs within the
		<SpeedyBBC::interpretStruct> method. Not only will this node be ignored
		if the constraints are not met, but the node will notify any other nodes
		that it needs to be ignored if the other nodes depend on this node.

		Parameters:
			none

		Returns:
			void - Nothing is returned by this method.
	*/
	public function checkConstraints()
	{
		if($this->checked || $this->isClosing())
		{
			return;
		}

		// Go ahead and say this node has checked it's constraints -- if we
		// don't before we get going, it could get ugly!
		$this->checked = true;

		// We can't check parent constraints if there aren't any, can we?
		if($this->required['parents_count'] > 0)
		{
			// Checking if the required parent tag is there is straightforward.
			if($this->parentNode() === null || $this->parentNode()->isText() || !in_array($this->parentNode()->tagName(), $this->required['parents']))
			{
				// I guess we will be ignoring this tag then, won't we?
				$this->setIgnore(true);
			}
		}

		// Same goes for child constraints, if there aren't any to check, then
		// there aren't any to check ;-).
		if($this->required['children_count'] > 0)
		{
			// There is a bit more work when it comes to checking the child nodes,
			// but not much.
			if($this->childNodesLength == 0)
			{
				// There are no child nodes, meaning there can be no matches.
				$this->setIgnore(true);
			}
			else
			{
				// Loop through each and every child node and make sure it fits
				// within the tag's constraints.
				foreach($this->childNodes as $childNode)
				{
					// If the child node is text, then we will check for [text],
					// otherwise we will look for the tags name (without brackets).
					if(!in_array($childNode->isText() ? '[text]' : $childNode->tagName(), $this->required['children']))
					{
						$this->setIgnore(true);

						// There is no need to continue to look for any other
						break;
					}
				}
			}
		}

		// We will need to have all the child nodes check their constraints now.
		if($this->childNodesLength > 0)
		{
			foreach($this->childNodes as $childNode)
			{
				// But don't check them if they're a text node... No point!
				if(!$childNode->isText())
				{
					$childNode->checkConstraints();
				}
			}
		}
	}

	/*
		Method: checked

		Indicates whether the node has checked it's constraints yet.

		Parameters:
			none

		Returns:
			bool - Returns true if the node has checked it's constraints, false if
						 not.
	*/
	public function checked()
	{
		return $this->checked || $this->isClosing();
	}

	/*
		Method: applyChildConstraints

		Applies the supplied child constraints to the node's children. The array
		passed is to contain an array of tags which are allowed (or disallowed)
		tags that are only (or not) to be parsed.

		Parameters:
			array $tag_names - An array containing the name of tags to allow or
												 not allow.
			bool $allowed - Whether the tag names in $tag_names are to be allowed
											or disallowed.

		Note:
			Just in case the above is confusing, consider this:

				[url=http://mylink.com]Some text [url=http://anotherlink.com] more
				text[/url] [b]some bold text[/b][/url]

			If $tag_names was array('url') (for the url tag) and $allowed was
			false, then the [url=http://anotherlink.com] tag would not be parsed
			because that tag has been disallowed, however any tag NOT in the
			$tag_names array with $allowed false would continue to be parsed.

			So in this example:

				[b]some bold text [url=mylink]linked text[/url] [i]italicized text
				[/i][/b]

			If $tag_names was array('url') (for [b]) and $allowed was true, then
			only the [url] tag within [b] would be parsed. Everything else would
			not be parsed.
	*/
	public function applyChildConstraints($tag_names, $allowed)
	{
		// Does this tag have any child nodes?
		if($this->childNodesLength() > 0)
		{
			foreach($this->childNodes() as $childNode)
			{
				// Is this not a tag? Then skip it!
				if(!$childNode->isTag())
				{
					continue;
				}

				// Do we need to disable this tag?
				if((!empty($allowed) && !in_array($childNode->tagName(), $tag_names)) || (empty($allowed) && in_array($childNode->tagName(), $tag_names)))
				{
					// Yes, we do. So tell the node to have itself ignored, which will
					// then mark it's closing node as ignored too.
					$childNode->setIgnore(true);
				}

				// Now the child needs to apply the constraints as well.
				$childNode->applyChildConstraints($tag_names, $allowed);
			}
		}
	}
}
?>