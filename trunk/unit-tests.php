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
									'name' => 'Font color (hex)',
									'bbc' => '[color=#cc0000]Text colored in #cc0000[/color]',
									'html' => '<span style="color: #cc0000 !important;">Text colored in #cc0000</span>',
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
								'Tables',
								array(
									'name' => 'Columns',
									'bbc' => '[columns]This is the first column[next]This is the second[next]... and this is the third[/columns]',
									'html' => '<table><tr><td>This is the first column</td><td>This is the second</td><td>... and this is the third</td></tr></table>',
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
		<p>The following are the results of the BBCode test cases.</p>';

@set_time_limit(5);
require('speedybbc.class.php');
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
		<h2>Test Results</h2>
		<p>Total tests: ', number_format($total_tests), '</p>
		<p>Tests passed: <span class="pass">', number_format($total_passed), '</span> (', ($percent = round(((double)$total_passed / $total_tests) * 100, 2)), '%)</p>
		<p>Tests failed: <span class="fail">', number_format($total_tests - $total_passed), '</span> (', (100 - $percent), '%)</p>
		<p>Tests completed in: ', round($completed, 5), ' seconds.</p>
	</div>
</body>
</html>';