<?php
	//constants
	if (!defined('SEARCH_FOR')) define('SEARCH_FOR', 'Search for');
	if (!defined('SEARCH_ZERO_MATCH')) define('SEARCH_ZERO_MATCH', 'No matches');
	if (!defined('SEARCH_ONE_MATCH')) define('SEARCH_ONE_MATCH', 'One match found');
	if (!defined('SEARCH_N_MATCH')) define('SEARCH_N_MATCH', 'There was %d matches found');
	if (!defined('SEARCH_RESULTS')) define('SEARCH_RESULTS', 'Search results');
	if (!defined('SEARCH_TRY_EXPANDED')) define('SEARCH_TRY_EXPANDED', '<br />Not sure which page to choose?<br />Try the <a href="$1">Expanded Text Search</a> which shows surrounding text.');
	if (!defined('SEARCH_TIPS')) define('SEARCH_TIPS', "<br /><br /><hr /><br /><strong>Search Tips:</strong><br /><br />"
		."<div class=\"indent\">apple banana</div>"
		."Find pages that contain at least one of the two words. <br />"
		."<br />"
		."<div class=\"indent\">+apple +juice</div>"
		."Find pages that contain both words. <br />"
		."<br />"
		."<div class=\"indent\">+apple -macintosh</div>"
		."Find pages that contain the word 'apple' but not 'macintosh'. <br />"
		."<br />"
		."<div class=\"indent\">apple*</div>"
		."Find pages that contain words such as apple, apples, applesauce, or applet. <br />"
		."<br />"
		."<div class=\"indent\">\"some words\"</div>"
		."Find pages that contain the exact phrase 'some words' (for example, pages that contain 'some words of wisdom' <br />"
		."but not 'some noise words'). <br />");
	$result_page_list = '';
?>		
<?php echo $this->FormOpen("", "", "get"); ?>
<table border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td><?php echo SEARCH_FOR; ?>:&nbsp;</td>
		<td><input name="phrase" size="40" value="<?php if (isset($_REQUEST["phrase"])) echo $this->htmlspecialchars_ent(stripslashes($_REQUEST["phrase"])); ?>" /> <input type="submit" value="Search"/></td>
	</tr>
</table><br />
<?php echo $this->FormClose(); ?>

<?php
if (isset($_REQUEST['phrase']) && ($phrase = $_REQUEST["phrase"]))
{
	$phrase_re = stripslashes(trim($phrase)); 
	if (!$phrase_re) return;
	$results = $this->FullTextSearch($phrase_re);
	$total_results = 0;
	if ($results)
	{
		foreach ($results as $i => $page)
		{
			if ($this->HasAccess("read",$page["tag"]))
			{
				$total_results ++;
				$result_page_list .= ($i+1).". ".$this->Link($page["tag"])."<br />\n";
			}
		}
	}
	switch ($total_results)
	{
		case 0:
			$match_str = SEARCH_ZERO_MATCH;
			break;
		case 1:
			$match_str = SEARCH_ONE_MATCH;
			break;
		default:
			$match_str = SEARCH_N_MATCH;
			break;
	}
	printf(SEARCH_RESULTS.": <strong>".$match_str."</strong> for <strong>".$this->htmlspecialchars_ent($phrase)."</strong><br />\n", $total_results);
	if ($total_results > 0)
	{
		print($result_page_list);
		print(str_replace('$1', $this->href("", "TextSearchExpanded", 'phrase='.urlencode($phrase)), SEARCH_TRY_EXPANDED));
	}
}
if ($this->CheckMySQLVersion(4,00,01))
{	
	print(SEARCH_TIPS);
}
?>
