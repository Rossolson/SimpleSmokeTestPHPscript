#!/usr/bin/php  
<?php  

/* Primary Class Definition */

Class SmokeTest {
	private function collectURLs($dom, $tag, $attribute, $url) {
		global $UI;
		$collection = array();
		$tags = $dom->getElementsByTagName($tag);
		foreach ($tags as $element) {
			$target = "";
			$href = $element->getAttribute($attribute);
			if ($DEBUG) { echo 'DEBUG: '.__line__.' :: $tag : '.$tag. " - '" .$href."'\n"; }
			if (isset($href) && $href !== '') {
				if (extension_loaded('http')) {
					$path = '/' . ltrim($href, '/');
					$target = http_build_url($url, array('path' => $path));
				} else {
					$parts = parse_url($href);
					$root = parse_url($url);
					if (isset($parts['scheme'])) {
						$target .= $parts['scheme'] . '://';
					} else {
						$target .= $root['scheme'] . '://';
					}
					if ($DEBUG) { echo 'DEBUG: '.__line__.' ::scheme '.$target."\n"; }
					if (isset($parts['user']) && isset($parts['pass'])) {
						$target .= $parts['user'] . ':' . $parts['pass'] . '@';
					}
					if ($DEBUG) { echo 'DEBUG: '.__line__.' ::u/p '.$target."\n"; }
					if (isset($parts['host'])) {
						$target .= $parts['host'];
					} else {
						$target .= $root['host'];
					}
					if ($DEBUG) { echo 'DEBUG: '.__line__.' ::host '.$target."\n"; }
					if (isset($parts['port'])) {
						$target .= ':' . $parts['port'];
					}
					if ($DEBUG) { echo 'DEBUG: '.__line__.' ::port '.$target."\n"; }
					if (isset($parts['path'])) {
						$target .= $parts['path'];
					} else {
						//$target .= $root['path'];
					}
					if ($DEBUG) { echo 'DEBUG: '.__line__.' ::path '.$target."\n"; }
					if ($DEBUG) { echo "\n"; }
				}
			}
			if (isset($target) && $target !== '')
				$collection[] = $target;
		}
		return $collection;
	}

	private function testThis($url, $verbose) {
		global $UI;
		$status = exec("curl --silent --head '". $url ."' | head -1 | cut -f 2 -d' '");
		if ($verbose) {
			echo $UI[SOL] . "Testing: ". $url . $UI[NEWLINE];
			echo $status !== '' && $status < 400 ?  $UI[GREENSTART] . " PASS " . $UI[GREENSTOP] . " Status ". $status ."\n" : $UI[REDSTART] . " FAIL " . $UI[REDSTOP] . " Status ". $status ." for ". $url . $UI[EOL];
		} else {
			if ($status !== '' && $status < 400) {
				// Green '.' (period)
				echo $UI[GREENSTART] . "." . $UI[GREENSTOP];
				if ($DEBUG) { $this->errors[] = $UI[SOL] . $UI[GREENSTART] . " PASS ". $UI[GREENSTOP] ." Status ". $status ." for ". $url  . $UI[EOL]; }
			} else {
				// Red 'x'
				echo $UI[REDSTART] . "x" . $UI[REDSTOP];
				$this->errors[] = $UI[SOL] . $UI[REDSTART] . " FAIL ". $UI[REDSTOP] . " Status ". $status ." for ". $url  . $UI[EOL];
			}
		}
		if ($UI[ui] == "web") {
			// echo str_repeat('    ', 500);
			ob_flush();
		}
	}

	public function run($url = null, $include, $verbose = false) {
		global $UI;
	
		if ($url === null) { return; }
		$this->errors = array();
		$this->testThis($url, $verbose);
		$dom = new DOMDocument('1.0');
		@$dom->loadHTMLFile($url);

		$scripts =  in_array('script', $include) ? $this->collectURLs($dom, 'script', 'src',  $url) : array();
		$links =    in_array('link',   $include) ? $this->collectURLs($dom, 'link',   'href', $url) : array();
		$imgs =     in_array('img',    $include) ? $this->collectURLs($dom, 'img',    'src',  $url) : array();
		$as =       in_array('a',      $include) ? $this->collectURLs($dom, 'a',      'href', $url) : array();
		$areas =    in_array('area',   $include) ? $this->collectURLs($dom, 'area',   'href', $url) : array();
		$collection = array_merge($scripts, $links, $imgs, $as, $areas);

		foreach ($collection as $url) {
			$this->testThis($url, $verbose);
		}
		if (!$verbose) {
			if (count($this->errors) === 0)
				echo $UI[SOL] . $UI[GREENSTART] . "YAY! All tests passed OK!" . $UI[GREENSTOP] . $UI[EOL];
			else {
				echo $UI[SOL] . $UI[REDSTART] . "There were errors!" . $UI[REDSTOP] . $UI[NEWLINE];
				foreach ($this->errors as $error) {
					echo $error;
				}
				echo $UI[NEWLINE] . $UI[REDSTOP] . "Errors: " . count($this->errors) . $UI[REDSTOP] . $UI[EOL];
			}
		}
	}
}


/*
	Script Processing Begins
*/

$php_type = php_sapi_name();

if ($php_type == "cli") {
	$arguments = getopt('u:i:vh');
	$UI[ui] = "cli";
	$UI[SOL] = "\n\n";
	$UI[EOL] = "\n\n";
	$UI[NEWLINE] = "\n";
	$UI[GREENSTART] = "\033[0;32m";
	$UI[GREENSTOP] = "\033[0m";
	$UI[REDSTART] = "\033[0;31m";
	$UI[REDSTOP] = "\033[0m";
	$UI[LT] = "<";
	$UI[GT] = ">";
} else {
	if (isset($_GET["u"])) { $arguments['u'] = htmlspecialchars($_GET["u"]); }
	if (isset($_GET["h"])) { $arguments['h'] = htmlspecialchars($_GET["h"]); }
	if (isset($_GET["v"]) && $_GET["v"] !== "") { $arguments['v'] = htmlspecialchars($_GET["v"]); }
	if (isset($_GET["i"])) { $arguments['i'] = explode(" ", htmlspecialchars(implode(" ", $_GET["i"]))); }
	$UI[ui] = "web";
	$UI[SOL] = "<pre>";
	$UI[NEWLINE] = "<br>";
	$UI[EOL] = "</pre>";
	$UI[GREENSTART] = "<span style='color: green;'>";
	$UI[GREENSTOP] = "</span>";
	$UI[REDSTART] = "<span style='color: red;'>";
	$UI[REDSTOP] = "</span>";
	$UI[LT] = "&lt;";
	$UI[GT] = "&gt;";
}

//$arguments = getopt('u:i:vh');

/*
	If the checking isn't activated, these default messages will be displayed.
*/

if (!isset($arguments['u']) || isset($arguments['h']) || isset($arguments['H'])) {
	if ($UI[ui] == "web") {

echo <<< ENDOFHTML

		<style>
		* {line-height: 1.5;}
		form {margin: auto; width: 30em;}
		dt, dd { border-top: solid lightgrey 1px; padding: .5em 0;}
		dt { float: left; clear: both; width: 6em;}
		dd { margin-left: 6.2em;}
		</style>
		<form action="smokeTest.php" method="GET" name="smoketest" id="smoketest">
		<dl>
			<dt><label for="u">URL:</label></dt>
				<dd><input name="u" value="http://www.example.com/" size="40"></dd>

			<dt><label for="v">Elements:</label></dt>
				<dd>
					<input type="checkbox" name="i[]" checked="checked" value="a">A HREF<br />
					<input type="checkbox" name="i[]" checked="checked" value="link">Link<br />
					<input type="checkbox" name="i[]" checked="checked" value="script">Script<br />
					<input type="checkbox" name="i[]" checked="checked" value="img">Images<br />
					<input type="checkbox" name="i[]" checked="checked" value="area">Area<br />
				</dd>

			<dt><label for="v">Verbose:</label></dt>
				<dd><input type="checkbox" name="v" value="true">Yes</dd>

			<dt>&nbsp;</dt>
				<dd><input type="submit" name="submit"></dd>
		</dl>
		</form>

ENDOFHTML;

	} else {

echo <<< ENDOFTEXT

smokeTest.php help:

-u <URL to test>  Required. URL to check.
-i [tag]          Optional. HTML tag to look for child URLs to test. Specify one -i for each tag or none for all.
                            Tags: script, link, img, a, area
-v                Optional. Verbose output
-h                Optional. Shows this help message

CLI Usage: ./smokeTest.php -u $UI[LT]URL to test$UI[GT] [-i a] [-i link] [-i img]  [-i script]  [-i area] [-v]
Web Usage: http://<domain>/<path>/smokeTest.php
 

ENDOFTEXT;
	}
	die();
}

$url = $arguments['u'];
if(isset($arguments['i'])) {
	if(!is_array($arguments['i'])) {
		$include[] = $arguments['i'];
	} else {
		$include = $arguments['i'];
	}
}


$argh = new SmokeTest;

$argh->run($url, isset($arguments['i']) ? $include : array('script', 'link', 'img', 'a', 'area'), isset($arguments['v']) ? true : false);
