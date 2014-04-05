<?php
	$t = $this;
	
	$head = array(
		'robots_meta' => '',
		'revisions_xml_link' => '',
		'changes_xml_link' => '',
		'ueb_link' => '',
		'additional_headers' => ''
	);
	
	if ( (! is_null($t->wikka->GetHandler()) && $t->wikka->GetHandler() != 'show') ||
			$t->wikka->page["latest"] == 'N' ||
			$t->wikka->page["tag"] == 'SandBox' ) {
		$head['robots_meta'] =
			'<meta name="robots" content="noindex, nofollow, noarchive" />';
	}
	
	if ( $t->wikka->GetHandler() != 'edit' ) {
		$head['revisions_xml_link'] = $t->build_alternate_link(
			'application/rss+xml',
			sprintf('%s: revisions for %s (RSS)', $t->escape_config('wakka_name'),
				$t->get_page_tag()),
			$t->wikka->Href('revisions.xml', $t->get_page_tag())
		);
		
		$head['changes_xml_link'] = $t->build_alternate_link(
			'application/rss+xml',
			sprintf('%s: recently edited pages (RSS)', $t->escape_config('wakka_name')),
			$t->wikka->Href('recentchanges.xml', $t->get_page_tag())
		);
	}
	
	if ( $t->wikka->GetHandler() != 'edit' && $t->wikka->HasAccess(
			"write", $t->get_page_tag()) ) {
		$head['ueb_link'] = $t->build_alternate_link(
			'application/x-wiki',
			sprintf('Click to edit %s', $t->get_page_tag()),
			$t->wikka->Href('edit', $t->get_page_tag())
		);
	}
	
	if ( isset($t->wikka->additional_headers) &&
			is_array($t->wikka->additional_headers) &&
			count($t->wikka->additional_headers) ) {
		$head['additional_headers'] = implode("\n", $t->wikka->additional_headers);
	}
?>
  <head>
    <title><?php echo $t->page_title; ?></title>
	<base href="<?php echo WIKKA_BASE_URL; ?>" />
	
	<!-- Meta Tags -->
	<meta name="generator" content="WikkaWiki">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="keywords" content="<?php echo $t->escape_config('meta_keywords'); ?>" />
    <meta name="description" content="<?php 
		echo $t->escape_config('meta_description'); ?>" />
	<?php echo $head['robots_meta']; ?>
	
	<!-- Favicons -->
    <link rel="icon" type="image/x-icon"
		  href="templates/bootstrap/images/favicon.ico"  />
    <link rel="shortcut icon" type="image/x-icon"
		  href="templates/bootstrap/images/favicon.ico"  />

    <!-- Stylesheets -->
    <link rel="stylesheet" type="text/css" href="templates/light/css/light.css?<?php
			echo $t->escape_config('stylesheet_hash'); ?>" />
	<link rel="stylesheet" type="text/css" media="print" 
		  href="templates/bootstrap/css/print.css" />
	
	<!-- Alternate Application Links -->
	<?php echo $head['revisions_xml_link']; ?>
	<?php echo $head['changes_xml_link']; ?>
	<?php echo $head['ueb_link']; ?>
	
	<?php echo $head['additional_headers']; ?>
  </head>