<?php
class QUnit_Txt_Html2Text
{

    /**
     *  Contains the HTML content to convert.
     *
     *  @var string $html
     *  @access public
     */
    var $html;

    /**
     *  Contains the converted, formatted text.
     *
     *  @var string $text
     *  @access public
     */
    var $text;

    /**
     *  Maximum width of the formatted text, in columns.
     *
     *  Set this value to 0 (or less) to ignore word wrapping
     *  and not constrain text to a fixed-width column.
     *
     *  @var integer $width
     *  @access public
     */
    var $width = 0;

    /**
     *  List of preg* regular expression patterns to search for,
     *  used in conjunction with $replace.
     *
     *  @var array $search
     *  @access public
     *  @see $replace
     */
    var $search = array(
        "/\r/",                                  // Non-legal carriage return
        "/[\n\t]+/",                             // Newlines and tabs
        '/<script[^>]*>.*?<\/script>/i',         // <script>s -- which strip_tags supposedly has problems with
        //'/<!-- .* -->/',                         // Comments -- which strip_tags might have problem a with
        '/<h[123][^>]*>(.+?)<\/h[123]>/ie',      // H1 - H3
        '/<h[456][^>]*>(.+?)<\/h[456]>/ie',      // H4 - H6
        '/<p[^>]*>/i',                           // <P>
        '/<br[^>]*>/i',                          // <br>
        '/<b[^>]*>(.+?)<\/b>/ie',                // <b>
        '/<i[^>]*>(.+?)<\/i>/i',                 // <i>
        '/(<ul[^>]*>|<\/ul>)/i',                 // <ul> and </ul>
        '/(<ol[^>]*>|<\/ol>)/i',                 // <ol> and </ol>
        '/<li[^>]*>/i',                          // <li>
        '/<a href="([^"]+)"[^>]*>(.+?)<\/a>/ie', // <a href="">
        '/<hr[^>]*>/i',                          // <hr>
        '/(<table[^>]*>|<\/table>)/i',           // <table> and </table>
        '/(<tr[^>]*>|<\/tr>)/i',                 // <tr> and </tr>
        '/<td[^>]*>(.+?)<\/td>/i',               // <td> and </td>
        '/<th[^>]*>(.+?)<\/th>/i',               // <th> and </th>
        '/&nbsp;/i',
        '/&quot;/i',
        '/&gt;/i',
        '/&lt;/i',
        '/&amp;/i',
        '/&copy;/i',
        '/&trade;/i',
        '/&#8220;/',
        '/&#8221;/',
        '/&#8211;/',
        '/&#8217;/',
        '/&#38;/',
        '/&#169;/',
        '/&#8482;/',
        '/&#151;/',
        '/&#147;/',
        '/&#148;/',
        '/&#149;/',
        '/&reg;/i',
        '/&bull;/i',
        '/&[&;]+;/i'
    );

    /**
     *  List of pattern replacements corresponding to patterns searched.
     *
     *  @var array $replace
     *  @access public
     *  @see $search
     */
    var $replace = array(
        '',                                     // Non-legal carriage return
        ' ',                                    // Newlines and tabs
        '',                                     // <script>s -- which strip_tags supposedly has problems with
        //'',                                     // Comments -- which strip_tags might have problem a with
        "strtoupper(\"\n\n\\1\n\n\")",          // H1 - H3
        "ucwords(\"\n\n\\1\n\n\")",             // H4 - H6
        "\n\n\t",                               // <P>
        "\n",                                   // <br>
        'strtoupper("\\1")',                    // <b>
        '_\\1_',                                // <i>
        "\n\n",                                 // <ul> and </ul>
        "\n\n",                                 // <ol> and </ol>
        "\t*",                                  // <li>
        '$this->_build_link_list($link_count++, "\\1", "\\2")',
                                                // <a href="">
        "\n-------------------------\n",        // <hr>
        "\n\n",                                 // <table> and </table>
        "\n",                                   // <tr> and </tr>
        "\t\t\\1\n",                            // <td> and </td>
        "strtoupper(\"\t\t\\1\n\")",            // <th> and </th>
        ' ',
        '"',
        '>',
        '<',
        '&',
        '(c)',
        '(tm)',
        '"',
        '"',
        '-',
        "'",
        '&',
        '(c)',
        '(tm)',
        '--',
        '"',
        '"',
        '*',
        '(R)',
        '*',
        ''
    );

    /**
     *  Contains a list of HTML tags to allow in the resulting text.
     *
     *  @var string $allowed_tags
     *  @access public
     *  @see set_allowed_tags()
     */
    var $allowed_tags = '';

    /**
     *  Contains the base URL that relative links should resolve to.
     *
     *  @var string $url
     *  @access public
     */
    var $url;

    /**
     *  Indicates whether content in the $html variable has been converted yet.
     *
     *  @var boolean $converted
     *  @access private
     *  @see $html, $text
     */
    var $_converted = false;

    /**
     *  Contains URL addresses from links to be rendered in plain text.
     *
     *  @var string $link_list
     *  @access private
     *  @see _build_link_list()
     */
    var $_link_list;

    /**
     *  Constructor.
     *
     *  If the HTML source string (or file) is supplied, the class
     *  will instantiate with that source propagated, all that has
     *  to be done it to call get_text().
     *
     *  @param string $source HTML content
     *  @param boolean $from_file Indicates $source is a file to pull content from
     *  @access public
     *  @return void
     */
    function html2text( $source = '', $from_file = false )
    {
        if ( !empty($source) ) {
            $this->set_html($source, $from_file);
        }
        $this->set_base_url();
    }

    /**
     *  Loads source HTML into memory, either from $source string or a file.
     *
     *  @param string $source HTML content
     *  @param boolean $from_file Indicates $source is a file to pull content from
     *  @access public
     *  @return void
     */
    function set_html( $source, $from_file = false )
    {
        $this->html = $source;

        if ( $from_file && file_exists($source) ) {
            $fp = fopen($source, 'r');
            $this->html = fread($fp, filesize($source));
            fclose($fp);
        }

        $this->_converted = false;
    }

    /**
     *  Returns the text, converted from HTML.
     *
     *  @access public
     *  @return string
     */
    function get_text()
    {
        if ( !$this->_converted ) {
            $this->_convert();
        }

        return $this->text;
    }

    /**
     *  Sets the allowed HTML tags to pass through to the resulting text.
     *
     *  Tags should be in the form "<p>", with no corresponding closing tag.
     *
     *  @access public
     *  @return void
     */
    function set_allowed_tags( $allowed_tags = '' )
    {
        if ( !empty($allowed_tags) ) {
            $this->allowed_tags = $allowed_tags;
        }
    }

    /**
     *  Sets a base URL to handle relative links.
     *
     *  @access public
     *  @return void
     */
    function set_base_url( $url = '' )
    {
        if ( empty($url) ) {
            $this->url = 'http://' . $_SERVER['HTTP_HOST'];
        } else {
            // Strip any trailing slashes for consistency (relative
            // URLs may already start with a slash like "/file.html")
            if ( substr($url, -1) == '/' ) {
                $url = substr($url, 0, -1);
            }
            $this->url = $url;
        }
    }

    /**
     *  Workhorse function that does actual conversion.
     *
     *  First performs custom tag replacement specified by $search and
     *  $replace arrays. Then strips any remaining HTML tags, reduces whitespace
     *  and newlines to a readable format, and word wraps the text to
     *  $width characters.
     *
     *  @access private
     *  @return void
     */
    function _convert()
    {
    	$badStr = $this->html;
	    //remove PHP if it exists
	    while( substr_count( $badStr, '<'.'?' ) && substr_count( $badStr, '?'.'>' ) && strpos( $badStr, '?'.'>', strpos( $badStr, '<'.'?' ) ) > strpos( $badStr, '<'.'?' ) ) {
	        $badStr = substr( $badStr, 0, strpos( $badStr, '<'.'?' ) ) . substr( $badStr, strpos( $badStr, '?'.'>', strpos( $badStr, '<'.'?' ) ) + 2 ); }
	    //remove comments
	    while( substr_count( $badStr, '<!--' ) && substr_count( $badStr, '-->' ) && strpos( $badStr, '-->', strpos( $badStr, '<!--' ) ) > strpos( $badStr, '<!--' ) ) {
	        $badStr = substr( $badStr, 0, strpos( $badStr, '<!--' ) ) . substr( $badStr, strpos( $badStr, '-->', strpos( $badStr, '<!--' ) ) + 3 ); }
	    //now make sure all HTML tags are correctly written (> not in between quotes)

	    
	    //TODO: here is any problem, because in some cases happens loop and mail is never parsed
	    //	    for( $x = 0, $goodStr = '', $is_open_tb = false, $is_open_sq = false, $is_open_dq = false; strlen( $chr = $badStr{$x} ); $x++ ) {
//	        //take each letter in turn and check if that character is permitted there
//	        switch( $chr ) {
//	            case '<':
//	                if( !$is_open_tb && strtolower( substr( $badStr, $x + 1, 5 ) ) == 'style' ) {
//	                    $badStr = substr( $badStr, 0, $x ) . substr( $badStr, strpos( strtolower( $badStr ), '</style>', $x ) + 7 ); $chr = '';
//	                } elseif( !$is_open_tb && strtolower( substr( $badStr, $x + 1, 6 ) ) == 'script' ) {
//	                    $badStr = substr( $badStr, 0, $x ) . substr( $badStr, strpos( strtolower( $badStr ), '</script>', $x ) + 8 ); $chr = '';
//	                } elseif( !$is_open_tb ) { $is_open_tb = true; } else { $chr = '&lt;'; }
//	                break;
//	            case '>':
//	                if( !$is_open_tb || $is_open_dq || $is_open_sq ) { $chr = '&gt;'; } else { $is_open_tb = false; }
//	                break;
//	            case '"':
//	                if( $is_open_tb && !$is_open_dq && !$is_open_sq ) { $is_open_dq = true; }
//	                elseif( $is_open_tb && $is_open_dq && !$is_open_sq ) { $is_open_dq = false; }
//	                else { $chr = '&quot;'; }
//	                break;
//	            case "'":
//	                if( $is_open_tb && !$is_open_dq && !$is_open_sq ) { $is_open_sq = true; }
//	                elseif( $is_open_tb && !$is_open_dq && $is_open_sq ) { $is_open_sq = false; }
//	        } $goodStr .= $chr;
//	    }
$goodStr = $badStr;
	    
	    //now that the page is valid (I hope) for strip_tags, strip all unwanted tags
	    $goodStr = strip_tags( $goodStr, '<title><hr><h1><h2><h3><h4><h5><h6><div><p><br><pre><sup><ul><ol><dl><dt><table><caption><tr><li><dd><th><td><a><area><img><form><input><textarea><button><select><option>' );
	    //strip extra whitespace except between <pre> and <textarea> tags
	    $badStr = preg_split( "/<\/?pre[^>]*>/i", $goodStr );
	    for( $x = 0; is_string( $badStr[$x] ); $x++ ) {
	        if( $x % 2 ) { $badStr[$x] = '<pre>'.$badStr[$x].'</pre>'; } else {
	            $goodStr = preg_split( "/<\/?textarea[^>]*>/i", $badStr[$x] );
	            for( $z = 0; is_string( $goodStr[$z] ); $z++ ) {
	                if( $z % 2 ) { $goodStr[$z] = '<textarea>'.$goodStr[$z].'</textarea>'; } else {
	                    $goodStr[$z] = preg_replace( "/\s+/", ' ', $goodStr[$z] );
	            } }
	            $badStr[$x] = implode('',$goodStr);
	    } }
	    $goodStr = implode('',$badStr);

		$search = array(
		        "/\r/",                                  // Non-legal carriage return
		        "/[\n\t]+/",                             // Newlines and tabs
		        '/<br[^>]*>/i',                          // <br>
		        '/&nbsp;/i',
		        '/&quot;/i',
		        '/&gt;/i',
		        '/&lt;/i',
		        '/&amp;/i',
		        '/&copy;/i',
		        '/&trade;/i',
		        '/&#8220;/',
		        '/&#8221;/',
		        '/&#8211;/',
		        '/&#8217;/',
		        '/&#38;/',
		        '/&#169;/',
		        '/&#8482;/',
		        '/&#151;/',
		        '/&#147;/',
		        '/&#148;/',
		        '/&#149;/',
		        '/&reg;/i',
		        '/&bull;/i',
		        '/&[&;]+;/i'
		    );

			$replace = array(
		        '',                                     // Non-legal carriage return
		        ' ',                                    // Newlines and tabs
		        "\n",                                   // <br>
		        ' ',
		        '"',
		        '>',
		        '<',
		        '&',
		        '(c)',
		        '(tm)',
		        '"',
		        '"',
		        '-',
		        "'",
		        '&',
		        '(c)',
		        '(tm)',
		        '--',
		        '"',
		        '"',
		        '*',
		        '(R)',
		        '*',
		        ''
		    );
	    
	    
	    $goodStr = preg_replace( $search, $replace, $goodStr );
	    
	    //remove all options from select inputs
	    $goodStr = preg_replace( "/<option[^>]*>[^<]*/i", '', $goodStr );
	    //replace all tags with their text equivalents
	    $goodStr = preg_replace( "/<(\/title|hr)[^>]*>/i", "\n          --------------------\n", $goodStr );
	    $goodStr = preg_replace( "/<(h|div|p)[^>]*>/i", "\n", $goodStr );
	    $goodStr = preg_replace( "/<sup[^>]*>/i", '^', $goodStr );
	    $goodStr = preg_replace( "/<(ul|ol|dl|dt|table|caption|\/textarea|tr[^>]*>\s*<(td|th))[^>]*>/i", "\n", $goodStr );
	    $goodStr = preg_replace( "/<li[^>]*>/i", "\n· ", $goodStr );
	    $goodStr = preg_replace( "/<dd[^>]*>/i", "\n\t", $goodStr );
	    $goodStr = preg_replace( "/<(th|td)[^>]*>/i", "\t", $goodStr );
	    $goodStr = preg_replace('/<br[^>]*>/i', "\n", $goodStr);
	    $goodStr = preg_replace( "/<a[^>]* href=(\"((?!\"|#|javascript:)[^\"#]*)(\"|#)|'((?!'|#|javascript:)[^'#]*)('|#)|((?!'|\"|>|#|javascript:)[^#\"'> ]*))[^>]*>/i", "[LINK: $2$4$6] ", $goodStr );
	    $goodStr = preg_replace( "/<img[^>]* alt=(\"([^\"]+)\"|'([^']+)'|([^\"'> ]+))[^>]*>/i", "[IMAGE: $2$3$4] ", $goodStr );
	    $goodStr = preg_replace( "/<form[^>]* action=(\"([^\"]+)\"|'([^']+)'|([^\"'> ]+))[^>]*>/i", "\n[FORM: $2$3$4] ", $goodStr );
	    $goodStr = preg_replace( "/<(input|textarea|button|select)[^>]*>/i", "[INPUT] ", $goodStr );
	    //strip all remaining tags (mostly closing tags)
	    $goodStr = strip_tags( $goodStr );
	    //convert HTML entities
	    $goodStr = strtr( $goodStr, array_flip( get_html_translation_table( HTML_ENTITIES ) ) );
	    preg_replace( "/&#(\d+);/me", "chr('$1')", $goodStr );
	    //wordwrap
	    //$goodStr = wordwrap( $goodStr );
	    //make sure there are no more than 3 linebreaks in a row and trim whitespace
	    $this->text = preg_replace( "/^\n*|\n*$/", '', preg_replace( "/[ \t]+(\n|$)/", "$1", preg_replace( "/\n(\s*\n){2}/", "\n\n\n", preg_replace( "/\r\n?|\f/", "\n", str_replace( chr(160), ' ', $goodStr ) ) ) ) );
    }

    /**
     *  Helper function called by preg_replace() on link replacement.
     *
     *  Maintains an internal list of links to be displayed at the end of the
     *  text, with numeric indices to the original point in the text they
     *  appeared. Also makes an effort at identifying and handling absolute
     *  and relative links.
     *
     *  @param integer $link_count Counter tracking current link number
     *  @param string $link URL of the link
     *  @param string $display Part of the text to associate number with
     *  @access private
     *  @return string
     */
    function _build_link_list($link_count, $link, $display)
    {
        if ( substr($link, 0, 7) == 'http://' || substr($link, 0, 8) == 'https://' ||
             substr($link, 0, 7) == 'mailto:' ) {
            $this->_link_list .= "[$link_count] $link\n";
        } else {
            $this->_link_list .= "[$link_count] " . $this->url;
            if ( substr($link, 0, 1) != '/' ) {
                $this->_link_list .= '/';
            }
            $this->_link_list .= "$link\n";
        }

        return $display . ' [' . $link_count . ']';
    }

}
?>