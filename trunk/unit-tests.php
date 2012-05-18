<?php
/*
	This file is used to test all of the built-in BBCode tags along with
	making sure other components of the parser are working properly.
*/
$test_cases = array(
								'Basic tags',
								array(
									'name' => 'Bold',
									'bbc' => '[b]This ought to be bold!!! :-)[/b]',
									'html' => '<strong>This ought to be bold!!! :-)</strong>',
								),
								array(
									'name' => 'Italics',
									'bbc' => '[i]This ought to be italicized!!![/i]',
									'html' => '<em>This ought to be italicized!!!</em>',
								),
								array(
									'name' => 'Underline',
									'bbc' => '[u]Underlined...[/u]',
									'html' => '<span style="text-decoration: underline !important;">Underlined...</span>',
								),
								array(
									'name' => 'Strikethrough',
									'bbc' => '[s]Deleted![/s]',
									'html' => '<del>Deleted!</del>',
								),
								array(
									'name' => 'Superscript',
									'bbc' => '1[sup]st[/sup] 2[sup]nd[/sup] 3[sup]rd[/sup]',
									'html' => '1<sup>st</sup> 2<sup>nd</sup> 3<sup>rd</sup>',
								),
								array(
									'name' => 'Subscript',
									'bbc' => 'x[sub]0[/sub] x[sub]1[/sub] x[sub]2[/sub]',
									'html' => 'x<sub>0</sub> x<sub>1</sub> x<sub>2</sub>',
								),
								array(
									'name' => 'No BBCode Parsing',
									'bbc' => '[nobbc][b]This should not be parsed, [i]at all![/i][/b][/nobbc] [b]But this should be![/b]',
									'html' => '[b]This should not be parsed, [i]at all![/i][/b] <strong>But this should be!</strong>',
								),
								'Alignment',
								array(
									'name' => 'Left',
									'bbc' => '[left]This text is off to the left![/left]',
									'html' => '<span style="text-align: left !important;">This text is off to the left!</span>',
								),
								array(
									'name' => 'Center',
									'bbc' => '[center]Now to the center...[/center]',
									'html' => '<span style="text-align: center !important;">Now to the center...</span>',
								),
								array(
									'name' => 'right',
									'bbc' => '[right]Last, but not least, the right... yay[/right]',
									'html' => '<span style="text-align: right !important;">Last, but not least, the right... yay</span>',
								),
								array(
									'name' => 'justify',
									'bbc' => '[justify]This text is justified![/justify]',
									'html' => '<span style="text-align: justify !important;">This text is justified!</span>',
								),
								array(
									'name' => '[align] tag',
									'bbc' => '[align=center]I aligned this to the center, I hope.[/align]',
									'html' => '<span style="text-align: center !important;">I aligned this to the center, I hope.</span>',
								),
								'Tags with values (equal signs)',
								array(
									'name' => 'Abbreviation',
									'bbc' => '[abbr=PHP: Hypertext Preprocessor]PHP[/abbr]',
									'html' => '<abbr title="PHP: Hypertext Preprocessor" class="bbcode-acronym">PHP</abbr>',
								),
								array(
									'name' => 'Font size',
									'bbc' => '[size=7]This is big text[/size]',
									'html' => '<span style="font-size: 2.5em !important;">This is big text</span>',
								),
								array(
									'name' => 'Font size (px)',
									'bbc' => '[size=50px]50px text[/size]',
									'html' => '<span style="font-size: 50px !important;">50px text</span>',
								),
								array(
									'name' => 'Font size',
									'bbc' => '[size=13pt]This is in a 13 point font[/size]',
									'html' => '<span style="font-size: 13pt !important;">This is in a 13 point font</span>',
								),
								array(
									'name' => 'Font color (hex)',
									'bbc' => '[color=#cc0000]Text colored in #cc0000[/color]',
									'html' => '<span style="color: #cc0000 !important;">Text colored in #cc0000</span>',
								),
								array(
									'name' => 'Font color (rgb)',
									'bbc' => '[color=rgb(255, 100, 50)]This text is colored with rgb(255, 100, 50)[/color]',
									'html' => '<span style="color: rgb(255, 100, 50) !important;">This text is colored with rgb(255, 100, 50)</span>',
								),
								array(
									'name' => 'Font color (rgb percent)',
									'bbc' => '[color=rgb(100%, 50%, 0%)]This text is colored with rgb(100%, 50%, 0%)[/color]',
									'html' => '<span style="color: rgb(100%, 50%, 0%) !important;">This text is colored with rgb(100%, 50%, 0%)</span>',
								),
								array(
									'name' => 'Font color (rgba)',
									'bbc' => '[color=rgba(255, 100, 50, 0.5)]This text is colored with rgba(255, 100, 50, 0.5)[/color]',
									'html' => '<span style="color: rgba(255, 100, 50, 0.5) !important;">This text is colored with rgba(255, 100, 50, 0.5)</span>',
								),
								array(
									'name' => 'Font color (rgba percent)',
									'bbc' => '[color=rgba(100%, 50%, 0%, 0.5)]This text is colored with rgba(100%, 50%, 0%, 0.5)[/color]',
									'html' => '<span style="color: rgba(100%, 50%, 0%, 0.5) !important;">This text is colored with rgba(100%, 50%, 0%, 0.5)</span>',
								),
								array(
									'name' => 'Font color (name)',
									'bbc' => '[color=red]Red text...[/color]',
									'html' => '<span style="color: red !important;">Red text...</span>',
								),
								array(
									'name' => 'Font family',
									'bbc' => '[font=Verdana]I like Verdana :-)[/font]',
									'html' => '<span style="font-family: Verdana !important;">I like Verdana :-)</span>',
								),
								array(
									'name' => 'Font family (with fallback)',
									'bbc' => '[font=Verdana, Tahoma, Arial]Verdana or Tahoma (maybe even Arial)...[/font]',
									'html' => '<span style="font-family: Verdana, Tahoma, Arial !important;">Verdana or Tahoma (maybe even Arial)...</span>',
								),
								'Links',
								array(
									'name' => 'Link',
									'bbc' => '[url=http://www.bing.com/]It\'s a link alright![/url]',
									'html' => '<a href="http://www.bing.com/" target="_blank">It&#039;s a link alright!</a>',
								),
								array(
									'name' => 'Link (iurl)',
									'bbc' => '[iurl=http://www.bing.com/]It\'s a link alright![/iurl]',
									'html' => '<a href="http://www.bing.com/">It&#039;s a link alright!</a>',
								),
								array(
									'name' => 'Just a link',
									'bbc' => '[url]http://www.bing.com/[/url]',
									'html' => '<a href="http://www.bing.com/" target="_blank">http://www.bing.com/</a>',
								),
								array(
									'name' => 'Email',
									'bbc' => '[email]me@example.com[/email]',
									'html' => '<a href="mailto:me@example.com" target="_blank">me@example.com</a>',
								),
								array(
									'name' => 'Text with linked email',
									'bbc' => '[email=me@example.com]Email me!!![/email]',
									'html' => '<a href="mailto:me@example.com" target="_blank">Email me!!!</a>',
								),
								'Images',
								array(
									'name' => 'Image',
									'bbc' => '[img]http://www.example.com/some-image.jpg[/img]',
									'html' => '<img src="http://www.example.com/some-image.jpg" alt="" />',
								),
								array(
									'name' => 'Image with attributes',
									'bbc' => '[img width=100 height=200]http://www.example.com/some-image.jpg[/img]',
									'html' => '<img src="http://www.example.com/some-image.jpg" width="100" height="200" alt="" />',
								),
								array(
									'name' => 'Image w/attributes (optional, only width)',
									'bbc' => '[img width=650]http://www.example.com/some-image.jpg[/img]',
									'html' => '<img src="http://www.example.com/some-image.jpg" width="650" alt="" />',
								),
								'Empty tags',
								array(
									'name' => 'Horizontal rule',
									'bbc' => 'Some text [hr] Separated by a horizontal line!',
									'html' => 'Some text <hr /> Separated by a horizontal line!',
								),
								array(
									'name' => 'Line break',
									'bbc' => 'Just [br] a [br] line [br] break',
									'html' => 'Just <br /> a <br /> line <br /> break',
								),
								'Lists',
								array(
									'name' => 'Unordered list',
									'bbc' => '[list][li]This is an unordered list...[/li][li]... and another item[/li][/list]',
									'html' => '<ul><li>This is an unordered list...</li><li>... and another item</li></ul>',
								),
								array(
									'name' => 'Ordered list',
									'bbc' => '[olist][li]This is an ordered list...[/li][li]... and another item[/li][/olist]',
									'html' => '<ol><li>This is an ordered list...</li><li>... and another item</li></ol>',
								),
								array(
									'name' => 'No [list] test',
									'bbc' => '[li]I am trying to make an unordered list without the proper parent tag![/li]',
									'html' => '[li]I am trying to make an unordered list without the proper parent tag![/li]',
								),
								'Tables',
								array(
									'name' => 'Columns',
									'bbc' => '[columns]This is the first column[next]This is the second[next]... and this is the third[/columns]',
									'html' => '<table><tr><td>This is the first column</td><td>This is the second</td><td>... and this is the third</td></tr></table>',
								),
								'Security',
								array(
									'name' => '[url] JavaScript injection test #1',
									'bbc' => '[url]javascript:alert(\'Gotcha!\');[/url]',
									'html' => '[url]javascript:alert(&#039;Gotcha!&#039;);[/url]',
								),
								array(
									'name' => '[url] JavaScript injection test #2',
									'bbc' => '[url=javascript:alert(\'Gotcha!\');]Click here![/url]',
									'html' => '[url=javascript:alert(&#039;Gotcha!&#039;);]Click here![/url]',
								),
								array(
									'name' => '[iurl] JavaScript injection test #1',
									'bbc' => '[iurl]javascript:alert(\'Gotcha!\');[/iurl]',
									'html' => '[iurl]javascript:alert(&#039;Gotcha!&#039;);[/iurl]',
								),
								array(
									'name' => '[iurl] JavaScript injection test #2',
									'bbc' => '[iurl=javascript:alert(\'Gotcha!\');]Click here![/iurl]',
									'html' => '[iurl=javascript:alert(&#039;Gotcha!&#039;);]Click here![/iurl]',
								),
								array(
									'name' => 'Email validation test #1',
									'bbc' => '[email]myinvalid@yay[/email]',
									'html' => '[email]myinvalid@yay[/email]',
								),
								array(
									'name' => 'Email validation test #2',
									'bbc' => '[email]almostvalid.@hotmail.com[/email]',
									'html' => '[email]almostvalid.@hotmail.com[/email]',
								),
								array(
									'name' => 'Email validation test #3',
									'bbc' => '[email=myinvalid@yay]Email me...[/email]',
									'html' => '[email=myinvalid@yay]Email me...[/email]',
								),
								array(
									'name' => 'URL validation',
									'bbc' => '[url=http://]Click this not valid link...[/url]',
									'html' => '[url=http://]Click this not valid link...[/url]',
								),
								array(
									'name' => 'Image URL validation',
									'bbc' => '[img]http://[/img]',
									'html' => '[img]http://[/img]',
								),
								'Auto Replacements',
								array(
									'name' => 'Auto &lt;br /&gt;',
									'bbc' => 'Look'. "\r\n". 'another'. "\r\n". 'line!!!',
									'html' => 'Look<br />another<br />line!!!',
								),
								'Correction',
								array(
									'name' => 'Fix improperly nested tags',
									'bbc' => '[b]Haha... I invalidated [i]your page![/b][/i]',
									'html' => '<strong>Haha... I invalidated <em>your page!</em></strong>[/i]',
								),
								array(
									'name' => 'Close all opened tags',
									'bbc' => '[b]I bolded the rest of this page...',
									'html' => '<strong>I bolded the rest of this page...</strong>',
								),
								array(
									'name' => 'Wrong children',
									'bbc' => '[table][tr]There is no td tag...[/tr][/table]',
									'html' => '[table][tr]There is no td tag...[/tr][/table]',
								),
								'Disallowed/Allowed Constraints',
								array(
									'name' => 'Disallowed children',
									'bbc' => '[url=http://www.bing.com][url=http://www.bing.com]A link inside a link[/url]![/url]',
									'html' => '<a href="http://www.bing.com" target="_blank">[url=http://www.bing.com]A link inside a link[/url]!</a>',
								),
							);

echo '<!DOCTYPE html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>BBCode Testing</title>
	<style>
		* { margin: 0; padding: 0; }

		body
		{
			background: #F0F0F0;
			font-family: Verdana, Tahoma, sans-serif;
			font-size: 13px;
		}

		#test-cases
		{
			width: 650px;
			margin: 10px auto;
			padding: 10px;
			background: white;
			border: 1px solid #DDDDDD;
		}

		#test-cases h1, h2, h3
		{
			font-family: Cambria, Georgia, "Times New Roman", Times, serif;
			font-weight: normal;
		}

		#test-cases h2, h3
		{
			margin-top: 15px;
		}

		.pass { color: green; font-weight: bold; }
		.fail { color: red; font-weight: bold; }
		.border-bottom { padding-bottom: 15px; border-bottom: 1px solid black; }

		p { margin-top: 5px; }
		code { display: block; margin: 10px 5px; background: #F0F0F0; overflow: auto; padding: 5px; }
	</style>
</head>
<body>
	<div id="test-cases">
		<h1>BBCode Testing</h1>
		<p>The following are the results of the BBCode test cases. <a href="#results">Jump to results</a>.</p>';

@set_time_limit(5);
require('speedybbc-redux.class.php');
$bbc = new SpeedyBBC();
$start_time = microtime(true);
$total_tests = 0;
$total_passed = 0;
foreach($test_cases as $test)
{
	if(!is_array($test))
	{
		echo '
			<h2>Category: ', $test, '</h2>';
	}
	else
	{
		echo '
			<h3>', $test['name'], '</h3>
			<p>Input:</p>
			<code>
				', htmlspecialchars($test['bbc'], ENT_QUOTES), '
			</code>

			<p>Expected output:</p>
			<code>
				', htmlspecialchars($test['html'], ENT_QUOTES), '
			</code>

			<p>Actual output:</p>
			<code>
				', htmlspecialchars($parsed = $bbc->parse($test['bbc']), ENT_QUOTES), '
			</code>

			<p class="border-bottom">Result: ', $parsed == $test['html'] ? '<span class="pass">PASS</span>' : '<span class="fail">FAIL</span>', '</p>';

		// Just some statistics.
		$total_tests++;

		if($parsed == $test['html'])
		{
			$total_passed++;
		}
	}
}

$completed = microtime(true) - $start_time;

echo '
		<a name="results"></a>
		<h2>Test Results</h2>
		<p>Total tests: ', number_format($total_tests), '</p>
		<p>Tests passed: <span class="pass">', number_format($total_passed), '</span> (', ($percent = round(((double)$total_passed / $total_tests) * 100, 2)), '%)</p>
		<p>Tests failed: <span class="fail">', number_format($total_tests - $total_passed), '</span> (', (100 - $percent), '%)</p>
		<p>Tests completed in: ', round($completed, 5), ' seconds.</p>
	</div>
</body>
</html>';