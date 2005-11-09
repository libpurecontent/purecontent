<?php

/*
 * Coding copyright Martin Lucas-Smith, University of Cambridge, 2003-5
 * Version 1.13
 * Distributed under the terms of the GNU Public Licence - www.gnu.org/copyleft/gpl.html
 * Requires PHP 4.1+ with register_globals set to 'off'
 * Download latest from: http://download.geog.cam.ac.uk/projects/purecontent/
 */


# Clean the server globals: this is the ONE exception to the rule that a library should not run things at top-level
pureContent::cleanServerGlobals ();


# Define a class containing website generation static methods
class pureContent {
	
	# Function to clean and standardise server-generated globals
	function cleanServerGlobals ($directoryIndex = 'index.html')
	{
		# Assign the server root path, non-slash terminated
		$_SERVER['DOCUMENT_ROOT'] = ((substr ($_SERVER['DOCUMENT_ROOT'], -1) == '/') ? substr ($_SERVER['DOCUMENT_ROOT'], 0, -1) : $_SERVER['DOCUMENT_ROOT']);
		
		# Assign the server root path
		// $_SERVER['SCRIPT_FILENAME'];
		
		# Assign the domain name
		// $_SERVER['SERVER_NAME'];
		
		# Assign the page location (i.e. the actual script opened), with index.html removed if it exists, starting from root
		$_SERVER['PHP_SELF'] = ereg_replace ("/$directoryIndex\$", '/', $_SERVER['PHP_SELF']);
		
		# Assign the page location (i.e. the page address requested) with query, removing double-slashes and the directory index
		$currentPath = ereg_replace ("/$directoryIndex\$", '/', $_SERVER['REQUEST_URI']);
		while (strpos ($currentPath, '//') !== false) {$currentPath = str_replace ('//', '/', $currentPath);}
		$_SERVER['REQUEST_URI'] = $currentPath;
		
		# Assign the current server protocol type and version
		list ($protocolType, $_SERVER['_SERVER_PROTOCOL_VERSION']) = explode ('/', $_SERVER['SERVER_PROTOCOL']);
		$_SERVER['_SERVER_PROTOCOL_TYPE'] = strtolower ($protocolType);
		
		# Assign the site URL
		$_SERVER['_SITE_URL'] = $_SERVER['_SERVER_PROTOCOL_TYPE'] . '://' . $_SERVER['SERVER_NAME'];
		
		# Assign the complete page URL (i.e. the full page address requested), with index.html removed if it exists, starting from root
		$_SERVER['_PAGE_URL'] = $_SERVER['_SITE_URL'] . $_SERVER['REQUEST_URI'];
		
		#!# Needs further work
		// $_SERVER['SCRIPT_URL'];
		
		# Assign the query string (for the few cases, e.g. a 404, where a REDIRECT_QUERY_STRING is generated instead
		$_SERVER['QUERY_STRING'] = (isSet ($_SERVER['REDIRECT_QUERY_STRING']) ? $_SERVER['REDIRECT_QUERY_STRING'] : $_SERVER['QUERY_STRING']);
		
		# Assign the referring page
		$_SERVER['HTTP_REFERER'] = (isSet ($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
		
		# Assign the user's IP address
		// $_SERVER['REMOTE_ADDR'];
		
		# Assign the username
		$_SERVER['REMOTE_USER'] = (isSet ($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'] : (isSet ($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : NULL));
		
		# Assign the user's browser
		$_SERVER['HTTP_USER_AGENT'] = (isSet ($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
	}
	
	
	/**
	 * Creates a navigation trail, assign a browser title and the correct menu, based on the present URL
	 *
	 * @param string $dividingTextOnPage		// The text which goes between each link on the page
	 * @param string $dividingTextInBrowserLine	// The text between each link in the browser title bar; examples are &raquo; | || -  &#187; ; note that \ / < > shouldn't be used (they won't be bookmarked on Windows)
	 * @param string $introductoryText			// What appears at the start of the line
	 * @param string $homeText					// Text for the first link: to the home page
	 * @param bool $enforceStrictBehaviour		// Whether to allow missing title files within the hierarchy
	 * @param bool $browserlineFullHierarchy	// Whether to show the whole hierarchy or only the last one in the browser title bar
	 * @param string $homeLocation				// The location of the home page (the first link) starting from / [trailing slash here is optional]
	 * @param string $sectionTitleFile			// The filename for the section information placed in each directory
	 * @param string $menuTitleFile				// The filename for the submenu placed in each top-level directory
	 */
	function assignNavigation ($dividingTextOnPage = ' &#187; ', $dividingTextInBrowserLine = ' &#187; ', $introductoryText = 'You are in:  ', $homeText = 'Home', $enforceStrictBehaviour = false, $browserlineFullHierarchy = false, $homeLocation = '/', $sectionTitleFile = '.title.txt', $menuTitleFile = '.menu.html', $tildeRoot = '/home/', $behaviouralHackFile = '')
	{
		# Ensure the home location and tilde root ends with a trailing slash
		if (substr ($homeLocation, -1) != '/') {$homeLocation .= '/';}
		if (substr ($tildeRoot, -1) != '/') {$tildeRoot .= '/';}
		
		# Clean up the current page location
		$currentPath = ereg_replace ("^$homeLocation", '', $_SERVER['REQUEST_URI']);
		$currentPath = str_replace ('../', '', $currentPath);
		while (strpos ($currentPath, '//') !== false) {$currentPath = str_replace ('//', '/', $currentPath);}
		
		# Create an array of the subdirectories of it, chopping off the last item (something.html or empty)
		$subdirectories = explode ('/', $currentPath);
		array_pop ($subdirectories);
		
		# Set a flag for being a tilde site
		$tildeSite = (substr ($homeLocation, 0, 2) == '/~');
		
		# Set the root of the site
		$serverRoot = (!$tildeSite ? $_SERVER['DOCUMENT_ROOT'] : $tildeRoot . substr ($homeLocation, 2) . 'public_html/');
		
		# If there are no subdirectories, assign the results immediately
		if (empty ($subdirectories)) {
			$browserline = '';
			$locationline = '&nbsp;';
			$menusection = '';
			$menufile = '';
		} else {
			
			# Start the location line and browserline
			$locationline = str_replace ('  ', '&nbsp; ', $introductoryText) . "<a href=\"$homeLocation\">$homeText</a>";
			$browserline = '';
			
			# Assign the starting point for the links
			$link = (!$tildeSite ? $homeLocation : '');
			
			# Go through each subdirectory and assign the text and link
			foreach ($subdirectories as $subdirectory) {
				
				# Prepend the previous subdirectory and append a trailing slash to make the link
				$link .= $subdirectory . '/';
				
				# Extract the text from the 'title' file
				$filename = $serverRoot . $link . $sectionTitleFile;
				
				# Check whether the file exists; stop the loop if strict hierarchy mode is on
				if (!file_exists ($filename)) {
					if ($enforceStrictBehaviour) {break;}
				} else {
					
					# Obtain the contents of the file
					$fileHandle = fopen ($filename, 'r');
					$contents = fread ($fileHandle, filesize ($filename));
					fclose ($fileHandle);
					
					# Trim white space and convert HTML entities
					$contents = htmlentities (trim ($contents));
					
					# Build up the text and links in the location line, preceeded by the dividing text
					$locationline .= $dividingTextOnPage . '<a href="' . ($tildeSite ? $homeLocation : '') . $link . '">' . $contents . '</a>';
					
					# Build up the text for the browser title
					$browserline = ($browserlineFullHierarchy ? $browserline : '') . $dividingTextInBrowserLine . $contents;
					
					# Allow the behaviour to be overridden by including a behavioural hack file
					if ($behaviouralHackFile) {include $behaviouralHackFile;}
				}
			}
			
			# $menusection which is used for showing the correct menu, stripping off the trailing slash in it
			$menusection = $subdirectories[0];
			$menufile = $serverRoot . $homeLocation . $menusection . '/' . $menuTitleFile;
		}
		
		# Return the result
		return array ($browserline, $locationline, $menusection, $menufile);
	}
	
	
	# Define a function to generate the menu
	function generateMenu ($menu, $cssSelected = 'selected', $parentTabLevel = 2, $orphanedDirectories = array (), $menufile = '')
	{
		# Ensure the orphanedDirectories supplied is an array
		if (!is_array ($orphanedDirectories)) {$orphanedDirectories = array ();}
		
		# Loop through each menu item to match the starting location but take account of lower-level subdirectories override higher-level directories
		$match = '';
		foreach ($menu as $location => $description) {
			if (($location == (substr ($_SERVER['REQUEST_URI'], 0, strlen ($location)))) && (strlen ($location) > strlen ($match))) {
				$match = $location;
			}
		}
		
		# If no match has been found, check whether the requested page is an orphaned directory (i.e. has no menu item)
		if ($match == '') {
			foreach ($orphanedDirectories as $orphanedDirectory => $orphanAssignment) {
				if (($orphanedDirectory == (substr ($_SERVER['REQUEST_URI'], 0, strlen ($orphanedDirectory)))) && (strlen ($orphanedDirectory) > strlen ($match))) {
					$match = $orphanAssignment;
				}
			}
		}
		
		# Define the starting tab level
		$tabs = str_repeat ("\t", ($parentTabLevel));
		
		# Create the HTML
		echo "\n$tabs<ul>";
		$spaced = false;
		foreach ($menu as $location => $description) {
			
			# Set the spacer flag if necessary
			if ((substr ($location, 0, 6) == 'spacer') && ($description == '_')) {
				$spaced = true;
				continue;
			}
			
			# Show the link
			echo "\n$tabs\t" . '<li class="' . str_replace (array ('/', ':', '.'), array ('', '-', '-'), $location) . ($match == $location ? " $cssSelected" : '') . (($spaced) ? ' spaced' : '') . "\"><a href=\"$location\">$description</a>";
			
			# Reset the spacer flag
			$spaced = false;
			
			# Include the menu file
			if ($match == $location) {
				if (!empty ($menufile)) {
					if (file_exists ($menufile)) {
						include ($menufile);
					}
				}
			}
			
			# End the menu item
			echo '</li>';
		}
		echo "\n$tabs</ul>";
	}
	
	
	# Function to switch stylesheet style via a cookie
	function switchStyle ($stylesheets, $directory)
	{
		# Allow the style to be set via a URL
		if (isSet ($_GET['style'])) {
			
			# Set the cookie
			setcookie ('style', $_GET['style'], time() + 31536000, '/', $_SERVER['SERVER_NAME'], '0');
			
			# Send the user back to the previous page (or the front page if not set); NB: the previous page cannot have had ?style=[whatever] in it because that would have been redirected
			$referrer = (isSet ($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'http://' . $_SERVER['SERVER_NAME'] . '/');
			header ("Location: $referrer");
		}
		
		# Assign the cookie style if that is set and it exists
		if (isSet ($_COOKIE['style'])) {
			foreach ($stylesheets as $stylesheet => $name) {
				if ($_COOKIE['style'] == $stylesheet) {
					$style = $_COOKIE['style'];
					break;
				}
			}
		}
		
		# Otherwise set the default (first) style in the array
		$temp = each ($stylesheets);
		$style = (isSet ($style) ? $style : $temp['key']);
		
		# Start the HTML
		$html['header'] = '<style type="text/css" media="all" title="User Defined Style">@import "' . $directory . $style . '.css";</style>';
		$html['links']  = "\n\t" . '<ul class="switch">';
		$html['links'] .= "\n\t\t" . '<li>Switch style:</li>';
		
		# Add in the other links
		foreach ($stylesheets as $file => $name) {
			
			# Add in the header links (but not to the present one)
			if ($style != $file) {
				$html['header'] .= "\n\t" . '<link rel="alternate stylesheet" type="text/css" href="' . $directory . $file . '.css" title="' . $name . '" />';
			}
			
			# Add in the on-page links (including the present one for page stability)
			$html['links']  .= "\n\t\t" . '<li><a href="?style=' . $file . '" title="Switch style (requires cookies)">' . $name . '</a></li>';
		}
		
		# Finish off the HTML
		$html['header'] .= "\n";
		$html['links']  .= "\n\t</ul>";
		
		# Return the HTML
		return $html;
	}
	
	
	
	# Wrapper function to provide search term highlighting
	function highlightSearchTerms ()
	{
		# Echo the result
		return highlightSearchTerms::main ();
	}
	
	
	# Function to create a basic threading system to enable easy previous/index/next links
	function thread ($pages)
	{
		# Loop through the list of pages numerically to find a match
		$totalPages = count ($pages);
		for ($page = 0; $page < $totalPages; $page++) {
			
			# If there's a match with the current page, break out of the loop and assign the previous/next links
			if ($pages[$page] == $_SERVER['REQUEST_URI']) {
				break;
			}
		}
		
		# Construct the HTML
		$html  = "\n" . '<ul class="thread">';
		$html .= (isSet ($pages[$page - 1]) ? "\n\t" . '<li><a href="' . $pages[$page - 1] . '">&lt; Previous</a></li>' : '');
		$html .= (isSet ($pages[0]) ? "\n\t" . '<li><a href="' . $pages[0] . '">Home</a></li>' : '');
		$html .= (isSet ($pages[$page + 1]) ? "\n\t" . '<li><a href="' . $pages[$page + 1] . '">Next &gt;</a></li>' : '');
		$html .= "\n" . '</ul>';
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to create a jumplist form
	function htmlJumplist ($values, $selected = '', $action = '', $name = 'jumplist', $parentTabLevel = 0, $class = 'jumplist', $introductoryText = 'Go to:')
	{
		# Return an empty string if no items
		if (empty ($values)) {return '';}
		
		# Prepare the tab string
		$tabs = str_repeat ("\t", ($parentTabLevel));
		
		# Build the list
		foreach ($values as $value => $visible) {
			$fragments[] = "<option value=\"{$value}\"" . ($value == $selected ? ' selected="selected"' : '') . ">$visible</option>";
		}
		
		# Construct the HTML
		$html  = "\n\n$tabs" . "<div class=\"$class\">$introductoryText";
		$html .= "\n$tabs\t" . "<form method=\"post\" action=\"$action\" name=\"$name\">";
		$html .= "\n$tabs\t\t" . "<select name=\"$name\">";
		$html .= "\n$tabs\t\t\t" . implode ("\n$tabs\t\t\t", $fragments);
		$html .= "\n$tabs\t\t" . '</select>';
		$html .= "\n$tabs\t\t" . '<input type="submit" value="Go!" class="button" />';
		$html .= "\n$tabs\t" . '</form>';
		$html .= "\n$tabs" . '</div>' . "\n";
		
		# Return the result
		return $html;
	}
	
	
	# Function to process the jumplist
	function jumplistProcessor ($name = 'jumplist')
	{
		# If posted, jump, adding the current site's URL if the target doesn't start with http(s);//
		if (isSet ($_POST[$name])) {
			$location = (eregi ('http://|https://', $_POST[$name]) ? '' : $_SERVER['_SITE_URL']) . $_POST[$name];
			require_once ('application.php');
			application::sendHeader (302, $location);
		}
	}
}


# Class for highlighting words from a search engine's referring page which includes search terms in the URL
class highlightSearchTerms
{
	# Quasi-constructor
	function main ()
	{
		# Only run the buffer if there is an outside referer, to save processing speed
		if (empty ($_SERVER['HTTP_REFERER'])) {return;}
		
		# Get the referer
		if (!$referer = @parse_url ($_SERVER['HTTP_REFERER'])) {return;}
		
		# Buffer the output
		if (isSet ($referer['host'])) {
			if ($referer['host'] != $_SERVER['HTTP_HOST']) {
				ob_start (array ('highlightSearchTerms', 'wrapper')); 
			}
		}
	}
	
	
	# List the supported search engines (which use & as the splitter and + between query words) as hostname core => query variable in URL.
	function supportedSearchEngines ()
	{
		# Return an array of search engines
		return $searchEngines = array (
			'google' => 'q',
			'yahoo' => 'p',
			'altavista' => 'q',
			'lycos' => 'query',
			'alltheweb' => 'q',
			'teoma' => 'q',
			'cam.ac' => 'qt',
		);
	}
	
	
	# List the available colours for highlighting, or enter 'highlight' to use class="highlight"
	#!# This should be set in the options instead of as a method
	function availableColours ()
	{
		# Return an array of available colours
		return $colours = array (
			'referer',
		);
	}
	
	
	# Wrapper function
	function wrapper ($string)
	{
		# Get the list of search engines and colours
		$searchEngines = highlightSearchTerms::supportedSearchEngines ();
		$colours = highlightSearchTerms::availableColours ();
		
		# Obtain the query words (if any) from the referring page
		if ($queryWords = highlightSearchTerms::obtainQueryWords ($searchEngines)) {
			
			# Introduce the HTML
			$html = '<p class="referer">Words you searched for have been highlighted.</p>' . "\n";
			
			# Modify the HTML
			$html .= highlightSearchTerms::replaceHtml ($string, $queryWords, $colours, $limitWords = 8);
			
			# Return the HTML
			return $html;
		}
		
		# Otherwise return the unmodified HTML
		return $string;
	}
	
	
	# Obtain the query words
	function obtainQueryWords ($searchEngines)
	{
		# Parse the URL so that the hostname can be obtained
		$referer = parse_url ($_SERVER['HTTP_REFERER']);
		
		# Continue if the referer contains a query term
		if (isSet ($referer['query'])) {
			
			# Loop through each of the search engines to determine if the previous page is from one of them
			$matched = false;
			while (list ($searchEngine['nameCore'], $searchEngine['queryVariable']) = each ($searchEngines)) {
				
				# Run a match against the search engine's name with a dot either side, e.g. .google.[com]; NB this could be subverted by e.g. www.google.foobar.com
				if (strpos ($referer['host'], ('.' . $searchEngine['nameCore'] . '.')) !== false) {
					
					# Flag the match then break so that the selected search engine is held in the array $searchEngine
					$matched = true;
					break;
				}
			}
			
			# If matched, obtain the query string used in the referring page
			if ($matched) {
				
				# Make an array of the previous page's query terms
				$queryTerms = explode ('&', $referer['query']);
				
				# Loop through each of the query terms until the relevant one is found
				$queryTermMatched = false;
				foreach ($queryTerms as $queryTerm) {
					
					# Do a match against the relevant query term e.g. q= at the start
					if (eregi (('^' . $searchEngine['queryVariable'] . '='), $queryTerm)) {
						
						# Flag the match then break so that the search query term is held in the variable $queryTerm
						$queryTermMatched = true;
						break;
					}
				}
				
				# If there is a match, obtain the query phrase from the query term (i.e. the words after the =
				if ($queryTermMatched) {
					list ($discarded, $queryPhrase) = explode ('=', $queryTerm);
					
					# Strip " (which is encoded as %22) from the query
					$queryPhrase = str_replace ('%22', '', $queryPhrase);
					
					# Split the query phrase into words demarcated by +
					$queryWords = explode ('+', $queryPhrase);
					
					# Return the result
					return $queryWords;
				}
			}
		}
		
		# Otherwise return false
		return false;
	}
	
	
	# Function to highlight search terms based on GPL'ed script by Eric Bodden - see www.bodden.de/projects/php/
	function replaceHtml ($html, $searchWords, $colours = 'yellow', $limitWords = 8)
	{
		# Assign the colours to be used, into an array
		if (!is_array ($colours)) {
			$temporary = $colours;
			unset ($colours);
			$colours[] = $temporary;
		}
		
		# Count the number of colours available
		$totalColours = count ($colours);
		
		# Loop through each of the search words
		$i = 0;
		foreach ($searchWords as $searchWord) {
		    
			# Stop further parsing if a large number of words have been supplied
			if ($i == $limitWords) {break;}
			
			# Escape slashes to prevent PCRE errors as listed on www.php.net/pcre.pattern.syntax
			$searchWord = preg_quote ($searchWord, '/');
			
			# Run a regexp match and put the matches into $matches[0]
			$regexpStart = '>[^<]*(';
			$regexpEnd = ')[^<]*<';
			preg_match_all (('/' . $regexpStart . $searchWord . $regexpEnd . '/i'), $html, $matches, PREG_PATTERN_ORDER);
			
			# Assign whether to use class="highlight" or style="background-color: foo;"
			switch ($colours[0]) {
				case 'referer':
					$highlightCodeStart = '<span class="referer">';
					break;
				default:
					$highlightCodeStart = '<span style="background-color: ' . $colours[($i % $totalColours)] . ';">';
					break;
			}
			
			# Loop through each of the matches; NB This sometimes times out (due to lots of words on a long page), but there's little that can be done, other than stopping replacement half-way through
			#!# Clean this up
			foreach ($matches[0] as $match) {
				preg_match ("/$searchWord/i", $match, $out);
				$case_sensitive_searchWord = $out[0];
				$newtext = str_replace ($case_sensitive_searchWord, ($highlightCodeStart . $case_sensitive_searchWord . '</span>'), $match);
				$html = str_replace ($match, $newtext, $html);
			}
			
			$i++;
		}
		
		# Return the result
		#!# This could be undefined...
		return $html;
	}
}

?>