---
<span>This page is up-to-date as of <a href='https://code.google.com/p/speedybbc/source/detail?r=11'>r11</a> of the SpeedyBBC parser.</span>
---

# Supported Tags #

This page lists the currently supported BBCode tags which come built-in with the SpeedyBBC parser.

### Basic Tags ###

**`[b]` - Bold** - Bolds the surrounded text with `<strong>`.

**`[i]` - Italics** - Italicizes the surrounded text with `<em>`.

**`[u]` - Underline** - Underlines the surrounded text with `<span style="text-decoration: underline !important;">`.

**`[s]` - Strike-through** - Puts a strike through the surrounded text with `<del>`.

**`[sup]` - Superscript** - Makes a superscript with `<sup>`.

**`[sub]` - Subscript** - Makes a subscript with `<sub>`.

**`[nobbc]` - No BBC parsing** - When text is surrounded with `[nobbc]...[/nobbc]` the text will not be parsed (including links and smileys) and will appear as-is.

### Alignment Tags ###

**`[left]` - Left** - Aligns the surrounded text to the left using `<span style="text-align: left !important;">`.

**`[center]` - Center** - Aligns the surrounded text to the center using `<span style="text-align: center !important;">`.

**`[right]` - Right** - Aligns the surrounded text to the right using `<span style="text-align: right !important;">`.

**`[justify]` - Justify** - Justifies the surrounded text using `<span style="text-align: justify !important;">`.

**`[align=left|center|right|justify]` - Align** - Aligns the surrounded text with the specified value. This is simply an alternative to the alignments above.

### Font Tags ###

**`[size=0-7]` - Font size** - Changes the font size for the surrounded text. 0 will become 0.5em, 1 will become 0.67em, then 0.83em, 1em, 1.17em, 1.5em, 2em and 7 will become 2.5em.

**`[size=1-1000(pt|px)]` - Font size (pt or px)** - Changes the font size for the surrounded text, ranging from 1 to 1000 in pt (point) or px.

**`[color=hex]` - Color** - Changes the fonts color, using hex codes (#000-#FFF as well as #000000-#FFFFFF).

**`[color=rgb(0-255, 0-255, 0-255)]` or `[color=rgba(0-255, 0-255, 0-255, 0-255)]` - Color** - Changes the fonts color, using RGB values. The second option allows alpha transparency.

**`[color=name]` - Color** - Changes the fonts color, using the name of the color. The names of supported colors are those listed under Wikipedia's [X11 color names](http://en.wikipedia.org/wiki/Web_colors#X11_color_names).

**`[font=name]` - Font family** - Changes the font family, which can be: Arial, Helvetica, Arial Black, Comic Sans MS, Courier New, Impact, Lucida Console, Monaco, Tahoma, Geneva, Times New Roman, Trebuchet MS, Verdana, Symbol, and Georgia. Multiple font names are to be separated by commas.

### Links ###

**`[url]{url}[/url]` - Hyperlink** - Links the {url} to {url}, see next tag for supported URL's.

**`[url={url}]` - Hyperlink** - Links the text to the specified location, supporting http://, https://, ftp:// and ftps://

**`[iurl]{url}[/iurl]` - Internal Hyperlink** - Links {url} to {url}, but instead of opening the location in a new tab/window it will open in the current window.

**`[iurl={url}]` - Internal Hyperlink** - Links the text to the specified location, but in the current window instead of a new tab/window.

**`[email]{email}[/email]` - Email** - Links the specified {email} address with a mailto: URL.

**`[email={email}]` - Email** - Links the specified text to [mailto:{email](mailto:{email)}.

### Images ###

**`[img]{image}[/img]` - Image** - Turns the specified {image} into a displayed image, only supports http:// and https:// locations.

**`[img width={width} height={height}]{image}[/img]` - Image** - Turns the specified {image} into a display image, only supports http:// and https:// locations, and also specifies the width and height attributes within the `<img />` tag.

### Lists ###

**`[list]` - Unordered list** - Creates the start of an unordered list, requires `[li]` tags as children.

**`[olist]` - Ordered list** - Creates the start of an ordered list, requires `[li]` tags as children.

**`[li]` - List item** - An item within a list, requires `[list]` or `[olist]` as a parent.

### Tables ###

**`[columns]` - Columns** - Creates a basic table with one row, to create another column, use `[next]`.

**`[next]` - Create column** - Creates a new column within `[columns]`.

**`[table]` - Table** - Creates a table, requires `[tr]` as a child.

**`[tr]` - Table row** - Creates a table row within a `[table]`, this is a required child of `[table]` and this tag requires `[td]` as children.

**`[td]` - Table column** - Creates a column within a `[tr]`.

### Miscellaneous ###

**`[acronym=Full Name]` or `[abbr=Full Name]` - Abbreviation** - Adds `<abbr title="Full Name">` tags around the specified text.

**`[hr]` - Horizontal Rule** - Creates a horizontal rule (`<hr />`).

**`[br]` - Link Break** - Creates a line break (`<br />`).